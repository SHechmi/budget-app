<?php
require_once __DIR__ . '/../config/database.php';

class Alerte {

    // Récupérer les alertes d'un utilisateur
    public static function getByUser(int $userId): array {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT * FROM alertes WHERE id_utilisateur = ? ORDER BY date_alerte DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Compter alertes non lues
    public static function countUnread(int $userId): int {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM alertes WHERE id_utilisateur = ? AND lu = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    // Marquer toutes comme lues
    public static function markAllRead(int $userId): bool {
        $db = getDB();
        $stmt = $db->prepare("UPDATE alertes SET lu = 1 WHERE id_utilisateur = ?");
        return $stmt->execute([$userId]);
    }

    // Marquer une alerte spécifique comme lue
    public static function markRead(int $id, int $userId): bool {
        $db = getDB();
        $stmt = $db->prepare("UPDATE alertes SET lu = 1 WHERE id = ? AND id_utilisateur = ?");
        return $stmt->execute([$id, $userId]);
    }

    // Créer une alerte
    public static function create(int $userId, string $type, string $message, ?int $budgetId = null): bool {
        $db = getDB();
        
        // Vérifier si une alerte similaire existe déjà aujourd'hui pour éviter les doublons
        if (self::exists($userId, $budgetId, $type)) {
            return false; // Alerte déjà envoyée aujourd'hui
        }
        
        $stmt = $db->prepare(
            "INSERT INTO alertes (id_utilisateur, id_budget, type, message, date_alerte, lu) 
             VALUES (?, ?, ?, ?, NOW(), 0)"
        );
        return $stmt->execute([$userId, $budgetId, $type, $message]);
    }

    // Supprimer une alerte
    public static function delete(int $id, int $userId): bool {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM alertes WHERE id = ? AND id_utilisateur = ?");
        return $stmt->execute([$id, $userId]);
    }

    // Vérifier si alerte déjà envoyée aujourd'hui (éviter les doublons)
    public static function exists(int $userId, ?int $budgetId, string $type): bool {
        $db = getDB();
        
        if ($budgetId !== null) {
            $stmt = $db->prepare(
                "SELECT id FROM alertes WHERE id_utilisateur = ? AND id_budget = ? AND type = ? AND DATE(date_alerte) = CURDATE()"
            );
            $stmt->execute([$userId, $budgetId, $type]);
        } else {
            $stmt = $db->prepare(
                "SELECT id FROM alertes WHERE id_utilisateur = ? AND id_budget IS NULL AND type = ? AND DATE(date_alerte) = CURDATE()"
            );
            $stmt->execute([$userId, $type]);
        }
        
        return (bool)$stmt->fetch();
    }

    // Récupérer les alertes non lues
    public static function getUnread(int $userId): array {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT * FROM alertes WHERE id_utilisateur = ? AND lu = 0 ORDER BY date_alerte DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Récupérer les alertes par type
    public static function getByType(int $userId, string $type): array {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT * FROM alertes WHERE id_utilisateur = ? AND type = ? ORDER BY date_alerte DESC"
        );
        $stmt->execute([$userId, $type]);
        return $stmt->fetchAll();
    }

    // Supprimer les alertes plus anciennes qu'une certaine date
    public static function deleteOld(int $days = 30): int {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM alertes WHERE date_alerte < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }
}
?>