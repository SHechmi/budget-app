<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/Budget.php';

requireLogin();
requireActive();

// Blocage admin
if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit();
}

$userId = $_SESSION['user_id'];
$pageTitle = 'Transactions';
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$categories = Categorie::getAllByUser($userId);
$budgets = Budget::getAllByUser($userId);
$transactions = Transaction::getAllByUser($userId);
$editTransaction = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editTransaction = Transaction::getById($editId, $userId);
}
$fullName = trim($_SESSION['prenom'] . ' ' . $_SESSION['nom']);

// Calcul des totaux
$totalRevenus = Transaction::getTotalByType($userId, 'revenu');
$totalDepenses = Transaction::getTotalByType($userId, 'depense');
$solde = $totalRevenus - $totalDepenses;
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
        
        .transaction-row {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .transaction-row:hover {
            background: #f8fafc;
            transform: translateX(5px);
            border-left-color: #667eea;
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
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .badge-revenu {
            background: linear-gradient(135deg, #48bb78, #0d9488);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
        }
        
        .badge-depense {
            background: linear-gradient(135deg, #f56565, #ed64a6);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
        }
        
        .category-badge {
            background: #e2e8f0;
            color: #4a5568;
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
        
        .amount-positive {
            color: #48bb78;
            font-weight: 600;
        }
        
        .amount-negative {
            color: #f56565;
            font-weight: 600;
        }
        
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
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
                            <h2 class="mb-2">📊 Mes Transactions</h2>
                            <p class="mb-0 opacity-75">Gérez vos revenus et dépenses quotidiennes</p>
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
                            <div class="stat-icon" style="background: linear-gradient(135deg, #48bb7820, #0d948820); color: #48bb78">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <h6 class="text-muted mb-2">Total revenus</h6>
                            <h2 class="mb-0 text-success"><?= number_format($totalRevenus, 2, ',', ' ') ?> TND</h2>
                            <small class="text-muted mt-2">Tous revenus confondus</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.2s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #f5656520, #ed64a620); color: #f56565">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <h6 class="text-muted mb-2">Total dépenses</h6>
                            <h2 class="mb-0 text-danger"><?= number_format($totalDepenses, 2, ',', ' ') ?> TND</h2>
                            <small class="text-muted mt-2">Toutes dépenses confondues</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.3s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220); color: #667eea">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <h6 class="text-muted mb-2">Solde total</h6>
                            <h2 class="mb-0 <?= $solde >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($solde, 2, ',', ' ') ?> TND</h2>
                            <small class="text-muted mt-2">Revenus - Dépenses</small>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Formulaire Ajouter/Modifier -->
                    <div class="col-lg-5">
                        <div class="form-card fade-in-up" style="animation-delay: 0.4s">
                            <h5 class="mb-4">
                                <i class="fas <?= $editTransaction ? 'fa-edit' : 'fa-plus-circle' ?> me-2" style="color: #667eea"></i>
                                <?= $editTransaction ? 'Modifier la transaction' : 'Ajouter une transaction' ?>
                            </h5>
                            
                            <form method="POST" action="<?= BASE_URL ?>controllers/TransactionController.php">
                                <input type="hidden" name="action" value="<?= $editTransaction ? 'update_transaction' : 'add_transaction' ?>">
                                <?php if ($editTransaction): ?>
                                    <input type="hidden" name="transaction_id" value="<?= $editTransaction['id'] ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-chart-line me-1"></i>Type
                                    </label>
                                    <select name="type" class="form-select" required>
                                        <option value="revenu" <?= $editTransaction && $editTransaction['type'] === 'revenu' ? 'selected' : '' ?>>
                                            📈 Revenu
                                        </option>
                                        <option value="depense" <?= $editTransaction && $editTransaction['type'] === 'depense' ? 'selected' : '' ?>>
                                            📉 Dépense
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-coins me-1"></i>Montant (TND)
                                    </label>
                                    <input type="number" step="0.01" name="montant" class="form-control" placeholder="0.00" value="<?= htmlspecialchars($editTransaction['montant'] ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-calendar-alt me-1"></i>Date
                                    </label>
                                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($editTransaction['date'] ?? date('Y-m-d')) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-tag me-1"></i>Catégorie
                                    </label>
                                    <select name="categorie" class="form-select">
                                        <option value="">Aucune catégorie</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" <?= $editTransaction && $category['id'] == $editTransaction['id_categorie'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-wallet me-1"></i>Budget associé
                                    </label>
                                    <select name="budget" class="form-select">
                                        <option value="">Aucun budget</option>
                                        <?php foreach ($budgets as $budget): ?>
                                            <option value="<?= $budget['id'] ?>" <?= $editTransaction && $budget['id'] == $editTransaction['id_budget'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($budget['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-pen me-1"></i>Description (optionnel)
                                    </label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="Description de la transaction..."><?= htmlspecialchars($editTransaction['description'] ?? '') ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary-custom w-100">
                                    <i class="fas <?= $editTransaction ? 'fa-save' : 'fa-plus' ?> me-2"></i>
                                    <?= $editTransaction ? 'Mettre à jour' : 'Ajouter la transaction' ?>
                                </button>
                                
                                <?php if ($editTransaction): ?>
                                    <div class="text-center mt-3">
                                        <a href="<?= BASE_URL ?>views/transactions.php" class="btn btn-outline-custom">
                                            <i class="fas fa-times me-2"></i>Annuler
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Liste des transactions -->
                    <div class="col-lg-7">
                        <div class="list-card fade-in-up" style="animation-delay: 0.5s">
                            <h5 class="mb-4">
                                <i class="fas fa-list me-2" style="color: #667eea"></i>
                                Liste des transactions
                                <span class="badge bg-secondary ms-2"><?= count($transactions) ?> transactions</span>
                            </h5>
                            
                            <?php if (empty($transactions)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucune transaction enregistrée</p>
                                    <small class="text-muted">Ajoutez votre première transaction ci-contre</small>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th><i class="fas fa-pen me-1"></i> Description</th>
                                                <th><i class="fas fa-tag me-1"></i> Catégorie</th>
                                                <th><i class="fas fa-calendar me-1"></i> Date</th>
                                                <th><i class="fas fa-coins me-1"></i> Montant</th>
                                                <th><i class="fas fa-cog me-1"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transactions as $transaction): ?>
                                                <tr class="transaction-row">
                                                    <td class="fw-semibold">
                                                        <?= htmlspecialchars($transaction['description'] ?: '—') ?>
                                                        <?php if ($transaction['budget_nom']): ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-wallet me-1"></i><?= htmlspecialchars($transaction['budget_nom']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($transaction['categorie_nom']): ?>
                                                            <span class="category-badge">
                                                                <i class="fas fa-tag me-1"></i><?= htmlspecialchars($transaction['categorie_nom']) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= date('d/m/Y', strtotime($transaction['date'])) ?>
                                                        <br>
                                                        <small class="text-muted"><?= date('H:i', strtotime($transaction['date'])) ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge-<?= $transaction['type'] === 'depense' ? 'depense' : 'revenu' ?> mb-1">
                                                            <?= $transaction['type'] === 'depense' ? '📉 Dépense' : '📈 Revenu' ?>
                                                        </span>
                                                        <br>
                                                        <span class="<?= $transaction['type'] === 'depense' ? 'amount-negative' : 'amount-positive' ?> fs-5">
                                                            <?= $transaction['type'] === 'depense' ? '-' : '+' ?><?= number_format($transaction['montant'], 2, ',', ' ') ?> TND
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="?edit=<?= $transaction['id'] ?>" class="btn btn-outline-primary" title="Modifier">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form method="POST" action="<?= BASE_URL ?>controllers/TransactionController.php" class="d-inline" onsubmit="return confirm('⚠️ Supprimer cette transaction ? Cette action est irréversible.')">
                                                                <input type="hidden" name="action" value="delete_transaction">
                                                                <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                                                                <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        </div>
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
                
                <!-- Conseils -->
                <div class="row g-4 mt-4">
                    <div class="col-12">
                        <div class="form-card fade-in-up" style="animation-delay: 0.6s">
                            <h5 class="mb-3">
                                <i class="fas fa-lightbulb me-2" style="color: #f59e0b"></i>
                                Conseils pour mieux gérer vos transactions
                            </h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center p-3">
                                        <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                                        <p class="mb-0 small">Utilisez des catégories pour mieux analyser vos dépenses</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3">
                                        <i class="fas fa-wallet fa-2x text-muted mb-2"></i>
                                        <p class="mb-0 small">Associez vos transactions à des budgets pour suivre vos limites</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3">
                                        <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                        <p class="mb-0 small">Ajoutez une description pour garder une trace détaillée</p>
                                    </div>
                                </div>
                            </div>
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
    </script>
</body>
</html>