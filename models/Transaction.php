<?php
require_once __DIR__ . '/../config/database.php';

class Transaction {
    public static function getAllByUser(int $userId): array {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT t.*, c.nom AS categorie_nom, c.couleur AS categorie_couleur, b.nom AS budget_nom
             FROM transactions t
             LEFT JOIN categories c ON t.id_categorie = c.id
             LEFT JOIN budgets b ON t.id_budget = b.id
             WHERE t.id_utilisateur = ?
             ORDER BY t.date DESC, t.id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function getById(int $id, int $userId): array|false {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ? AND id_utilisateur = ? LIMIT 1");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch();
    }

    public static function create(array $data): bool {
        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO transactions (montant, type, description, date, id_utilisateur, id_categorie, id_budget)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $data['montant'],
            $data['type'],
            $data['description'],
            $data['date'],
            $data['id_utilisateur'],
            $data['id_categorie'] ?: null,
            $data['id_budget'] ?: null
        ]);
    }

    public static function update(int $id, int $userId, array $data): bool {
        $db = getDB();
        $stmt = $db->prepare(
            "UPDATE transactions SET montant = ?, type = ?, description = ?, date = ?, id_categorie = ?, id_budget = ?
             WHERE id = ? AND id_utilisateur = ?"
        );
        return $stmt->execute([
            $data['montant'],
            $data['type'],
            $data['description'],
            $data['date'],
            $data['id_categorie'] ?: null,
            $data['id_budget'] ?: null,
            $id,
            $userId
        ]);
    }

    public static function delete(int $id, int $userId): bool {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM transactions WHERE id = ? AND id_utilisateur = ?");
        return $stmt->execute([$id, $userId]);
    }

    public static function getRecent(int $userId, int $limit = 5): array {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT t.*, c.nom AS categorie_nom, b.nom AS budget_nom
             FROM transactions t
             LEFT JOIN categories c ON t.id_categorie = c.id
             LEFT JOIN budgets b ON t.id_budget = b.id
             WHERE t.id_utilisateur = ?
             ORDER BY t.date DESC, t.id DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getTotalsByCategory(int $userId, string $type = 'depense'): array {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(t.montant), 0) AS total, COALESCE(c.nom, 'Sans catégorie') AS categorie
             FROM transactions t
             LEFT JOIN categories c ON t.id_categorie = c.id
             WHERE t.id_utilisateur = ? AND t.type = ?
             GROUP BY t.id_categorie
             ORDER BY total DESC
             LIMIT 6"
        );
        $stmt->execute([$userId, $type]);
        return $stmt->fetchAll();
    }

    public static function getTotalByType(int $userId, string $type): float {
        $db = getDB();
        $stmt = $db->prepare("SELECT COALESCE(SUM(montant), 0) FROM transactions WHERE id_utilisateur = ? AND type = ?");
        $stmt->execute([$userId, $type]);
        return (float)$stmt->fetchColumn();
    }

    public static function getBalance(int $userId): float {
        return self::getTotalByType($userId, 'revenu') - self::getTotalByType($userId, 'depense');
    }
}
