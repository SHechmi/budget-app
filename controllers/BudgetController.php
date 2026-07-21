<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../utils/validation.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../utils/auth.php';

class BudgetController {
    public static function create(): void {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $nom = cleanInput($_POST['nom'] ?? '');
        $periode = cleanInput($_POST['periode'] ?? 'mensuel');
        $type = cleanInput($_POST['type'] ?? 'individuel');
        $dateDebut = $_POST['date_debut'] ?? '';
        $dateFin = $_POST['date_fin'] ?? '';
        $plafondGlobal = $_POST['plafond_global'] ?? 0;

        if (!$nom || !$dateDebut || !$dateFin || !validDate($dateDebut) || !validDate($dateFin)) {
            $_SESSION['error'] = 'Veuillez remplir tous les champs du budget.';
            redirect(BASE_URL . 'views/budgets.php');
        }
        if (strtotime($dateDebut) > strtotime($dateFin)) {
            $_SESSION['error'] = 'La date de début doit être antérieure à la date de fin.';
            redirect(BASE_URL . 'views/budgets.php');
        }
        if (!is_numeric($plafondGlobal) || (float)$plafondGlobal <= 0) {
            $_SESSION['error'] = 'Le plafond doit être un montant positif.';
            redirect(BASE_URL . 'views/budgets.php');
        }

        $ok = Budget::create($userId, [
            'nom' => $nom,
            'type' => $type,
            'periode' => $periode,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'plafond_global' => $plafondGlobal
        ]);

        $_SESSION['success'] = $ok ? 'Budget créé.' : 'Impossible de créer le budget.';
        redirect(BASE_URL . 'views/budgets.php');
    }

    public static function update(): void {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $id = isset($_POST['budget_id']) ? (int)$_POST['budget_id'] : 0;
        $nom = cleanInput($_POST['nom'] ?? '');
        $periode = cleanInput($_POST['periode'] ?? 'mensuel');
        $type = cleanInput($_POST['type'] ?? 'individuel');
        $dateDebut = $_POST['date_debut'] ?? '';
        $dateFin = $_POST['date_fin'] ?? '';
        $plafondGlobal = $_POST['plafond_global'] ?? 0;

        if (!$id || !$nom || !$dateDebut || !$dateFin || !validDate($dateDebut) || !validDate($dateFin)) {
            $_SESSION['error'] = 'Veuillez remplir tous les champs du budget.';
            redirect(BASE_URL . 'views/budgets.php');
        }
        if (strtotime($dateDebut) > strtotime($dateFin)) {
            $_SESSION['error'] = 'La date de début doit être antérieure à la date de fin.';
            redirect(BASE_URL . 'views/budgets.php');
        }
        if (!is_numeric($plafondGlobal) || (float)$plafondGlobal <= 0) {
            $_SESSION['error'] = 'Le plafond doit être un montant positif.';
            redirect(BASE_URL . 'views/budgets.php');
        }

        $ok = Budget::update($id, $userId, [
            'nom' => $nom,
            'type' => $type,
            'periode' => $periode,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'plafond_global' => $plafondGlobal
        ]);

        $_SESSION['success'] = $ok ? 'Budget mis à jour.' : 'Impossible de mettre à jour le budget.';
        redirect(BASE_URL . 'views/budgets.php');
    }

    public static function delete(): void {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $id = isset($_POST['budget_id']) ? (int)$_POST['budget_id'] : 0;

        if (!$id) {
            $_SESSION['error'] = 'Budget invalide.';
            redirect(BASE_URL . 'views/budgets.php');
        }

        $ok = Budget::delete($id, $userId);
        $_SESSION['success'] = $ok ? 'Budget supprimé.' : 'Impossible de supprimer le budget.';
        redirect(BASE_URL . 'views/budgets.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_budget') BudgetController::create();
    if ($action === 'update_budget') BudgetController::update();
    if ($action === 'delete_budget') BudgetController::delete();
}