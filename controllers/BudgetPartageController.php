<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/BudgetPartage.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/validation.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../utils/auth.php';

class BudgetPartageController {
    public static function createBudget(): void {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $nom = cleanInput($_POST['nom'] ?? '');
        $periode = cleanInput($_POST['periode'] ?? 'mensuel');
        $dateDebut = $_POST['date_debut'] ?? '';
        $dateFin = $_POST['date_fin'] ?? '';
        $plafondGlobal = $_POST['plafond_global'] ?? 0;

        if (!$nom || !$dateDebut || !$dateFin || !validDate($dateDebut) || !validDate($dateFin)) {
            $_SESSION['error'] = 'Veuillez renseigner tous les champs du budget partagé.';
            redirect(BASE_URL . 'views/budget_partage.php');
        }
        if (strtotime($dateDebut) > strtotime($dateFin)) {
            $_SESSION['error'] = 'La date de début doit être antérieure à la date de fin.';
            redirect(BASE_URL . 'views/budget_partage.php');
        }
        if (!is_numeric($plafondGlobal) || (float)$plafondGlobal <= 0) {
            $_SESSION['error'] = 'Le plafond doit être un montant positif.';
            redirect(BASE_URL . 'views/budget_partage.php');
        }

        $ok = BudgetPartage::createSharedBudget($userId, [
            'nom' => $nom,
            'periode' => $periode,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'plafond_global' => $plafondGlobal
        ]);

        $_SESSION['success'] = $ok ? 'Budget partagé créé.' : 'Impossible de créer le budget partagé.';
        redirect(BASE_URL . 'views/budget_partage.php');
    }

    public static function addMember(): void {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $budgetId = isset($_POST['budget_id']) ? (int)$_POST['budget_id'] : 0;
        $email = cleanInput($_POST['email'] ?? '');

        if (!$budgetId || !$email) {
            $_SESSION['error'] = 'Veuillez sélectionner un budget et un membre.';
            redirect(BASE_URL . 'views/budget_partage.php');
        }

        $member = User::findByEmail($email);
        if (!$member) {
            $_SESSION['error'] = 'Aucun utilisateur trouvé pour cet email.';
            redirect(BASE_URL . 'views/budget_partage.php');
        }

        $ok = BudgetPartage::addMember($budgetId, $member['id']);
        $_SESSION['success'] = $ok ? 'Membre ajouté au budget partagé.' : 'Le membre est déjà présent ou impossible à ajouter.';
        redirect(BASE_URL . 'views/budget_partage.php?budget_id=' . $budgetId);
    }

    public static function addComment(): void {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $transactionId = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
        $contenu = cleanInput($_POST['contenu'] ?? '');

        if (!$transactionId || !$contenu) {
            $_SESSION['error'] = 'Le commentaire ne peut pas être vide.';
            redirect(BASE_URL . 'views/budget_partage.php');
        }

        $ok = BudgetPartage::addComment($transactionId, $userId, $contenu);
        $_SESSION['success'] = $ok ? 'Commentaire ajouté.' : 'Impossible d’ajouter le commentaire.';
        redirect(BASE_URL . 'views/budget_partage.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_shared_budget') BudgetPartageController::createBudget();
    if ($action === 'add_budget_member') BudgetPartageController::addMember();
    if ($action === 'add_budget_comment') BudgetPartageController::addComment();
}