<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../models/BudgetPartage.php';

requireLogin();
requireActive();

// Blocage admin
if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit();
}

$userId = $_SESSION['user_id'];
$pageTitle = 'Budget partagé';
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$sharedBudgets = BudgetPartage::getSharedBudgets($userId);

// Calculs pour les cartes statistiques
if (!empty($sharedBudgets)) {
    $budgetIds = array_column($sharedBudgets, 'id');
    $placeholders = implode(',', array_fill(0, count($budgetIds), '?'));
    $stmtContrib = getDB()->prepare(
        "SELECT COALESCE(SUM(montant), 0) FROM transactions
         WHERE id_utilisateur = ? AND type = 'depense'
         AND id_budget IN ($placeholders)"
    );
    $stmtContrib->execute(array_merge([$userId], $budgetIds));
    $maContribution = (float)$stmtContrib->fetchColumn();

    $maxPct = -1;
    $budgetLePlusSollicite = null;
    foreach ($sharedBudgets as $b) {
        $pct = ((float)$b['plafond_global'] > 0) ? (($b['depenses'] / (float)$b['plafond_global']) * 100) : 0;
        if ($pct > $maxPct) {
            $maxPct = $pct;
            $budgetLePlusSollicite = $b;
        }
    }
} else {
    $maContribution = 0.0;
    $budgetLePlusSollicite = null;
}

