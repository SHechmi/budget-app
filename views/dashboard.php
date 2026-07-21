<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../models/Alerte.php';

requireLogin();
requireActive();

$userId = $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$pageTitle = $isAdmin ? 'Dashboard Admin' : 'Dashboard';
$fullName = trim($_SESSION['prenom'] . ' ' . $_SESSION['nom']);

if ($isAdmin) {
    require_once __DIR__ . '/../controllers/AdminController.php';
    $adminCtrl = new AdminController(getDB());
    $stats = $adminCtrl->getGlobalStats();
    $pendingUsers = $adminCtrl->getUsersEnAttente();
    
    // Données pour les graphiques
    $monthlyStats = $adminCtrl->getMonthlyStats(); // Transactions par mois
    $userGrowth = $adminCtrl->getUserGrowth(); // Croissance des utilisateurs
    $categoryStats = $adminCtrl->getCategoryStats(); // Stats par catégorie
    $budgetUtilization = $adminCtrl->getBudgetUtilization(); // Utilisation des budgets
} else {
    $balance = Transaction::getBalance($userId);
    $totalDepenses = Transaction::getTotalByType($userId, 'depense');
    $totalRevenus = Transaction::getTotalByType($userId, 'revenu');
    $activeBudgets = Budget::getActiveByUser($userId);
    $recentTransactions = Transaction::getRecent($userId, 5);
    $depensesParCategorie = Transaction::getTotalsByCategory($userId, 'depense');
    $unreadAlertes = Alerte::countUnread($userId);
    $activeCount = count($activeBudgets);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Budget App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1a1c2e 0%, #2d1b4e 100%);
            backdrop-filter: blur(10px);
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 12px 20px;
            border-radius: 12px;
            margin: 6px 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar .nav-link i {
            width: 24px;
            margin-right: 12px;
        }
        
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white !important;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(102,126,234,0.3);
        }
        
        .main-content {
            background: #f8fafc;
            min-height: 100vh;
            border-radius: 30px 0 0 30px;
            padding: 30px;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 25px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '📊';
            position: absolute;
            bottom: -30px;
            right: -30px;
            font-size: 150px;
            opacity: 0.1;
        }
        
        .stat-card {
            background: white;
            border: none;
            border-radius: 20px;
            padding: 25px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .stat-card:hover::before {
            left: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
        }
        
        .chart-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .chart-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .user-row {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .user-row:hover {
            background: #f8fafc;
            transform: translateX(5px);
            border-left-color: #667eea;
        }
        
        .badge-pending {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .fade-in-up {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }
        .fade-in-up.in-view {
            opacity: 1;
            transform: translateY(0);
        }
        
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            
            <div class="col-md-10 main-content">
                <?php if ($isAdmin): ?>
                    <!-- ========== ADMIN DASHBOARD ========== -->
                    <div class="welcome-section fade-in-up">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2">👋 Bonjour, <?= htmlspecialchars($fullName) ?></h2>
                                <p class="mb-0 opacity-75">Tableau de bord d'administration - Vue globale du système</p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <a href="admin.php" class="btn btn-light">
                                    <i class="fas fa-users-cog me-2"></i>Gérer les utilisateurs
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Messages Flash -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- Cartes Statistiques -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card fade-in-up" style="animation-delay: 0.1s">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220); color: #667eea">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h6 class="text-muted mb-2">Total Utilisateurs</h6>
                                <h2 class="mb-0"><?= $stats['totalUsers'] ?></h2>
                                <small class="text-success mt-2"><i class="fas fa-arrow-up"></i> +<?= $stats['newUsersThisMonth'] ?> ce mois</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card fade-in-up" style="animation-delay: 0.2s">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #48bb7820, #0d948820); color: #48bb78">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <h6 class="text-muted mb-2">Utilisateurs Actifs</h6>
                                <h2 class="mb-0"><?= $stats['activeUsers'] ?></h2>
                                <small class="text-success mt-2"><i class="fas fa-chart-line"></i> Taux: <?= round(($stats['activeUsers']/$stats['totalUsers'])*100, 1) ?>%</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card fade-in-up" style="animation-delay: 0.3s">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #f6ad5520, #ed893620); color: #ed8936">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <h6 class="text-muted mb-2">Transactions</h6>
                                <h2 class="mb-0"><?= $stats['totalTransactions'] ?></h2>
                                <small class="text-info mt-2">Montant: <?= number_format($stats['totalMontant'], 0, ',', ' ') ?> TND</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card fade-in-up" style="animation-delay: 0.4s">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #38b2ac20, #31979520); color: #319795">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <h6 class="text-muted mb-2">Budgets</h6>
                                <h2 class="mb-0"><?= $stats['totalBudgets'] ?></h2>
                                <small class="text-warning mt-2"><i class="fas fa-bell"></i> Alertes: <?= $stats['totalAlertes'] ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Graphiques Ligne 1 -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-7">
                            <div class="chart-container fade-in-up" style="animation-delay: 0.5s">
                                <h5 class="mb-4"><i class="fas fa-chart-line me-2" style="color: #667eea"></i>Évolution des transactions (6 derniers mois)</h5>
                                <canvas id="transactionsChart" height="280"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="chart-container fade-in-up" style="animation-delay: 0.6s">
                                <h5 class="mb-4"><i class="fas fa-chart-pie me-2" style="color: #667eea"></i>Répartition par type</h5>
                                <canvas id="typeChart" height="280"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Graphiques Ligne 2 -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <div class="chart-container fade-in-up" style="animation-delay: 0.7s">
                                <h5 class="mb-4"><i class="fas fa-chart-bar me-2" style="color: #667eea"></i>Top catégories de dépenses</h5>
                                <canvas id="categoriesChart" height="280"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="chart-container fade-in-up" style="animation-delay: 0.8s">
                                <h5 class="mb-4"><i class="fas fa-chart-simple me-2" style="color: #667eea"></i>Croissance des utilisateurs</h5>
                                <canvas id="userGrowthChart" height="280"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Graphique Budgets et Comptes en attente -->
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="chart-container fade-in-up" style="animation-delay: 0.9s">
                                <h5 class="mb-4"><i class="fas fa-chart-donut me-2" style="color: #667eea"></i>Utilisation des budgets</h5>
                                <canvas id="budgetChart" height="280"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="table-container fade-in-up" style="animation-delay: 1s">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0"><i class="fas fa-clock me-2" style="color: #667eea"></i>Comptes en attente</h5>
                                    <span class="badge-pending"><?= count($pendingUsers) ?> en attente</span>
                                </div>
                                
                                <?php if (empty($pendingUsers)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                        <p class="text-muted">Aucun compte en attente de validation</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th><i class="fas fa-user me-1"></i> Utilisateur</th>
                                                    <th><i class="fas fa-envelope me-1"></i> Email</th>
                                                    <th><i class="fas fa-calendar me-1"></i> Date</th>
                                                    <th><i class="fas fa-cog me-1"></i> Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pendingUsers as $user): ?>
                                                <tr>
                                                    <td class="fw-semibold"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
                                                    <td>
                                                        <form method="POST" action="<?= BASE_URL ?>controllers/AdminController.php" class="d-inline">
                                                            <input type="hidden" name="action" value="activer_compte">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <button class="btn btn-sm btn-success" onclick="return confirm('Activer ce compte ?')">
                                                                <i class="fas fa-check"></i> Activer
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Scripts pour les graphiques -->
                    <script>
                        // 1. Graphique des transactions (Ligne)
                        const ctx1 = document.getElementById('transactionsChart').getContext('2d');
                        new Chart(ctx1, {
                            type: 'line',
                            data: {
                                labels: <?= json_encode($monthlyStats['months'] ?? ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin']) ?>,
                                datasets: [
                                    {
                                        label: 'Revenus',
                                        data: <?= json_encode($monthlyStats['revenus'] ?? [0, 0, 0, 0, 0, 0]) ?>,
                                        borderColor: '#48bb78',
                                        backgroundColor: 'rgba(72, 187, 120, 0.1)',
                                        borderWidth: 3,
                                        tension: 0.4,
                                        fill: true
                                    },
                                    {
                                        label: 'Dépenses',
                                        data: <?= json_encode($monthlyStats['depenses'] ?? [0, 0, 0, 0, 0, 0]) ?>,
                                        borderColor: '#f56565',
                                        backgroundColor: 'rgba(245, 101, 101, 0.1)',
                                        borderWidth: 3,
                                        tension: 0.4,
                                        fill: true
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: { position: 'top' },
                                    tooltip: { callbacks: { label: function(context) { return `${context.dataset.label}: ${context.raw.toLocaleString('fr-FR')} TND`; } } }
                                }
                            }
                        });

                        // 2. Graphique des types (Doughnut)
                        const ctx2 = document.getElementById('typeChart').getContext('2d');
                        new Chart(ctx2, {
                            type: 'doughnut',
                            data: {
                                labels: ['Revenus', 'Dépenses'],
                                datasets: [{
                                    data: [<?= $stats['totalRevenus'] ?? 0 ?>, <?= $stats['totalDepenses'] ?? 0 ?>],
                                    backgroundColor: ['#48bb78', '#f56565'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: { position: 'bottom' },
                                    tooltip: { callbacks: { label: function(context) { return `${context.label}: ${context.raw.toLocaleString('fr-FR')} TND`; } } }
                                }
                            }
                        });

                        // 3. Graphique des catégories (Barre horizontale)
                        const ctx3 = document.getElementById('categoriesChart').getContext('2d');
                        new Chart(ctx3, {
                            type: 'bar',
                            data: {
                                labels: <?= json_encode(array_column($categoryStats, 'nom')) ?>,
                                datasets: [{
                                    label: 'Montant (TND)',
                                    data: <?= json_encode(array_column($categoryStats, 'total')) ?>,
                                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                                    borderRadius: 10
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                indexAxis: 'y',
                                plugins: {
                                    legend: { display: false },
                                    tooltip: { callbacks: { label: function(context) { return `${context.raw.toLocaleString('fr-FR')} TND`; } } }
                                }
                            }
                        });

                        // 4. Graphique croissance utilisateurs
                        const ctx4 = document.getElementById('userGrowthChart').getContext('2d');
                        new Chart(ctx4, {
                            type: 'line',
                            data: {
                                labels: <?= json_encode($userGrowth['months'] ?? ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin']) ?>,
                                datasets: [{
                                    label: 'Nouveaux utilisateurs',
                                    data: <?= json_encode($userGrowth['counts'] ?? [0, 0, 0, 0, 0, 0]) ?>,
                                    borderColor: '#667eea',
                                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: { position: 'top' }
                                }
                            }
                        });

                        // 5. Graphique utilisation budgets (Donut)
                        const ctx5 = document.getElementById('budgetChart').getContext('2d');
                        new Chart(ctx5, {
                            type: 'doughnut',
                            data: {
                                labels: <?= json_encode($budgetUtilization['names'] ?? ['Budget 1', 'Budget 2', 'Budget 3']) ?>,
                                datasets: [{
                                    data: <?= json_encode($budgetUtilization['percentages'] ?? [0, 0, 0]) ?>,
                                    backgroundColor: ['#667eea', '#48bb78', '#ed8936', '#f56565', '#38b2ac', '#9f7aea'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: { position: 'bottom' },
                                    tooltip: { callbacks: { label: function(context) { return `${context.label}: ${context.raw}%`; } } }
                                }
                            }
                        });
                    </script>

                <?php else: ?>
                    <!-- ========== USER DASHBOARD ========== -->
                    <div class="welcome-section fade-in-up">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2">👋 Bonjour, <?= htmlspecialchars($fullName) ?> !</h2>
                                <p class="mb-0 opacity-75">Voici un récapitulatif de votre situation financière</p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <?php if ($unreadAlertes > 0): ?>
                                    <a href="alertes.php" class="btn btn-light position-relative">
                                        <i class="fas fa-bell"></i> Alertes
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?= $unreadAlertes ?>
                                        </span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card fade-in-up" style="animation-delay: 0.1s">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220); color: #667eea">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <h6 class="text-muted mb-2">Solde actuel</h6>
                                <h3 class="mb-2"><?= number_format($balance, 2, ',', ' ') ?> <small class="fs-6">TND</small></h3>
                                <small class="text-muted">Revenus - Dépenses</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card fade-in-up" style="animation-delay: 0.2s">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #f5656520, #ed64a620); color: #f56565">
                                    <i class="fas fa-arrow-down"></i>
                                </div>
                                <h6 class="text-muted mb-2">Dépenses totales</h6>
                                <h3 class="mb-2 text-danger"><?= number_format($totalDepenses, 2, ',', ' ') ?> <small class="fs-6">TND</small></h3>
                                <small class="text-muted">Ce mois</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card fade-in-up" style="animation-delay: 0.3s">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #48bb7820, #0d948820); color: #48bb78">
                                    <i class="fas fa-arrow-up"></i>
                                </div>
                                <h6 class="text-muted mb-2">Revenus totaux</h6>
                                <h3 class="mb-2 text-success"><?= number_format($totalRevenus, 2, ',', ' ') ?> <small class="fs-6">TND</small></h3>
                                <small class="text-muted">Ce mois</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card fade-in-up" style="animation-delay: 0.4s">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #38b2ac20, #31979520); color: #319795">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h6 class="text-muted mb-2">Taux d'épargne</h6>
                                <h3 class="mb-2"><?= $totalRevenus > 0 ? round(($balance / $totalRevenus) * 100, 1) : 0 ?>%</h3>
                                <small class="text-muted">du revenu total</small>
                            </div>
                        </div>
                    </div>

                    <!-- Charts and Budgets -->
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="chart-container fade-in-up" style="animation-delay: 0.5s">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Dépenses par catégorie</h5>
                                    <span class="badge bg-secondary">Ce mois</span>
                                </div>
                                <?php if (empty($depensesParCategorie)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Aucune donnée à afficher</p>
                                    </div>
                                <?php else: ?>
                                    <canvas id="userPieChart" height="280"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="chart-container fade-in-up" style="animation-delay: 0.6s">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Budgets actifs</h5>
                                    <span class="badge bg-info"><?= $activeCount ?> en cours</span>
                                </div>
                                <?php if (empty($activeBudgets)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Aucun budget actif</p>
                                        <a href="budgets.php" class="btn btn-primary btn-sm">Créer un budget</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($activeBudgets as $budget): ?>
                                        <?php $utilisation = Budget::getUsage($budget['id']); ?>
                                        <?php 
                                        $percent = min($utilisation['pourcentage'], 100);
                                        $color = $percent < 70 ? '#48bb78' : ($percent < 90 ? '#ed8936' : '#f56565');
                                        ?>
                                        <div class="mb-4">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <strong><?= htmlspecialchars($budget['nom']) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= ucfirst($budget['type']) ?> · <?= ucfirst($budget['periode']) ?></small>
                                                </div>
                                                <div class="text-end">
                                                    <span class="fw-bold"><?= number_format($utilisation['depenses'], 2, ',', ' ') ?> TND</span>
                                                    <br>
                                                    <small class="text-muted">/ <?= number_format($utilisation['plafond'], 2, ',', ' ') ?> TND</small>
                                                </div>
                                            </div>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar" style="width: <?= $percent ?>%; background: linear-gradient(90deg, <?= $color ?>, <?= $color ?>)"></div>
                                            </div>
                                            <div class="d-flex justify-content-between mt-1">
                                                <small class="text-muted">0%</small>
                                                <small class="fw-semibold" style="color: <?= $color ?>"><?= $utilisation['pourcentage'] ?>%</small>
                                                <small class="text-muted">100%</small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="row g-4 mt-2">
                        <div class="col-lg-8">
                            <div class="chart-container fade-in-up" style="animation-delay: 0.7s">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Dernières transactions</h5>
                                    <a href="transactions.php" class="btn btn-sm btn-outline-primary">
                                        Voir toutes <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                                <?php if (empty($recentTransactions)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Aucune transaction enregistrée</p>
                                        <a href="transactions.php" class="btn btn-primary btn-sm">Ajouter une transaction</a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Description</th>
                                                    <th>Catégorie</th>
                                                    <th>Date</th>
                                                    <th class="text-end">Montant</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentTransactions as $transaction): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($transaction['description'] ?: '—') ?></td>
                                                    <td><?= htmlspecialchars($transaction['categorie_nom'] ?: 'Sans catégorie') ?></td>
                                                    <td><?= date('d/m/Y', strtotime($transaction['date'])) ?></td>
                                                    <td class="text-end <?= $transaction['type'] === 'depense' ? 'text-danger' : 'text-success' ?>">
                                                        <?= $transaction['type'] === 'depense' ? '-' : '+' ?><?= number_format($transaction['montant'], 2, ',', ' ') ?> TND
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="chart-container fade-in-up" style="animation-delay: 0.8s">
                                <h5 class="mb-4"><i class="fas fa-chart-simple me-2"></i>Résumé financier</h5>
                                
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-3">
                                        <span>Revenus</span>
                                        <span class="fw-bold text-success"><?= number_format($totalRevenus, 2, ',', ' ') ?> TND</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span>Dépenses</span>
                                        <span class="fw-bold text-danger"><?= number_format($totalDepenses, 2, ',', ' ') ?> TND</span>
                                    </div>
                                    <div class="d-flex justify-content-between pt-3 border-top">
                                        <span class="fw-semibold">Solde</span>
                                        <span class="fw-bold fs-5 <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($balance, 2, ',', ' ') ?> TND
                                        </span>
                                    </div>
                                </div>

                                <hr>

                                <div class="mt-4">
                                    <button class="btn btn-primary w-100" onclick="window.location.href='transactions.php'">
                                        <i class="fas fa-plus me-2"></i>Ajouter une transaction
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Script pour le graphique utilisateur -->
                    <script>
                        <?php if (!empty($depensesParCategorie)): ?>
                        const userCtx = document.getElementById('userPieChart').getContext('2d');
                        const userCategories = <?= json_encode(array_column($depensesParCategorie, 'categorie')) ?>;
                        const userValues = <?= json_encode(array_map(fn($item) => (float)$item['total'], $depensesParCategorie)) ?>;
                        const colors = ['#667eea', '#48bb78', '#ed8936', '#f56565', '#38b2ac', '#9f7aea', '#ecc94b', '#a0aec0'];
                        
                        new Chart(userCtx, {
                            type: 'doughnut',
                            data: {
                                labels: userCategories,
                                datasets: [{
                                    data: userValues,
                                    backgroundColor: colors.slice(0, userCategories.length),
                                    borderWidth: 0,
                                    hoverOffset: 10
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: { position: 'bottom', labels: { font: { size: 12 }, padding: 15 } },
                                    tooltip: { callbacks: { label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${label}: ${value.toLocaleString('fr-FR')} TND (${percentage}%)`;
                                    } } }
                                },
                                cutout: '60%'
                            }
                        });
                        <?php endif; ?>
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Animation on scroll
        const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.welcome-section, .stat-card, .chart-container, .table-container').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>