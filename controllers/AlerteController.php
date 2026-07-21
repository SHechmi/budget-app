<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Alerte.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/helpers.php';

class AlerteController {

    // Vérifier et créer alertes (appelé après chaque transaction)
    public static function checkBudgetAlertes(int $userId): void {
        $db = getDB();

        // Récupère budgets actifs
        $stmt = $db->prepare("\n            SELECT DISTINCT b.* FROM budgets b
            LEFT JOIN budgets_membres bm ON bm.id_budget = b.id
            WHERE (b.id_createur = ? OR bm.id_utilisateur = ?)
            AND b.date_debut = CURDATE()
        ");
        $stmt->execute([$userId, $userId]);
        $budgets = $stmt->fetchAll();

        foreach ($budgets as $budget) {
            // Total dépenses du budget sur la période, tous membres confondus
            $stmt2 = $db->prepare("
                SELECT COALESCE(SUM(montant), 0) FROM transactions
                WHERE id_budget = ? AND type = 'depense'
                AND date BETWEEN ? AND ?
            ");
            $stmt2->execute([$budget['id'], $budget['date_debut'], $budget['date_fin']]);
            $totalDepenses = (float)$stmt2->fetchColumn();
            
            $plafond = (float)$budget['plafond_global'];
            $pct = $plafond > 0 ? ($totalDepenses / $plafond) * 100 : 0;

            $recipientStmt = $db->prepare("
                SELECT DISTINCT id_utilisateur
                FROM budgets_membres
                WHERE id_budget = ?
                UNION
                SELECT id_createur AS id_utilisateur
                FROM budgets
                WHERE id = ?
            ");
            $recipientStmt->execute([$budget['id'], $budget['id']]);
            $recipientIds = $recipientStmt->fetchAll(PDO::FETCH_COLUMN);

            // Alerte seuil à 80%
            if ($pct >= 80 && $pct < 100) {
                foreach ($recipientIds as $recipientId) {
                    $recipientId = (int)$recipientId;
                    if (!Alerte::exists($recipientId, $budget['id'], 'seuil')) {
                        Alerte::create(
                            $recipientId, 'seuil',
                            "Attention : vous avez consommé " . round($pct, 1) . "% de votre budget \"" . htmlspecialchars($budget['nom']) . "\".",
                            $budget['id']
                        );
                    }
                }
            }

            // Alerte dépassement à 100%
            if ($pct >= 100) {
                foreach ($recipientIds as $recipientId) {
                    $recipientId = (int)$recipientId;
                    if (!Alerte::exists($recipientId, $budget['id'], 'depassement')) {
                        Alerte::create(
                            $recipientId, 'depassement',
                            "⚠️ Dépassement : votre budget \"" . htmlspecialchars($budget['nom']) . "\" a été dépassé (dépenses : " . number_format($totalDepenses, 2) . " €).",
                            $budget['id']
                        );
                    }
                }
            }
        }
    }

    // Marquer toutes les alertes comme lues
    public static function markAllRead(): void {
        requireLogin();
        Alerte::markAllRead($_SESSION['user_id']);
        redirect(BASE_URL . 'views/alertes.php');
    }

    // Marquer une alerte spécifique comme lue
    public static function markRead(): void {
        requireLogin();
        $id = (int)($_POST['alerte_id'] ?? 0);
        if ($id > 0) {
            Alerte::markRead($id, $_SESSION['user_id']);
        }
        redirect(BASE_URL . 'views/alertes.php');
    }

    // Supprimer une alerte
    public static function deleteAlerte(): void {
        requireLogin();
        $id = (int)($_POST['alerte_id'] ?? 0);
        if ($id > 0) {
            Alerte::delete($id, $_SESSION['user_id']);
        }
        redirect(BASE_URL . 'views/alertes.php');
    }
}

// Dispatch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_all_read')    AlerteController::markAllRead();
    if ($action === 'mark_read')        AlerteController::markRead();
    if ($action === 'delete_alerte')    AlerteController::deleteAlerte();
}
?>