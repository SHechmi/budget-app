<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../controllers/AlerteController.php';
require_once __DIR__ . '/../utils/validation.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../utils/auth.php';

class TransactionController {
    public static function create(): void {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $montant = cleanInput($_POST['montant'] ?? '');
        $type = cleanInput($_POST['type'] ?? '');
        $date = $_POST['date'] ?? '';
        $description = cleanInput($_POST['description'] ?? '');
        $categorieId = isset($_POST['categorie']) ? (int)$_POST['categorie'] : 0;
        $budgetId = isset($_POST['budget']) ? (int)$_POST['budget'] : 0;

        if (!$montant || !$type || !$date) {
            $_SESSION['error'] = 'Le montant, le type et la date sont obligatoires.';
            redirect(BASE_URL . 'views/transactions.php');
        }
        if (!validMontant($montant)) {
            $_SESSION['error'] = 'Montant invalide.';
            redirect(BASE_URL . 'views/transactions.php');
        }
        if (!validDate($date)) {
            $_SESSION['error'] = 'Date invalide.';
            redirect(BASE_URL . 'views/transactions.php');
        }

        $ok = Transaction::create([
            'montant' => $montant,
            'type' => $type,
            'description' => $description,
            'date' => $date,
            'id_utilisateur' => $userId,
            'id_categorie' => $categorieId,
            'id_budget' => $budgetId
        ]);

        if ($ok) {
            AlerteController::checkBudgetAlertes($userId);
            $_SESSION['success'] = 'Transaction ajoutée avec succès.';
        } else {
            $_SESSION['error'] = 'Impossible d’ajouter la transaction.';
        }
        redirect(BASE_URL . 'views/transactions.php');
    }

    public static function update(): void {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $id = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
        $montant = cleanInput($_POST['montant'] ?? '');
        $type = cleanInput($_POST['type'] ?? '');
        $date = $_POST['date'] ?? '';
        $description = cleanInput($_POST['description'] ?? '');
        $categorieId = isset($_POST['categorie']) ? (int)$_POST['categorie'] : 0;
        $budgetId = isset($_POST['budget']) ? (int)$_POST['budget'] : 0;

        if (!$id || !$montant || !$type || !$date) {
            $_SESSION['error'] = 'Tous les champs obligatoires doivent être remplis.';
            redirect(BASE_URL . 'views/transactions.php');
        }
        if (!validMontant($montant)) {
            $_SESSION['error'] = 'Montant invalide.';
            redirect(BASE_URL . 'views/transactions.php');
        }
        if (!validDate($date)) {
            $_SESSION['error'] = 'Date invalide.';
            redirect(BASE_URL . 'views/transactions.php');
        }

        $ok = Transaction::update($id, $userId, [
            'montant' => $montant,
            'type' => $type,
            'description' => $description,
            'date' => $date,
            'id_categorie' => $categorieId,
            'id_budget' => $budgetId
        ]);

        if ($ok) {
            AlerteController::checkBudgetAlertes($userId);
            $_SESSION['success'] = 'Transaction mise à jour.';
        } else {
            $_SESSION['error'] = 'Impossible de mettre à jour la transaction.';
        }
        redirect(BASE_URL . 'views/transactions.php');
    }

    public static function delete(): void {
        requireLogin();
        $userId = $_SESSION['user_id'];
        $id = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;

        if (!$id) {
            $_SESSION['error'] = 'Transaction invalide.';
            redirect(BASE_URL . 'views/transactions.php');
        }

        $ok = Transaction::delete($id, $userId);
        if ($ok) {
            AlerteController::checkBudgetAlertes($userId);
            $_SESSION['success'] = 'Transaction supprimée.';
        } else {
            $_SESSION['error'] = 'Impossible de supprimer la transaction.';
        }
        redirect(BASE_URL . 'views/transactions.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_transaction') TransactionController::create();
    if ($action === 'update_transaction') TransactionController::update();
    if ($action === 'delete_transaction') TransactionController::delete();
}