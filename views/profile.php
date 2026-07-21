<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/auth.php';

requireLogin();
requireActive();

$db = getDB();
$userId = $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

$stmt = $db->prepare("SELECT id, nom, prenom, email, role, actif, date_inscription FROM utilisateurs WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement modification profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profil') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    $errors = [];
    if (empty($nom)) $errors[] = "Le nom est obligatoire";
    if (empty($prenom)) $errors[] = "Le prénom est obligatoire";
    if (empty($email)) $errors[] = "L'email est obligatoire";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
    
    $stmtCheck = $db->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
    $stmtCheck->execute([$email, $userId]);
    if ($stmtCheck->fetch()) $errors[] = "Cet email est déjà utilisé";
    
    if (empty($errors)) {
        $stmtUpdate = $db->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id = ?");
        if ($stmtUpdate->execute([$nom, $prenom, $email, $userId])) {
            $_SESSION['success'] = "Profil mis à jour avec succès";
            $_SESSION['nom'] = $nom;
            $_SESSION['prenom'] = $prenom;
            $_SESSION['email'] = $email;
            $_SESSION['username'] = $prenom . " " . $nom;
            $user['nom'] = $nom;
            $user['prenom'] = $prenom;
            $user['email'] = $email;
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour";
        }
    } else {
        $_SESSION['error'] = implode(", ", $errors);
    }
    header("Location: profile.php");
    exit();
}

// Traitement changement mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $ancien_mdp = $_POST['ancien_mdp'] ?? '';
    $nouveau_mdp = $_POST['nouveau_mdp'] ?? '';
    $confirm_mdp = $_POST['confirm_mdp'] ?? '';
    
    $errors = [];
    if (empty($ancien_mdp)) $errors[] = "L'ancien mot de passe est obligatoire";
    if (strlen($nouveau_mdp) < 6) $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères";
    if ($nouveau_mdp !== $confirm_mdp) $errors[] = "Les mots de passe ne correspondent pas";
    
    $stmtCheck = $db->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
    $stmtCheck->execute([$userId]);
    $userData = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if (!password_verify($ancien_mdp, $userData['mot_de_passe'])) $errors[] = "L'ancien mot de passe est incorrect";
    
    if (empty($errors)) {
        $nouveau_mdp_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
        $stmtUpdate = $db->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
        if ($stmtUpdate->execute([$nouveau_mdp_hash, $userId])) {
            $_SESSION['success'] = "Mot de passe changé avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors du changement";
        }
    } else {
        $_SESSION['error'] = implode(", ", $errors);
    }
    header("Location: profile.php");
    exit();
}

$stmtAlertes = $db->prepare("SELECT COUNT(*) FROM alertes WHERE id_utilisateur = ? AND lu = 0");
$stmtAlertes->execute([$userId]);
$unreadAlertes = $stmtAlertes->fetchColumn();

$pageTitle = "Mon Profil";
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
        
        .profile-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 25px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '👤';
            position: absolute;
            bottom: -30px;
            right: -30px;
            font-size: 150px;
            opacity: 0.1;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 20px;
        }
        
        .profile-card {
            background: white;
            border: none;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: #4a5568;
        }
        
        .info-value {
            color: #2d3748;
            font-weight: 500;
        }
        
        .badge-role {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
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
        
        .form-control {
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #64748b, #475569);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
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
                <div class="profile-header fade-in-up">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="profile-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <h2 class="mb-2">Mon Profil</h2>
                            <p class="mb-0 opacity-75">Gérez vos informations personnelles et votre sécurité</p>
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

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Informations personnelles -->
                    <div class="col-md-6">
                        <div class="profile-card fade-in-up" style="animation-delay: 0.1s">
                            <h5 class="mb-4">
                                <i class="fas fa-info-circle me-2" style="color: #667eea"></i>
                                Mes informations
                            </h5>
                            
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-user me-2"></i>Nom complet</span>
                                <span class="info-value"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-envelope me-2"></i>Email</span>
                                <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-tag me-2"></i>Rôle</span>
                                <span class="info-value">
                                    <span class="badge-role <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                        <i class="fas <?= $user['role'] === 'admin' ? 'fa-crown' : 'fa-user' ?> me-1"></i>
                                        <?= $user['role'] === 'admin' ? 'Administrateur' : 'Utilisateur' ?>
                                    </span>
                                </span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-check-circle me-2"></i>Statut</span>
                                <span class="info-value">
                                    <span class="badge-role <?= $user['actif'] ? 'badge-active' : 'badge-inactive' ?>">
                                        <i class="fas <?= $user['actif'] ? 'fa-check-circle' : 'fa-clock' ?> me-1"></i>
                                        <?= $user['actif'] ? 'Compte activé' : 'En attente' ?>
                                    </span>
                                </span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label"><i class="fas fa-calendar-alt me-2"></i>Date d'inscription</span>
                                <span class="info-value"><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Modifier informations -->
                    <div class="col-md-6">
                        <div class="profile-card fade-in-up" style="animation-delay: 0.2s">
                            <h5 class="mb-4">
                                <i class="fas fa-edit me-2" style="color: #667eea"></i>
                                Modifier mes informations
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_profil">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Prénom</label>
                                        <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Nom</label>
                                        <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($user['nom']) ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Adresse email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                </button>
                            </form>
                        </div>

                        <!-- Changer mot de passe -->
                        <div class="profile-card fade-in-up mt-4" style="animation-delay: 0.3s">
                            <h5 class="mb-4">
                                <i class="fas fa-key me-2" style="color: #667eea"></i>
                                Sécurité
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Mot de passe actuel</label>
                                    <input type="password" name="ancien_mdp" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nouveau mot de passe</label>
                                    <input type="password" name="nouveau_mdp" class="form-control" required>
                                    <small class="text-muted">Minimum 6 caractères</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Confirmer le nouveau mot de passe</label>
                                    <input type="password" name="confirm_mdp" class="form-control" required>
                                </div>
                                
                                <button type="submit" class="btn btn-secondary w-100">
                                    <i class="fas fa-sync-alt me-2"></i>Changer le mot de passe
                                </button>
                            </form>
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

        document.querySelectorAll('.profile-header, .profile-card, .welcome-section').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>