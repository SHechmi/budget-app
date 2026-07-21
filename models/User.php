<?php
require_once __DIR__ . '/../config/database.php';

class User {

    // Créer un compte
    public static function create(array $data): bool {
        $db = getDB();
        $countStmt = $db->query("SELECT COUNT(*) FROM utilisateurs");
        $isFirstUser = ((int)$countStmt->fetchColumn() === 0);

        $role = $isFirstUser ? 'admin' : 'user';
        $actif = $isFirstUser ? 1 : 0;

        $stmt = $db->prepare("
            INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, actif)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['nom'],
            $data['prenom'],
            $data['email'],
            password_hash($data['mot_de_passe'], PASSWORD_BCRYPT),
            $role,
            $actif
        ]);
    }

    // Trouver par email
    public static function findByEmail(string $email): array|false {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    // Trouver par ID
    public static function findById(int $id): array|false {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Mettre à jour le profil
    public static function updateProfil(int $id, array $data): bool {
        $db = getDB();
        $stmt = $db->prepare("UPDATE utilisateurs SET nom=?, prenom=?, email=? WHERE id=?");
        return $stmt->execute([$data['nom'], $data['prenom'], $data['email'], $id]);
    }

    // Changer le mot de passe
    public static function updatePassword(int $id, string $newPassword): bool {
        $db = getDB();
        $stmt = $db->prepare("UPDATE utilisateurs SET mot_de_passe=? WHERE id=?");
        return $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $id]);
    }

    // Vérifier si email existe déjà (ajouté)
    public static function emailExists(string $email, int $excludeId = 0): bool {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $stmt->execute([$email, $excludeId]);
        return (bool)$stmt->fetch();
    }
}
?>