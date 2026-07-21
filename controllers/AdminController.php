<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/helpers.php';

class AdminController {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function activerCompte($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE utilisateurs SET actif = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            return ['success' => true, 'message' => 'Compte activé avec succès'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de l\'activation'];
        }
    }

    public function desactiverCompte($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE utilisateurs SET actif = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            return ['success' => true, 'message' => 'Compte désactivé avec succès'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la désactivation'];
        }
    }

    public function supprimerCompte($userId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
            $stmt->execute([$userId]);
            return ['success' => true, 'message' => 'Compte supprimé avec succès'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        }
    }

    public function changerRole($userId, $nouveauRole) {
        try {
            $stmt = $this->pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?");
            $stmt->execute([$nouveauRole, $userId]);
            return ['success' => true, 'message' => 'Rôle modifié avec succès'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors du changement de rôle'];
        }
    }

    public function getAllUsers() {
        $stmt = $this->pdo->query("SELECT id, nom, prenom, email, role, actif, date_inscription FROM utilisateurs ORDER BY date_inscription DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUsersEnAttente() {
        $stmt = $this->pdo->query("SELECT id, nom, prenom, email, date_inscription FROM utilisateurs WHERE actif = 0 ORDER BY date_inscription ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUsersEnAttente() {
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM utilisateurs WHERE actif = 0");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getGlobalStats() {
        $stats = [];
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM utilisateurs");
        $stats['totalUsers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM utilisateurs WHERE actif = 1");
        $stats['activeUsers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM transactions");
        $stats['totalTransactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM budgets");
        $stats['totalBudgets'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM alertes");
        $stats['totalAlertes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(montant), 0) as total FROM transactions");
        $stats['totalMontant'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total revenus
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(montant), 0) as total FROM transactions WHERE type = 'revenu'");
        $stats['totalRevenus'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total dépenses
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(montant), 0) as total FROM transactions WHERE type = 'depense'");
        $stats['totalDepenses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM utilisateurs WHERE MONTH(date_inscription) = MONTH(CURDATE()) AND YEAR(date_inscription) = YEAR(CURDATE())");
        $stats['newUsersThisMonth'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        return $stats;
    }

    /**
     * Récupère les statistiques mensuelles des transactions
     */
    public function getMonthlyStats(): array {
        // 6 derniers mois
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE_FORMAT(date, '%b') as mois,
                DATE_FORMAT(date, '%Y-%m') as mois_key,
                SUM(CASE WHEN type = 'revenu' THEN montant ELSE 0 END) as revenus,
                SUM(CASE WHEN type = 'depense' THEN montant ELSE 0 END) as depenses
            FROM transactions
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY DATE_FORMAT(date, '%Y-%m'), DATE_FORMAT(date, '%b')
            ORDER BY MIN(date) ASC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'months' => array_column($results, 'mois'),
            'revenus' => array_column($results, 'revenus'),
            'depenses' => array_column($results, 'depenses')
        ];
    }

    /**
     * Récupère la croissance des utilisateurs
     */
    public function getUserGrowth(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE_FORMAT(date_inscription, '%b') as mois,
                DATE_FORMAT(date_inscription, '%Y-%m') as mois_key,
                COUNT(*) as total
            FROM utilisateurs
            WHERE date_inscription >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
            GROUP BY DATE_FORMAT(date_inscription, '%Y-%m'), DATE_FORMAT(date_inscription, '%b')
            ORDER BY MIN(date_inscription) ASC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'months' => array_column($results, 'mois'),
            'counts' => array_column($results, 'total')
        ];
    }

    /**
     * Récupère les statistiques par catégorie
     */
    public function getCategoryStats(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                COALESCE(c.nom, 'Sans catégorie') as nom,
                SUM(t.montant) as total
            FROM transactions t
            LEFT JOIN categories c ON t.id_categorie = c.id
            WHERE t.type = 'depense'
            GROUP BY t.id_categorie
            ORDER BY total DESC
            LIMIT 5
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère l'utilisation des budgets
     */
    public function getBudgetUtilization(): array {
        $stmt = $this->pdo->prepare("
            SELECT 
                b.nom,
                COALESCE(SUM(t.montant), 0) as depenses,
                b.plafond_global
            FROM budgets b
            LEFT JOIN transactions t ON t.id_budget = b.id AND t.type = 'depense'
            WHERE b.date_debut <= CURDATE() AND b.date_fin >= CURDATE()
            GROUP BY b.id
            LIMIT 5
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'names' => array_column($results, 'nom'),
            'percentages' => array_map(function($item) {
                return $item['plafond_global'] > 0 ? round(($item['depenses'] / $item['plafond_global']) * 100, 1) : 0;
            }, $results)
        ];
    }
}

// Traitement des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    requireAdmin();
    $admin = new AdminController(getDB());
    $action = $_POST['action'] ?? '';
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if ($userId > 0) {
        switch ($action) {
            case 'activer_compte':
                $result = $admin->activerCompte($userId);
                $_SESSION[$result['success'] ? 'success' : 'error'] = $result['message'];
                break;
            case 'desactiver_compte':
                $result = $admin->desactiverCompte($userId);
                $_SESSION[$result['success'] ? 'success' : 'error'] = $result['message'];
                break;
            case 'supprimer_compte':
                $result = $admin->supprimerCompte($userId);
                $_SESSION[$result['success'] ? 'success' : 'error'] = $result['message'];
                break;
            case 'changer_role':
                $role = cleanInput($_POST['role'] ?? 'user');
                $result = $admin->changerRole($userId, in_array($role, ['admin', 'user'], true) ? $role : 'user');
                $_SESSION[$result['success'] ? 'success' : 'error'] = $result['message'];
                break;
        }
    }
    
    redirect(BASE_URL . 'views/admin.php');
}
?>