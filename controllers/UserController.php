<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/validation.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../utils/auth.php';

class UserController {

    // Modifier profil
    public static function updateProfil(): void {
        requireLogin();
        $id     = $_SESSION['user_id'];
        $nom    = cleanInput($_POST['nom'] ?? '');
        $prenom = cleanInput($_POST['prenom'] ?? '');
        $email  = cleanInput($_POST['email'] ?? '');

        if (!$nom || !$prenom || !$email) {
            $_SESSION['error'] = "Tous les champs sont requis.";
            redirect(BASE_URL . 'views/profile.php');
        }
        if (!validEmail($email)) {
            $_SESSION['error'] = "Email invalide.";
            redirect(BASE_URL . 'views/profile.php');
        }
        if (User::emailExists($email, $id)) {
            $_SESSION['error'] = "Cet email est déjà utilisé.";
            redirect(BASE_URL . 'views/profile.php');
        }

        $ok = User::updateProfil($id, ['nom' => $nom, 'prenom' => $prenom, 'email' => $email]);
        if ($ok) {
            // Mise à jour session
            $_SESSION['nom']    = $nom;
            $_SESSION['prenom'] = $prenom;
            $_SESSION['email']  = $email;
            $_SESSION['success'] = "Profil mis à jour.";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour.";
        }
        redirect(BASE_URL . 'views/profile.php');
    }

    // Changer mot de passe
    public static function changePassword(): void {
        requireLogin();
        $id      = $_SESSION['user_id'];
        $ancien  = $_POST['ancien_mdp'] ?? '';
        $nouveau = $_POST['nouveau_mdp'] ?? '';
        $confirm = $_POST['confirm_mdp'] ?? '';

        $user = User::findById($id);
        if (!$user || !password_verify($ancien, $user['mot_de_passe'])) {
            $_SESSION['error'] = "Ancien mot de passe incorrect.";
            redirect(BASE_URL . 'views/profile.php');
        }
        if (!validPassword($nouveau)) {
            $_SESSION['error'] = "Nouveau mot de passe trop court (6 chars min).";
            redirect(BASE_URL . 'views/profile.php');
        }
        if ($nouveau !== $confirm) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            redirect(BASE_URL . 'views/profile.php');
        }

