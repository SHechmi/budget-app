<?php
require_once __DIR__ . '/../config/database.php';

class BudgetPartage {
    public static function createSharedBudget(int $userId, array $data): bool {
        $db = getDB();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare(
                "INSERT INTO budgets (nom, type, periode, date_debut, date_fin, plafond_global, id_createur)
                 VALUES (?, 'partage', ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['nom'],
                $data['periode'],
                $data['date_debut'],
                $data['date_fin'],
                $data['plafond_global'],
                $userId
            ]);

            $budgetId = (int)$db->lastInsertId();
            $memberStmt = $db->prepare("INSERT INTO budgets_membres (id_budget, id_utilisateur, role) VALUES (?, ?, 'createur')");
            $memberStmt->execute([$budgetId, $userId]);
            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function addMember(int $budgetId, int $memberId): bool {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM budgets_membres WHERE id_budget = ? AND id_utilisateur = ?");
        $stmt->execute([$budgetId, $memberId]);
        if ($stmt->fetch()) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO budgets_membres (id_budget, id_utilisateur, role) VALUES (?, ?, 'membre')");
        return $stmt->execute([$budgetId, $memberId]);
    }

    public static function getMembers(int $budgetId): array {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT u.id, u.nom, u.prenom, u.email, m.role, m.date_ajout
             FROM budgets_membres m
             JOIN utilisateurs u ON m.id_utilisateur = u.id
             WHERE m.id_budget = ?"
        );
        $stmt->execute([$budgetId]);
        return $stmt->fetchAll();
    }

    public static function getSharedBudgets(int $userId): array {
        $db = getDB();
        $stmt = $db->prepare(
                "SELECT b.*, COALESCE((SELECT SUM(t.montant) FROM transactions t WHERE t.id_budget = b.id AND t.type = 'depense'), 0) AS depenses,
                    (SELECT COUNT(*) FROM budgets_membres m WHERE m.id_budget = b.id) AS membres
                 FROM budgets b
                 WHERE b.type = 'partage'
                 AND (b.id_createur = ? OR b.id IN (SELECT id_budget FROM budgets_membres WHERE id_utilisateur = ?))
                 ORDER BY b.date_debut DESC"
        );
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }

    public static function getTransactionsForBudget(int $budgetId): array {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT t.*, u.nom AS auteur_nom, u.prenom AS auteur_prenom
             FROM transactions t
             JOIN utilisateurs u ON t.id_utilisateur = u.id
             WHERE t.id_budget = ?
             ORDER BY t.date DESC"
        );
        $stmt->execute([$budgetId]);
        return $stmt->fetchAll();
    }

    public static function addComment(int $transactionId, int $userId, string $contenu): bool {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO commentaires (id_transaction, id_utilisateur, contenu) VALUES (?, ?, ?) ");
        return $stmt->execute([$transactionId, $userId, $contenu]);
    }

    public static function getCommentsByBudget(int $budgetId): array {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT c.*, u.nom AS auteur_nom, u.prenom AS auteur_prenom, t.description AS transaction_description
             FROM commentaires c
             JOIN utilisateurs u ON c.id_utilisateur = u.id
             JOIN transactions t ON c.id_transaction = t.id
             WHERE t.id_budget = ?
             ORDER BY c.date_commentaire DESC"
        );
        $stmt->execute([$budgetId]);
        return $stmt->fetchAll();
    }
}
