<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Alerte.php';
require_once __DIR__ . '/../utils/auth.php';
requireLogin();
requireActive();

// Blocage admin
if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit();
}

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
if ($userId === null) { redirect(BASE_URL . 'views/login.php'); exit(); }

$alertes = Alerte::getByUser($userId);
$unreadCount = Alerte::countUnread($userId);
$pageTitle = "Mes Alertes";
$fullName = trim($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Budget App</title>
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
            content: '🔔';
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
            transform: translateY(-5px);
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
        
        .alert-card {
            background: white;
            border: none;
            border-radius: 20px;
            padding: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .alert-card:hover {
            transform: translateX(8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        .alert-item {
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .alert-unread {
            background: linear-gradient(135deg, #fef3c7, #fffbeb);
            border-left-color: #f59e0b;
        }
        
        .alert-read {
            background-color: white;
            border-left-color: #10b981;
        }
        
        .alert-depassement {
            border-left-color: #ef4444;
            background: linear-gradient(135deg, #fef2f2, #fff5f5);
        }
        
        .badge-seuil {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-depassement {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-unread {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 80px;
            color: #cbd5e1;
            margin-bottom: 20px;
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
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
            color: white;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
            color: white;
        }
        
        .btn-outline-custom {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: white;
        }
        
        .btn-outline-custom:hover {
            border-color: #667eea;
            transform: translateY(-2px);
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
                            <h2 class="mb-2">🔔 Mes Alertes</h2>
                            <p class="mb-0 opacity-75">Suivez vos notifications et restez informé de vos finances</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="dashboard.php" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Retour dashboard
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

                <!-- Statistiques Alertes -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.1s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220); color: #667eea">
                                <i class="fas fa-bell"></i>
                            </div>
                            <h6 class="text-muted mb-2">Total alertes</h6>
                            <h2 class="mb-0"><?= count($alertes) ?></h2>
                            <small class="text-muted mt-2">Toutes les alertes</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.2s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b20, #d9770620); color: #f59e0b">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h6 class="text-muted mb-2">Non lues</h6>
                            <h2 class="mb-0 text-warning"><?= $unreadCount ?></h2>
                            <small class="text-muted mt-2">À consulter</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.3s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #10b98120, #05966920); color: #10b981">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h6 class="text-muted mb-2">Lues</h6>
                            <h2 class="mb-0 text-success"><?= count($alertes) - $unreadCount ?></h2>
                            <small class="text-muted mt-2">Déjà consultées</small>
                        </div>
                    </div>
                </div>

                <!-- Liste des Alertes -->
                <?php if (empty($alertes)): ?>
                    <div class="alert-card fade-in-up" style="animation-delay: 0.4s">
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h4 class="mb-3">Aucune alerte</h4>
                            <p class="text-muted mb-4">Vous recevrez des alertes lorsque vous approcherez vos budgets.</p>
                            <a href="budgets.php" class="btn btn-primary-custom">
                                <i class="fas fa-plus me-2"></i>Créer un budget
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <div class="col-lg-8 mx-auto">
                            <?php if ($unreadCount > 0): ?>
                            <div class="d-flex justify-content-end mb-4 fade-in-up" style="animation-delay: 0.4s">
                                <form method="POST" action="/budget_app/controllers/AlerteController.php">
                                    <input type="hidden" name="action" value="mark_all_read">
                                    <button type="submit" class="btn btn-primary-custom">
                                        <i class="fas fa-check-double me-2"></i>Tout marquer comme lu
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                            
                            <?php foreach ($alertes as $index => $alerte): ?>
                                <div class="alert-card fade-in-up mb-4" style="animation-delay: <?= 0.5 + ($index * 0.05) ?>s">
                                    <div class="alert-item p-4 <?= $alerte['lu'] ? 'alert-read' : 'alert-unread' ?> <?= $alerte['type'] === 'depassement' ? 'alert-depassement' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="mb-3">
                                                    <?php if ($alerte['type'] === 'seuil'): ?>
                                                        <span class="badge-seuil">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>Seuil d'alerte
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge-depassement">
                                                            <i class="fas fa-times-circle me-1"></i>Dépassement
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!$alerte['lu']): ?>
                                                        <span class="badge-unread ms-2">
                                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>Non lu
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <p class="card-text mb-2 fs-5">
                                                    <?= htmlspecialchars($alerte['message']) ?>
                                                </p>
                                                
                                                <small class="text-muted">
                                                    <i class="far fa-clock me-1"></i>
                                                    <?= date('d/m/Y à H:i', strtotime($alerte['date_alerte'])) ?>
                                                </small>
                                            </div>
                                            
                                            <div class="btn-group ms-3">
                                                <?php if (!$alerte['lu']): ?>
                                                    <form method="POST" action="/budget_app/controllers/AlerteController.php" class="d-inline me-2">
                                                        <input type="hidden" name="action" value="mark_read">
                                                        <input type="hidden" name="alerte_id" value="<?= $alerte['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3" title="Marquer comme lu">
                                                            <i class="fas fa-check me-1"></i>Lu
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" action="/budget_app/controllers/AlerteController.php" class="d-inline" onsubmit="return confirm('Supprimer cette alerte ?')">
                                                    <input type="hidden" name="action" value="delete_alerte">
                                                    <input type="hidden" name="alerte_id" value="<?= $alerte['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Supprimer">
                                                        <i class="fas fa-trash-alt me-1"></i>Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
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

        document.querySelectorAll('.welcome-section, .stat-card, .alert-card').forEach(el => {
            observer.observe(el);
        });
        
        // Auto-refresh des alertes toutes les 30 secondes (optionnel)
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>