        $ok = User::updatePassword($id, $nouveau);
        if ($ok) {
            $_SESSION['success'] = "Mot de passe modifié.";
        } else {
            $_SESSION['error'] = "Erreur lors du changement.";
        }
        redirect(BASE_URL . 'views/profile.php');
    }

    /**
     * Récupère les statistiques globales de l'utilisateur
     */
    public static function getUserStats(int $userId): array {
        $db = getDB();
        $stats = [];
        
        // Total des transactions
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM transactions WHERE id_utilisateur = ?");
        $stmt->execute([$userId]);
        $stats['totalTransactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total des revenus
        $stmt = $db->prepare("SELECT COALESCE(SUM(montant), 0) as total FROM transactions WHERE id_utilisateur = ? AND type = 'revenu'");
        $stmt->execute([$userId]);
        $stats['totalRevenus'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total des dépenses
        $stmt = $db->prepare("SELECT COALESCE(SUM(montant), 0) as total FROM transactions WHERE id_utilisateur = ? AND type = 'depense'");
        $stmt->execute([$userId]);
        $stats['totalDepenses'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Solde
        $stats['solde'] = $stats['totalRevenus'] - $stats['totalDepenses'];
        
        // Nombre de budgets
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM budgets WHERE id_createur = ? OR id IN (SELECT id_budget FROM budgets_membres WHERE id_utilisateur = ?)");
        $stmt->execute([$userId, $userId]);
        $stats['totalBudgets'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Budgets actifs
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM budgets WHERE (id_createur = ? OR id IN (SELECT id_budget FROM budgets_membres WHERE id_utilisateur = ?)) AND date_debut <= CURDATE() AND date_fin >= CURDATE()");
        $stmt->execute([$userId, $userId]);
        $stats['budgetsActifs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Alertes non lues
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM alertes WHERE user_id = ? AND lu = 0");
        $stmt->execute([$userId]);
        $stats['alertesNonLues'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Taux d'épargne
        $stats['tauxEpargne'] = $stats['totalRevenus'] > 0 ? round(($stats['solde'] / $stats['totalRevenus']) * 100, 1) : 0;
        
        // Dépense moyenne par transaction
        $stmt = $db->prepare("SELECT COALESCE(AVG(montant), 0) as moyenne FROM transactions WHERE id_utilisateur = ? AND type = 'depense'");
        $stmt->execute([$userId]);
        $stats['depenseMoyenne'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['moyenne'];
        
        return $stats;
    }

    /**
     * Récupère les statistiques mensuelles des transactions de l'utilisateur
     */
    public static function getMonthlyStats(int $userId): array {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                DATE_FORMAT(date, '%b') as mois,
                DATE_FORMAT(date, '%Y-%m') as mois_key,
                SUM(CASE WHEN type = 'revenu' THEN montant ELSE 0 END) as revenus,
                SUM(CASE WHEN type = 'depense' THEN montant ELSE 0 END) as depenses
            FROM transactions
            WHERE id_utilisateur = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY DATE_FORMAT(date, '%Y-%m'), DATE_FORMAT(date, '%b')
            ORDER BY MIN(date) ASC
        ");
        $stmt->execute([$userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'months' => array_column($results, 'mois'),
            'revenus' => array_column($results, 'revenus'),
            'depenses' => array_column($results, 'depenses')
        ];
    }

    /**
     * Récupère les dépenses par catégorie pour l'utilisateur
     */
    public static function getCategoryStats(int $userId): array {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                COALESCE(c.nom, 'Sans catégorie') as nom,
                SUM(t.montant) as total,
                COUNT(t.id) as nombre
            FROM transactions t
            LEFT JOIN categories c ON t.id_categorie = c.id
            WHERE t.id_utilisateur = ? AND t.type = 'depense'
            GROUP BY t.id_categorie
            ORDER BY total DESC
            LIMIT 6
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les revenus par catégorie pour l'utilisateur
     */
    public static function getRevenusByCategory(int $userId): array {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                COALESCE(c.nom, 'Sans catégorie') as nom,
                SUM(t.montant) as total
            FROM transactions t
            LEFT JOIN categories c ON t.id_categorie = c.id
            WHERE t.id_utilisateur = ? AND t.type = 'revenu'
            GROUP BY t.id_categorie
            ORDER BY total DESC
            LIMIT 6
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère l'évolution mensuelle du solde
     */
    public static function getSoldeEvolution(int $userId): array {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                DATE_FORMAT(date, '%b') as mois,
                DATE_FORMAT(date, '%Y-%m') as mois_key,
                SUM(CASE WHEN type = 'revenu' THEN montant ELSE 0 END) as revenus,
                SUM(CASE WHEN type = 'depense' THEN montant ELSE 0 END) as depenses
            FROM transactions
            WHERE id_utilisateur = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY DATE_FORMAT(date, '%Y-%m'), DATE_FORMAT(date, '%b')
            ORDER BY MIN(date) ASC
        ");
        $stmt->execute([$userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $solde = 0;
        $soldes = [];
        $months = [];
        
        foreach ($results as $row) {
            $solde += ($row['revenus'] - $row['depenses']);
            $soldes[] = $solde;
            $months[] = $row['mois'];
        }
        
        return [
            'months' => $months,
            'soldes' => $soldes
        ];
    }

    /**
     * Récupère les budgets avec leur utilisation
     */
    public static function getBudgetsUtilization(int $userId): array {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                b.id,
                b.nom,
                b.type,
                b.periode,
                b.plafond_global,
                COALESCE(SUM(t.montant), 0) as depenses,
                (b.plafond_global - COALESCE(SUM(t.montant), 0)) as reste
            FROM budgets b
            LEFT JOIN transactions t ON t.id_budget = b.id AND t.type = 'depense'
            WHERE (b.id_createur = ? OR b.id IN (SELECT id_budget FROM budgets_membres WHERE id_utilisateur = ?))
            AND b.date_debut <= CURDATE() AND b.date_fin >= CURDATE()
            GROUP BY b.id
            ORDER BY (COALESCE(SUM(t.montant), 0) / b.plafond_global) DESC
            LIMIT 5
        ");
        $stmt->execute([$userId, $userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($budget) {
            $budget['pourcentage'] = $budget['plafond_global'] > 0 ? round(($budget['depenses'] / $budget['plafond_global']) * 100, 1) : 0;
            $budget['couleur'] = $budget['pourcentage'] < 70 ? '#48bb78' : ($budget['pourcentage'] < 90 ? '#ed8936' : '#f56565');
            return $budget;
        }, $results);
    }

    /**
     * Récupère les dernières transactions avec détails
     */
    public static function getRecentTransactions(int $userId, int $limit = 10): array {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                t.*,
                c.nom as categorie_nom,
                c.couleur as categorie_couleur,
                b.nom as budget_nom
            FROM transactions t
            LEFT JOIN categories c ON t.id_categorie = c.id
            LEFT JOIN budgets b ON t.id_budget = b.id
            WHERE t.id_utilisateur = ?
            ORDER BY t.date DESC, t.id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les top dépenses par montant
     */
    public static function getTopDepenses(int $userId, int $limit = 5): array {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                t.*,
                c.nom as categorie_nom
            FROM transactions t
            LEFT JOIN categories c ON t.id_categorie = c.id
            WHERE t.id_utilisateur = ? AND t.type = 'depense'
            ORDER BY t.montant DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les statistiques journalières pour les 7 derniers jours
     */
    public static function getWeeklyStats(int $userId): array {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                DATE_FORMAT(date, '%a') as jour,
                SUM(CASE WHEN type = 'depense' THEN montant ELSE 0 END) as depenses,
                SUM(CASE WHEN type = 'revenu' THEN montant ELSE 0 END) as revenus
            FROM transactions
            WHERE id_utilisateur = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(date)
            ORDER BY date ASC
        ");
        $stmt->execute([$userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $jours = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $depenses = array_fill(0, 7, 0);
        $revenus = array_fill(0, 7, 0);
        
        foreach ($results as $row) {
            $index = array_search($row['jour'], $jours);
            if ($index !== false) {
                $depenses[$index] = (float)$row['depenses'];
                $revenus[$index] = (float)$row['revenus'];
            }
        }
        
        return [
            'jours' => $jours,
            'depenses' => $depenses,
            'revenus' => $revenus
        ];
    }

    /**
     * Récupère le résumé complet pour le dashboard utilisateur
     */
    public static function getDashboardData(int $userId): array {
        return [
            'stats' => self::getUserStats($userId),
            'monthlyStats' => self::getMonthlyStats($userId),
            'categoryStats' => self::getCategoryStats($userId),
            'budgets' => self::getBudgetsUtilization($userId),
            'recentTransactions' => self::getRecentTransactions($userId, 5),
            'weeklyStats' => self::getWeeklyStats($userId),
            'topDepenses' => self::getTopDepenses($userId, 3)
        ];
    }
}

// Dispatch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profil')   UserController::updateProfil();
    if ($action === 'change_password') UserController::changePassword();
}

// Pour les requêtes AJAX (statistiques)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    requireLogin();
    $userId = $_SESSION['user_id'];
    $action = $_GET['action'];
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'get_stats':
            echo json_encode(UserController::getUserStats($userId));
            break;
        case 'get_monthly':
            echo json_encode(UserController::getMonthlyStats($userId));
            break;
        case 'get_categories':
            echo json_encode(UserController::getCategoryStats($userId));
            break;
        case 'get_budgets':
            echo json_encode(UserController::getBudgetsUtilization($userId));
            break;
        case 'get_weekly':
            echo json_encode(UserController::getWeeklyStats($userId));
            break;
        case 'get_dashboard':
            echo json_encode(UserController::getDashboardData($userId));
            break;
        default:
            echo json_encode(['error' => 'Action non valide']);
    }
    exit();
}
?>