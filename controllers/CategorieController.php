<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../utils/validation.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../utils/auth.php';

class CategorieController {
    public static function create(): void {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $nom = cleanInput($_POST['nom'] ?? '');
        $couleur = cleanInput($_POST['couleur'] ?? '#0d9488');
        $type = cleanInput($_POST['type'] ?? 'depense');

        if (!$nom || !in_array($type, ['revenu', 'depense'], true)) {
            $_SESSION['error'] = 'Nom de catégorie ou type invalide.';
            redirect(BASE_URL . 'views/categories.php');
        }

        $ok = Categorie::create($userId, ['nom' => $nom, 'couleur' => $couleur, 'type' => $type]);
        $_SESSION['success'] = $ok ? 'Catégorie ajoutée.' : 'Impossible d’ajouter la catégorie.';
        redirect(BASE_URL . 'views/categories.php');
    }

    public static function update(): void {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $id = isset($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : 0;
        $nom = cleanInput($_POST['nom'] ?? '');
        $couleur = cleanInput($_POST['couleur'] ?? '#0d9488');
        $type = cleanInput($_POST['type'] ?? 'depense');

        if (!$id || !$nom || !in_array($type, ['revenu', 'depense'], true)) {
            $_SESSION['error'] = 'Données de catégorie invalides.';
            redirect(BASE_URL . 'views/categories.php');
        }

        $ok = Categorie::update($id, $userId, ['nom' => $nom, 'couleur' => $couleur, 'type' => $type]);
        $_SESSION['success'] = $ok ? 'Catégorie mise à jour.' : 'Impossible de mettre à jour la catégorie.';
        redirect(BASE_URL . 'views/categories.php');
    }

    public static function delete(): void {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $id = isset($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : 0;

        if (!$id) {
            $_SESSION['error'] = 'Catégorie invalide.';
            redirect(BASE_URL . 'views/categories.php');
        }

        $ok = Categorie::delete($id, $userId);
        $_SESSION['success'] = $ok ? 'Catégorie supprimée.' : 'Impossible de supprimer la catégorie.';
        redirect(BASE_URL . 'views/categories.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_category') CategorieController::create();
    if ($action === 'update_category') CategorieController::update();
    if ($action === 'delete_category') CategorieController::delete();
}