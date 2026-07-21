<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../models/Categorie.php';

requireLogin();
requireActive();

// Blocage admin
if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: ' . BASE_URL . 'views/dashboard.php');
    exit();
}

$userId = $_SESSION['user_id'];
$pageTitle = 'Catégories';
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$categories = Categorie::getAllByUser($userId);
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editCategory = Categorie::findById($editId, $userId);
    if ($editCategory && $editCategory['id_utilisateur'] === null) $editCategory = null;
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
            content: '🏷️';
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
        
        .category-row {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .category-row:hover {
            background: #f8fafc;
            transform: translateX(5px);
            border-left-color: #667eea;
        }
        
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: inline-block;
            margin-right: 10px;
            vertical-align: middle;
            border: 2px solid #e2e8f0;
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
        
        .badge-depense {
            background: linear-gradient(135deg, #f56565, #ed64a6);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
        }
        
        .badge-revenu {
            background: linear-gradient(135deg, #48bb78, #0d9488);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
        }
        
        .badge-systeme {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
        }
        
        .badge-personnel {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 4px 12px;
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
                            <h2 class="mb-2">🏷️ Mes Catégories</h2>
                            <p class="mb-0 opacity-75">Gérez vos catégories de revenus et dépenses</p>
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
                                <i class="fas fa-tags"></i>
                            </div>
                            <h6 class="text-muted mb-2">Total catégories</h6>
                            <h2 class="mb-0"><?= count($categories) ?></h2>
                            <small class="text-muted mt-2">Catégories disponibles</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.2s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #f5656520, #ed64a620); color: #f56565">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <h6 class="text-muted mb-2">Catégories dépenses</h6>
                            <h2 class="mb-0"><?= count(array_filter($categories, fn($c) => $c['type'] === 'depense')) ?></h2>
                            <small class="text-muted mt-2">Pour suivre vos dépenses</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card fade-in-up" style="animation-delay: 0.3s">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #48bb7820, #0d948820); color: #48bb78">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <h6 class="text-muted mb-2">Catégories revenus</h6>
                            <h2 class="mb-0"><?= count(array_filter($categories, fn($c) => $c['type'] === 'revenu')) ?></h2>
                            <small class="text-muted mt-2">Pour suivre vos revenus</small>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Formulaire Ajouter/Modifier -->
                    <div class="col-lg-5">
                        <div class="form-card fade-in-up" style="animation-delay: 0.4s">
                            <h5 class="mb-4">
                                <i class="fas <?= $editCategory ? 'fa-edit' : 'fa-plus-circle' ?> me-2" style="color: #667eea"></i>
                                <?= $editCategory ? 'Modifier la catégorie' : 'Ajouter une catégorie' ?>
                            </h5>
                            
                            <form method="POST" action="<?= BASE_URL ?>controllers/CategorieController.php">
                                <input type="hidden" name="action" value="<?= $editCategory ? 'update_category' : 'add_category' ?>">
                                <?php if ($editCategory): ?>
                                    <input type="hidden" name="categorie_id" value="<?= $editCategory['id'] ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-tag me-1"></i>Nom de la catégorie
                                    </label>
                                    <input type="text" name="nom" class="form-control" placeholder="Ex: Alimentation, Transport, Salaire..." value="<?= htmlspecialchars($editCategory['nom'] ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-chart-line me-1"></i>Type
                                    </label>
                                    <select name="type" class="form-select" required>
                                        <option value="depense" <?= $editCategory && $editCategory['type'] === 'depense' ? 'selected' : '' ?>>
                                            📉 Dépense
                                        </option>
                                        <option value="revenu" <?= $editCategory && $editCategory['type'] === 'revenu' ? 'selected' : '' ?>>
                                            📈 Revenu
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-palette me-1"></i>Couleur
                                    </label>
                                    <div class="d-flex align-items-center">
                                        <input type="color" name="couleur" class="form-control form-control-color" style="width: 60px; height: 45px; padding: 5px;" value="<?= htmlspecialchars($editCategory['couleur'] ?? '#667eea') ?>">
                                        <span class="ms-3 text-muted">Choisissez une couleur distinctive</span>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary-custom w-100">
                                    <i class="fas <?= $editCategory ? 'fa-save' : 'fa-plus' ?> me-2"></i>
                                    <?= $editCategory ? 'Mettre à jour' : 'Ajouter la catégorie' ?>
                                </button>
                                
                                <?php if ($editCategory): ?>
                                    <div class="text-center mt-3">
                                        <a href="<?= BASE_URL ?>views/categories.php" class="btn btn-outline-custom">
                                            <i class="fas fa-times me-2"></i>Annuler
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Liste des catégories -->
                    <div class="col-lg-7">
                        <div class="list-card fade-in-up" style="animation-delay: 0.5s">
                            <h5 class="mb-4">
                                <i class="fas fa-list me-2" style="color: #667eea"></i>
                                Mes catégories
                                <span class="badge bg-secondary ms-2"><?= count($categories) ?> catégories</span>
                            </h5>
                            
                            <?php if (empty($categories)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Vous n'avez pas encore de catégorie</p>
                                    <small class="text-muted">Créez votre première catégorie pour organiser vos transactions</small>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th><i class="fas fa-tag me-1"></i> Nom</th>
                                                <th><i class="fas fa-chart-line me-1"></i> Type</th>
                                                <th><i class="fas fa-palette me-1"></i> Couleur</th>
                                                <th><i class="fas fa-database me-1"></i> Source</th>
                                                <th><i class="fas fa-cog me-1"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $category): ?>
                                                <tr class="category-row">
                                                    <td class="fw-semibold">
                                                        <?= htmlspecialchars($category['nom']) ?>
                                                     </td>
                                                    <td>
                                                        <span class="badge-<?= $category['type'] === 'depense' ? 'depense' : 'revenu' ?>">
                                                            <?= $category['type'] === 'depense' ? '📉 Dépense' : '📈 Revenu' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="color-preview" style="background-color: <?= htmlspecialchars($category['couleur']) ?>"></div>
                                                            <span><?= htmlspecialchars($category['couleur']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge-<?= $category['id_utilisateur'] === null ? 'systeme' : 'personnel' ?>">
                                                            <?= $category['id_utilisateur'] === null ? '🏢 Système' : '👤 Personnel' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($category['id_utilisateur'] !== null): ?>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="?edit=<?= $category['id'] ?>" class="btn btn-outline-primary" title="Modifier">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form method="POST" action="<?= BASE_URL ?>controllers/CategorieController.php" class="d-inline" onsubmit="return confirm('⚠️ Supprimer cette catégorie ? Les transactions liées ne seront pas supprimées.')">
                                                                    <input type="hidden" name="action" value="delete_category">
                                                                    <input type="hidden" name="categorie_id" value="<?= $category['id'] ?>">
                                                                    <button type="submit" class="btn btn-outline-danger" title="Supprimer">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">
                                                                <i class="fas fa-lock me-1"></i>Prédéfinie
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
                
                <!-- Conseils -->
                <div class="row g-4 mt-4">
                    <div class="col-12">
                        <div class="form-card fade-in-up" style="animation-delay: 0.6s">
                            <h5 class="mb-3">
                                <i class="fas fa-lightbulb me-2" style="color: #f59e0b"></i>
                                Conseils d'organisation
                            </h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center p-3">
                                        <i class="fas fa-utensils fa-2x text-muted mb-2"></i>
                                        <p class="mb-0 small">Créez des catégories pour vos dépenses quotidiennes</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3">
                                        <i class="fas fa-chart-pie fa-2x text-muted mb-2"></i>
                                        <p class="mb-0 small">Utilisez des couleurs pour mieux visualiser vos graphiques</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3">
                                        <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                                        <p class="mb-0 small">Les catégories système ne peuvent pas être modifiées</p>
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