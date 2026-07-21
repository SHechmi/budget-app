<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../utils/auth.php';

requireLogin();
requireAdmin();

$admin = new AdminController(getDB());
$stats = $admin->getGlobalStats();
$users = $admin->getAllUsers();
$pageTitle = 'Gestion des utilisateurs';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Budget App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            content: '👥';
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
        
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .user-table {
            border-radius: 15px;
            overflow: hidden;
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
        
        .badge-role {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-admin {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
        }
        
        .badge-user {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .badge-active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .badge-inactive {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .btn-action {
            transition: all 0.3s ease;
            margin: 0 2px;
        }
        
        .btn-action:hover {
            transform: scale(1.1);
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
                <div class="welcome-section fade-in-up">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">👋 Gestion des utilisateurs</h2>
                            <p class="mb-0 opacity-75">Activez, désactivez, modifiez les rôles ou supprimez des comptes.</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="dashboard.php" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Retour dashboard
                            </a>
                        </div>
                    </div>
                </div>

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

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.1s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220); color: #667eea">
                                <i class="fas fa-users"></i>
                            </div>
                            <h6 class="text-muted mb-2">Total utilisateurs</h6>
                            <h2 class="mb-0"><?= $stats['totalUsers'] ?></h2>
                            <small class="text-success mt-2"><i class="fas fa-chart-line"></i> Total inscrits</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.2s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #48bb7820, #0d948820); color: #48bb78">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <h6 class="text-muted mb-2">Utilisateurs actifs</h6>
                            <h2 class="mb-0"><?= $stats['activeUsers'] ?></h2>
                            <small class="text-success mt-2"><i class="fas fa-percent"></i> <?= round(($stats['activeUsers']/$stats['totalUsers'])*100, 1) ?>% du total</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.3s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #38b2ac20, #31979520); color: #319795">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <h6 class="text-muted mb-2">Total transactions</h6>
                            <h2 class="mb-0"><?= $stats['totalTransactions'] ?></h2>
                            <small class="text-info mt-2"><i class="fas fa-chart-bar"></i> Montant: <?= number_format($stats['totalMontant'], 0, ',', ' ') ?> TND</small>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="table-container fade-in-up" style="animation-delay: 0.4s">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Tous les utilisateurs</h5>
                        <span class="badge bg-secondary"><?= count($users) ?> utilisateurs</span>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle user-table">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-hashtag me-1"></i> ID</th>
                                    <th><i class="fas fa-user me-1"></i> Nom complet</th>
                                    <th><i class="fas fa-envelope me-1"></i> Email</th>
                                    <th><i class="fas fa-tag me-1"></i> Rôle</th>
                                    <th><i class="fas fa-circle me-1"></i> Statut</th>
                                    <th><i class="fas fa-calendar me-1"></i> Inscription</th>
                                    <th><i class="fas fa-cog me-1"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr class="user-row">
                                    <td class="fw-semibold">#<?= $user['id'] ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
                                        <small class="text-muted">ID: <?= $user['id'] ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <form method="POST" action="<?= BASE_URL ?>controllers/AdminController.php" class="d-inline">
                                            <input type="hidden" name="action" value="changer_role">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <select name="role" class="form-select form-select-sm" style="width: auto; min-width: 120px;" onchange="this.form.submit()">
                                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>👤 Utilisateur</option>
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>👑 Administrateur</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($user['actif']): ?>
                                            <span class="badge-active badge-role"><i class="fas fa-check-circle me-1"></i> Actif</span>
                                        <?php else: ?>
                                            <span class="badge-inactive badge-role"><i class="fas fa-clock me-1"></i> En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="far fa-calendar-alt text-muted me-1"></i>
                                        <?= date('d/m/Y', strtotime($user['date_inscription'])) ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (!$user['actif']): ?>
                                                <form method="POST" action="<?= BASE_URL ?>controllers/AdminController.php" class="d-inline">
                                                    <input type="hidden" name="action" value="activer_compte">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button class="btn btn-success btn-action" title="Activer le compte">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="<?= BASE_URL ?>controllers/AdminController.php" class="d-inline">
                                                    <input type="hidden" name="action" value="desactiver_compte">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button class="btn btn-warning btn-action" title="Désactiver le compte">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" action="<?= BASE_URL ?>controllers/AdminController.php" class="d-inline" onsubmit="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer définitivement ce compte ? Cette action est irréversible.')">
                                                <input type="hidden" name="action" value="supprimer_compte">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button class="btn btn-danger btn-action" title="Supprimer le compte">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.welcome-section, .stat-card, .table-container').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>