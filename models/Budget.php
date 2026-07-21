<?php
require_once __DIR__ . '/../config/database.php';

class Budget {
    
    // Récupérer tous les budgets d'un utilisateur (ceux qu'il a créés + ceux où il est membre)
    public static function getAllByUser(int $userId): array {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT b.*, 
                    COALESCE((SELECT SUM(t.montant) FROM transactions t WHERE t.id_budget = b.id AND t.type = 'depense'), 0) AS depenses,
                    (SELECT COUNT(*) FROM budgets_membres m WHERE m.id_budget = b.id) AS membres
             FROM budgets b
             WHERE b.id_createur = ? OR b.id IN (SELECT id_budget FROM budgets_membres WHERE id_utilisateur = ?)
             ORDER BY b.date_debut DESC"
        );
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }

    // Récupérer les budgets actifs d'un utilisateur (période en cours)
    public static function getActiveByUser(int $userId): array {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT b.*, 
                    COALESCE((SELECT SUM(t.montant) FROM transactions t WHERE t.id_budget = b.id AND t.type = 'depense'), 0) AS depenses,
                    (SELECT COUNT(*) FROM budgets_membres m WHERE m.id_budget = b.id) AS membres
             FROM budgets b
             WHERE (b.id_createur = ? OR b.id IN (SELECT id_budget FROM budgets_membres WHERE id_utilisateur = ?))
             AND b.date_debut <= CURDATE() AND b.date_fin >= CURDATE()
             ORDER BY b.date_debut DESC"
        );
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }

    // Récupérer un budget par son ID
    public static function getById(int $id, int $userId): array|false {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT * FROM budgets WHERE id = ? AND (id_createur = ? OR id IN (SELECT id_budget FROM budgets_membres WHERE id_utilisateur = ?)) LIMIT 1"
        );
        $stmt->execute([$id, $userId, $userId]);
        return $stmt->fetch();
    }

public static function create(int $userId, array $data): bool {
    $db = getDB();
    
    // S'assurer qu'on n'insère pas explicitement l'ID
    $stmt = $db->prepare(
        "INSERT INTO budgets (nom, type, periode, date_debut, date_fin, plafond_global, id_createur)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    
    return $stmt->execute([
        $data['nom'],
        $data['type'],
        $data['periode'],
        $data['date_debut'],
        $data['date_fin'],
        $data['plafond_global'],
        $userId
    ]);
}

    // Mettre à jour un budget
    public static function update(int $id, int $userId, array $data): bool {
        $db = getDB();
        $stmt = $db->prepare(
            "UPDATE budgets SET nom = ?, type = ?, periode = ?, date_debut = ?, date_fin = ?, plafond_global = ?
             WHERE id = ? AND id_createur = ?"
        );
        return $stmt->execute([
            $data['nom'],
            $data['type'],
            $data['periode'],
            $data['date_debut'],
            $data['date_fin'],
            $data['plafond_global'],
            $id,
            $userId
        ]);
    }

    // Supprimer un budget
    public static function delete(int $id, int $userId): bool {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM budgets WHERE id = ? AND id_createur = ?");
        return $stmt->execute([$id, $userId]);
    }

    // Récupérer l'utilisation d'un budget (dépenses, plafond, pourcentage)
    public static function getUsage(int $budgetId): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT COALESCE(SUM(montant), 0) AS depenses FROM transactions WHERE id_budget = ? AND type = 'depense'");
        $stmt->execute([$budgetId]);
        $depenses = (float)$stmt->fetchColumn();

        $stmt2 = $db->prepare("SELECT plafond_global FROM budgets WHERE id = ? LIMIT 1");
        $stmt2->execute([$budgetId]);
        $plafond = (float)$stmt2->fetchColumn();

        $pourcentage = $plafond > 0 ? round(($depenses / $plafond) * 100, 1) : 0.0;
        return ['depenses' => $depenses, 'plafond' => $plafond, 'pourcentage' => $pourcentage];
    }
}
?>