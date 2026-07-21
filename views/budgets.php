<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../models/Budget.php';

requireLogin();
requireActive();

// Vérifier que l'utilisateur n'est PAS admin
if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit();
}

$userId = $_SESSION['user_id'];
$pageTitle = 'Budgets';
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$budgets = Budget::getAllByUser($userId);
$editBudget = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editBudget = Budget::getById($editId, $userId);
    if ($editBudget && $editBudget['id_createur'] !== $userId) $editBudget = null;
}
$fullName = trim($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
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
            content: '💰';
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
        
        .form-card, .list-card {
            background: white;
            border: none;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .form-card:hover, .list-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        .budget-row {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .budget-row:hover {
            background: #f8fafc;
            transform: translateX(5px);
            border-left-color: #667eea;
        }
        
        .progress-custom {
            background: #e2e8f0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
        }
        
        .progress-bar-custom {
            border-radius: 10px;
            transition: width 1s ease-in-out;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
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
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: white;
        }
        
        .btn-outline-custom:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .form-control, .form-select {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            transform: translateY(-2px);
        }
        
        .badge-individuel {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
        }
        
        .badge-partage {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
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
                            <h2 class="mb-2">💰 Mes Budgets</h2>
                            <p class="mb-0 opacity-75">Créez, gérez et suivez vos budgets personnels ou partagés</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="dashboard.php" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Retour dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Messages Flash -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistiques -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.1s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220); color: #667eea">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h6 class="text-muted mb-2">Total budgets</h6>
                            <h2 class="mb-0"><?= count($budgets) ?></h2>
                            <small class="text-muted mt-2">Budgets créés</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.2s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #48bb7820, #0d948820); color: #48bb78">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <h6 class="text-muted mb-2">Plafond total</h6>
                            <h2 class="mb-0"><?= number_format(array_sum(array_column($budgets, 'plafond_global')), 0, ',', ' ') ?> TND</h2>
                            <small class="text-muted mt-2">Budget maximum alloué</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.3s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #38b2ac20, #31979520); color: #319795">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h6 class="text-muted mb-2">Utilisation moyenne</h6>
                            <h2 class="mb-0"><?= !empty($budgets) ? round(array_sum(array_map(function($b) { $u = Budget::getUsage($b['id']); return $u['pourcentage']; }, $budgets)) / count($budgets), 1) : 0 ?>%</h2>
                            <small class="text-muted mt-2">Taux d'utilisation</small>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Formulaire Créer/Modifier -->
                    <div class="col-lg-5">
                        <div class="form-card fade-in-up" style="animation-delay: 0.4s">
                            <h5 class="mb-4">
                                <i class="fas <?= $editBudget ? 'fa-edit' : 'fa-plus-circle' ?> me-2" style="color: #667eea"></i>
                                <?= $editBudget ? 'Modifier le budget' : 'Créer un budget' ?>
                            </h5>
                            
                            <form method="POST" action="<?= BASE_URL ?>controllers/BudgetController.php">
                                <input type="hidden" name="action" value="<?= $editBudget ? 'update_budget' : 'add_budget' ?>">
                                <?php if ($editBudget): ?>
                                    <input type="hidden" name="budget_id" value="<?= $editBudget['id'] ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-tag me-1"></i>Nom du budget
                                    </label>
                                    <input type="text" name="nom" class="form-control" placeholder="Ex: Courses, Loisirs, Voyage..." value="<?= htmlspecialchars($editBudget['nom'] ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-users me-1"></i>Type
                                    </label>
                                    <select name="type" class="form-select">
                                        <option value="individuel" <?= $editBudget && $editBudget['type'] === 'individuel' ? 'selected' : '' ?>>
                                            👤 Individuel
                                        </option>
                                        <option value="partage" <?= $editBudget && $editBudget['type'] === 'partage' ? 'selected' : '' ?>>
                                            👥 Partagé
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-calendar-alt me-1"></i>Période
                                    </label>
                                    <select name="periode" class="form-select">
                                        <option value="mensuel" <?= $editBudget && $editBudget['periode'] === 'mensuel' ? 'selected' : '' ?>>
                                            📅 Mensuel
                                        </option>
                                        <option value="hebdomadaire" <?= $editBudget && $editBudget['periode'] === 'hebdomadaire' ? 'selected' : '' ?>>
                                            📆 Hebdomadaire
                                        </option>
                                        <option value="personnalise" <?= $editBudget && $editBudget['periode'] === 'personnalise' ? 'selected' : '' ?>>
                                            ⚙️ Personnalisé
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Date début</label>
                                        <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($editBudget['date_debut'] ?? date('Y-m-d')) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Date fin</label>
                                        <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($editBudget['date_fin'] ?? date('Y-m-d', strtotime('+1 month'))) ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-coins me-1"></i>Plafond (TND)
                                    </label>
                                    <input type="number" step="0.01" name="plafond_global" class="form-control" placeholder="0.00" value="<?= htmlspecialchars($editBudget['plafond_global'] ?? '') ?>" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary-custom w-100">
                                    <i class="fas <?= $editBudget ? 'fa-save' : 'fa-plus' ?> me-2"></i>
                                    <?= $editBudget ? 'Mettre à jour' : 'Créer le budget' ?>
                                </button>
                                
                                <?php if ($editBudget): ?>
                                    <div class="text-center mt-3">
                                        <a href="<?= BASE_URL ?>views/budgets.php" class="btn btn-outline-custom">
                                            <i class="fas fa-times me-2"></i>Annuler
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Liste des budgets -->
                    <div class="col-lg-7">
                        <div class="list-card fade-in-up" style="animation-delay: 0.5s">
                            <h5 class="mb-4">
                                <i class="fas fa-list me-2" style="color: #667eea"></i>
                                Mes budgets
                                <span class="badge bg-secondary ms-2"><?= count($budgets) ?> budgets</span>
                            </h5>
                            
                            <?php if (empty($budgets)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Vous n'avez pas encore de budget</p>
                                    <small class="text-muted">Créez votre premier budget pour commencer à suivre vos dépenses</small>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th><i class="fas fa-tag me-1"></i> Nom</th>
                                                <th><i class="fas fa-calendar me-1"></i> Période</th>
                                                <th><i class="fas fa-coins me-1"></i> Plafond</th>
                                                <th><i class="fas fa-chart-line me-1"></i> Utilisation</th>
                                                <th><i class="fas fa-cog me-1"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($budgets as $budget): ?>
                                                <?php $usage = Budget::getUsage($budget['id']); ?>
                                                <?php 
                                                $percent = min($usage['pourcentage'], 100);
                                                $color = $percent < 70 ? '#48bb78' : ($percent < 90 ? '#ed8936' : '#f56565');
                                                ?>
                                                <tr class="budget-row">
                                                    <td class="fw-semibold">
                                                        <?= htmlspecialchars($budget['nom']) ?>
                                                        <br>
                                                        <small>
                                                            <span class="badge-<?= $budget['type'] === 'individuel' ? 'individuel' : 'partage' ?>">
                                                                <?= $budget['type'] === 'individuel' ? '👤 Individuel' : '👥 Partagé' ?>
                                                            </span>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <?= ucfirst($budget['periode']) ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?= date('d/m', strtotime($budget['date_debut'])) ?> - <?= date('d/m', strtotime($budget['date_fin'])) ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold"><?= number_format($budget['plafond_global'], 2, ',', ' ') ?> TND</span>
                                                        <br>
                                                        <small class="text-muted">Dépensé: <?= number_format($usage['depenses'], 2, ',', ' ') ?> TND</small>
                                                    </td>
                                                    <td style="min-width: 180px;">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <small class="text-muted">0%</small>
                                                            <small class="fw-semibold" style="color: <?= $color ?>"><?= $usage['pourcentage'] ?>%</small>
                                                            <small class="text-muted">100%</small>
                                                        </div>
                                                        <div class="progress-custom">
                                                            <div class="progress-bar-custom" style="width: <?= $percent ?>%; height: 8px; background: linear-gradient(90deg, <?= $color ?>, <?= $color ?>)"></div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($budget['id_createur'] === $userId): ?>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="?edit=<?= $budget['id'] ?>" class="btn btn-outline-primary" title="Modifier">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form method="POST" action="<?= BASE_URL ?>controllers/BudgetController.php" class="d-inline" onsubmit="return confirm('⚠️ Supprimer ce budget ? Cette action est irréversible.')">
                                                                    <input type="hidden" name="action" value="delete_budget">
                                                                    <input type="hidden" name="budget_id" value="<?= $budget['id'] ?>">
                                                                    <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">
                                                                <i class="fas fa-user-friends me-1"></i>Membre
                                                            </span>
                                                        <?php endif; ?>
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

        document.querySelectorAll('.welcome-section, .stat-card, .form-card, .list-card').forEach(el => {
            observer.observe(el);
        });
        
        // Animation des barres de progression
        setTimeout(() => {
            document.querySelectorAll('.progress-bar-custom').forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => { bar.style.width = width; }, 100);
            });
        }, 500);
    </script>
</body>
</html>