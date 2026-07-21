<?php
require_once __DIR__ . '/../config/database.php';

class Categorie {
    
    // Initialiser les catégories par défaut (système)
    public static function seedDefaults(): void {
        $db = getDB();
        $count = (int)$db->query("SELECT COUNT(*) FROM categories WHERE id_utilisateur IS NULL")->fetchColumn();
        if ($count > 0) {
            return;
        }

        $defaults = [
            ['nom' => 'Salaire', 'couleur' => '#16a34a', 'type' => 'revenu'],
            ['nom' => 'Freelance', 'couleur' => '#0ea5e9', 'type' => 'revenu'],
            ['nom' => 'Investissements', 'couleur' => '#14b8a6', 'type' => 'revenu'],
            ['nom' => 'Cadeaux', 'couleur' => '#ec4899', 'type' => 'revenu'],
            ['nom' => 'Loisirs', 'couleur' => '#f59e0b', 'type' => 'depense'],
            ['nom' => 'Alimentation', 'couleur' => '#ef4444', 'type' => 'depense'],
            ['nom' => 'Transport', 'couleur' => '#8b5cf6', 'type' => 'depense'],
            ['nom' => 'Logement', 'couleur' => '#f97316', 'type' => 'depense'],
            ['nom' => 'Santé', 'couleur' => '#0f766e', 'type' => 'depense'],
            ['nom' => 'Shopping', 'couleur' => '#ec4899', 'type' => 'depense'],
            ['nom' => 'Études', 'couleur' => '#06b6d4', 'type' => 'depense'],
            ['nom' => 'Restaurant', 'couleur' => '#f97316', 'type' => 'depense'],
            ['nom' => 'Voyages', 'couleur' => '#14b8a6', 'type' => 'depense']
        ];

        $stmt = $db->prepare("INSERT INTO categories (nom, couleur, type, id_utilisateur) VALUES (?, ?, ?, NULL)");
        foreach ($defaults as $category) {
            $stmt->execute([$category['nom'], $category['couleur'], $category['type']]);
        }
    }

    // Récupérer toutes les catégories d'un utilisateur (système + personnelles)
    public static function getAllByUser(int $userId): array {
        self::seedDefaults();
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT * FROM categories WHERE id_utilisateur IS NULL OR id_utilisateur = ? ORDER BY type DESC, nom ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Récupérer une catégorie par son ID
    public static function findById(int $id, int $userId): array|false {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = ? AND (id_utilisateur = ? OR id_utilisateur IS NULL) LIMIT 1");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch();
    }

    // Créer une catégorie personnalisée
    public static function create(int $userId, array $data): bool {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO categories (nom, couleur, type, id_utilisateur) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $data['nom'],
            $data['couleur'],
            $data['type'],
            $userId
        ]);
    }

    // Mettre à jour une catégorie personnalisée
    public static function update(int $id, int $userId, array $data): bool {
        $db = getDB();
        $stmt = $db->prepare("UPDATE categories SET nom = ?, couleur = ?, type = ? WHERE id = ? AND id_utilisateur = ?");
        return $stmt->execute([
            $data['nom'],
            $data['couleur'],
            $data['type'],
            $id,
            $userId
        ]);
    }

    // Supprimer une catégorie personnalisée
    public static function delete(int $id, int $userId): bool {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ? AND id_utilisateur = ?");
        return $stmt->execute([$id, $userId]);
    }
}
?>