$selectedBudget = null;
$members = [];
$transactions = [];
$comments = [];
if (isset($_GET['budget_id'])) {
    $budgetId = (int)$_GET['budget_id'];
    $selectedBudget = Budget::getById($budgetId, $userId);
    if ($selectedBudget) {
        $members = BudgetPartage::getMembers($budgetId);
        $transactions = BudgetPartage::getTransactionsForBudget($budgetId);
        $comments = BudgetPartage::getCommentsByBudget($budgetId);
    }
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
        
        .form-card, .list-card, .detail-card {
            background: white;
            border: none;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .form-card:hover, .list-card:hover, .detail-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        .budget-item {
            background: white;
            border: none;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid transparent;
        }
        
        .budget-item:hover {
            transform: translateX(8px);
            border-left-color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .member-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .member-item:hover {
            background: #f1f5f9;
            transform: translateX(5px);
        }
        
        .comment-item {
            background: #f8fafc;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            border-left: 3px solid #667eea;
        }
        
        .comment-item:hover {
            transform: translateX(5px);
            background: #f1f5f9;
        }
        
        .transaction-item {
            background: white;
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        
        .transaction-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .badge-role-creator {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
        }
        
        .badge-role-member {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
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
                            <h2 class="mb-2">👥 Budgets Partagés</h2>
                            <p class="mb-0 opacity-75">Créez des budgets collaboratifs et gérez vos dépenses en équipe</p>
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
                                <i class="fas fa-users"></i>
                            </div>
                            <h6 class="text-muted mb-2">Mes budgets partagés</h6>
                            <h2 class="mb-0"><?= count($sharedBudgets) ?></h2>
                            <small class="text-muted mt-2">Budgets collaboratifs</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.2s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #48bb7820, #0d948820); color: #48bb78">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <h6 class="text-muted mb-2">Ma contribution</h6>
                            <h2 class="mb-0"><?= number_format($maContribution, 2, ',', ' ') ?> TND</h2>
                            <small class="text-muted mt-2">Mes dépenses dans les budgets partagés</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.3s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #38b2ac20, #31979520); color: #319795">
                                <i class="fas fa-fire"></i>
                            </div>
                            <h6 class="text-muted mb-2">Budget le plus sollicité</h6>
                            <?php if ($budgetLePlusSollicite): ?>
                                <h2 class="mb-0"><?= htmlspecialchars($budgetLePlusSollicite['nom']) ?></h2>
                                <small class="text-muted mt-2"><?= round(((float)$budgetLePlusSollicite['plafond_global']) > 0 ? ($budgetLePlusSollicite['depenses'] / (float)$budgetLePlusSollicite['plafond_global']) * 100 : 0, 1) ?>% utilisé</small>
                            <?php else: ?>
                                <h2 class="mb-0">—</h2>
                                <small class="text-muted mt-2">Aucun budget actif</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- Créer un budget partagé -->
                    <div class="col-lg-5">
                        <div class="form-card fade-in-up" style="animation-delay: 0.4s">
                            <h5 class="mb-4">
                                <i class="fas fa-plus-circle me-2" style="color: #667eea"></i>
                                Créer un budget partagé
                            </h5>
                            <form method="POST" action="<?= BASE_URL ?>controllers/BudgetPartageController.php">
                                <input type="hidden" name="action" value="create_shared_budget">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nom du budget</label>
                                    <input type="text" name="nom" class="form-control" placeholder="Ex: Voyage entre amis" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Période</label>
                                    <select name="periode" class="form-select">
                                        <option value="mensuel">📅 Mensuel</option>
                                        <option value="hebdomadaire">📆 Hebdomadaire</option>
                                        <option value="personnalise">⚙️ Personnalisé</option>
                                    </select>
                                </div>
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Date début</label>
                                        <input type="date" name="date_debut" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Date fin</label>
                                        <input type="date" name="date_fin" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Plafond global (TND)</label>
                                    <input type="number" step="0.01" name="plafond_global" class="form-control" placeholder="0.00" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary-custom w-100">
                                    <i class="fas fa-plus me-2"></i>Créer le budget
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Mes budgets partagés -->
                    <div class="col-lg-7">
                        <div class="list-card fade-in-up" style="animation-delay: 0.5s">
                            <h5 class="mb-4">
                                <i class="fas fa-list me-2" style="color: #667eea"></i>
                                Mes budgets partagés
                            </h5>
                            
                            <?php if (empty($sharedBudgets)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucun budget partagé pour le moment</p>
                                    <small class="text-muted">Créez votre premier budget collaboratif !</small>
                                </div>
                            <?php else: ?>
                                <?php foreach ($sharedBudgets as $budget): ?>
                                    <?php 
                                    $percent = $budget['plafond_global'] > 0 ? round(($budget['depenses'] / $budget['plafond_global']) * 100, 1) : 0;
                                    $color = $percent < 70 ? '#48bb78' : ($percent < 90 ? '#ed8936' : '#f56565');
                                    ?>
                                    <a href="?budget_id=<?= $budget['id'] ?>" class="text-decoration-none">
                                        <div class="budget-item <?= (isset($_GET['budget_id']) && $_GET['budget_id'] == $budget['id']) ? 'border-left-color: #667eea; background: #f8fafc;' : '' ?>">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <div class="fw-semibold fs-5"><?= htmlspecialchars($budget['nom']) ?></div>
                                                    <small class="text-muted">
                                                        <i class="far fa-calendar-alt me-1"></i><?= ucfirst($budget['periode']) ?>
                                                    </small>
                                                </div>
                                                <div class="text-end">
                                                    <span class="fw-bold fs-5"><?= number_format($budget['depenses'], 2, ',', ' ') ?> TND</span>
                                                    <br>
                                                    <small class="text-muted">/ <?= number_format($budget['plafond_global'], 2, ',', ' ') ?> TND</small>
                                                </div>
                                            </div>
                                            <div class="progress mt-2" style="height: 8px;">
                                                <div class="progress-bar" style="width: <?= min($percent, 100) ?>%; background: linear-gradient(90deg, <?= $color ?>, <?= $color ?>)"></div>
                                            </div>
                                            <div class="d-flex justify-content-between mt-2">
                                                <small class="text-muted">0%</small>
                                                <small class="fw-semibold" style="color: <?= $color ?>"><?= $percent ?>%</small>
                                                <small class="text-muted">100%</small>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Détails du budget sélectionné -->
                <?php if ($selectedBudget): ?>
                    <div class="row g-4 mt-2">
                        <div class="col-lg-6">
                            <!-- Membres -->
                            <div class="detail-card fade-in-up" style="animation-delay: 0.6s">
                                <h5 class="mb-4">
                                    <i class="fas fa-users me-2" style="color: #667eea"></i>
                                    Membres du budget
                                    <span class="badge bg-secondary ms-2"><?= count($members) ?> membres</span>
                                </h5>
                                
                                <?php if (empty($members)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Aucun membre pour le moment</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($members as $member): ?>
                                        <div class="member-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-semibold">
                                                        <?= htmlspecialchars($member['prenom'] . ' ' . $member['nom']) ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($member['email']) ?>
                                                    </small>
                                                </div>
                                                <span class="badge-<?= $member['role'] === 'createur' ? 'creator' : 'member' ?>">
                                                    <i class="fas <?= $member['role'] === 'createur' ? 'fa-crown' : 'fa-user' ?> me-1"></i>
                                                    <?= $member['role'] === 'createur' ? 'Créateur' : 'Membre' ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <!-- Ajouter un membre (si créateur) -->
                                <?php if ($selectedBudget['id_createur'] === $userId): ?>
                                    <form method="POST" action="<?= BASE_URL ?>controllers/BudgetPartageController.php" class="mt-4 pt-3 border-top">
                                        <input type="hidden" name="action" value="add_budget_member">
                                        <input type="hidden" name="budget_id" value="<?= $selectedBudget['id'] ?>">
                                        
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-user-plus me-1"></i>Inviter un membre
                                        </label>
                                        <div class="input-group">
                                            <input type="email" name="email" class="form-control" placeholder="email@exemple.com" required>
                                            <button type="submit" class="btn btn-primary-custom">
                                                <i class="fas fa-paper-plane"></i> Inviter
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Transactions -->
                            <div class="detail-card fade-in-up mt-4" style="animation-delay: 0.7s">
                                <h5 class="mb-4">
                                    <i class="fas fa-exchange-alt me-2" style="color: #667eea"></i>
                                    Transactions
                                    <span class="badge bg-secondary ms-2"><?= count($transactions) ?> transactions</span>
                                </h5>
                                
                                <?php if (empty($transactions)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Aucune transaction</p>
                                        <small class="text-muted">Ajoutez des transactions depuis le tableau de bord</small>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($transactions as $tx): ?>
<div class="transaction-item">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="flex-grow-1">
            <div class="fw-semibold"><?= htmlspecialchars($tx['description'] ?: 'Sans description') ?></div>
            <small class="text-muted">
                <i class="fas fa-user me-1"></i><?= htmlspecialchars($tx['auteur_prenom'] . ' ' . $tx['auteur_nom']) ?>
                <i class="far fa-calendar-alt ms-2 me-1"></i><?= date('d/m/Y', strtotime($tx['date'])) ?>
            </small>
        </div>
        <div class="text-end">
            <span class="badge <?= $tx['type'] === 'depense' ? 'bg-danger' : 'bg-success' ?> mb-1">
                <?= $tx['type'] === 'depense' ? 'Dépense' : 'Revenu' ?>
            </span>
            <br>
            <span class="fw-bold <?= $tx['type'] === 'depense' ? 'text-danger' : 'text-success' ?>">
                <?= $tx['type'] === 'depense' ? '-' : '+' ?><?= number_format($tx['montant'], 2, ',', ' ') ?> TND
            </span>
        </div>
    </div>
</div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <!-- Ajouter un commentaire -->
                            <div class="detail-card fade-in-up" style="animation-delay: 0.8s">
                                <h5 class="mb-4">
                                    <i class="fas fa-comment-dots me-2" style="color: #667eea"></i>
                                    Commenter une transaction
                                </h5>
                                
                                <form method="POST" action="<?= BASE_URL ?>controllers/BudgetPartageController.php">
                                    <input type="hidden" name="action" value="add_budget_comment">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Transaction</label>
                                        <select name="transaction_id" class="form-select" required>
                                            <option value="">Sélectionner une transaction</option>
                                            <?php foreach ($transactions as $tx): ?>
                                                <option value="<?= $tx['id'] ?>">
                                                    <?= htmlspecialchars($tx['description'] ?: 'Sans description') ?> - <?= date('d/m/Y', strtotime($tx['date'])) ?> (<?= number_format($tx['montant'], 2, ',', ' ') ?> TND)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Votre commentaire</label>
                                        <input type="text" name="contenu" class="form-control" placeholder="Écrivez votre commentaire..." required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary-custom w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Publier le commentaire
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Liste des commentaires -->
                            <?php if (!empty($comments)): ?>
                                <div class="detail-card fade-in-up mt-4" style="animation-delay: 0.9s">
                                    <h5 class="mb-4">
                                        <i class="fas fa-comments me-2" style="color: #667eea"></i>
                                        Commentaires
                                        <span class="badge bg-secondary ms-2"><?= count($comments) ?> commentaires</span>
                                    </h5>
                                    
                                    <?php foreach ($comments as $comment): ?>
                                        <div class="comment-item">
                                            <div class="d-flex justify-content-between mb-2">
                                                <div class="fw-semibold">
                                                    <i class="fas fa-user-circle me-1"></i>
                                                    <?= htmlspecialchars($comment['auteur_prenom'] . ' ' . $comment['auteur_nom']) ?>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="far fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($comment['date_commentaire'])) ?>
                                                </small>
                                            </div>
                                            <p class="mb-2"><?= htmlspecialchars($comment['contenu']) ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-tag me-1"></i>Transaction : <?= htmlspecialchars($comment['transaction_description'] ?: 'Sans description') ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
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

        document.querySelectorAll('.welcome-section, .stat-card, .form-card, .list-card, .detail-card').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>