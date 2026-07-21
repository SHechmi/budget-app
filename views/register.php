<?php
// inscription.php - Page d'inscription
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$pageTitle = "Inscription";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Budget App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            width: 100%;
            max-width: 500px;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-header i {
            font-size: 50px;
            margin-bottom: 15px;
        }
        
        .register-header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .register-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .register-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-group label i {
            margin-right: 8px;
            color: #0d9488;
        }
        
        .form-control {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
            outline: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            color: white;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            transform: translateY(-2px);
            color: white;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .login-link a {
            color: #0d9488;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .footer-note {
            text-align: center;
            margin-top: 20px;
            color: #94a3b8;
            font-size: 12px;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .password-requirements i {
            font-size: 10px;
            margin-right: 5px;
        }
        
        @media (max-width: 576px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .register-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-user-plus"></i>
                <h2>💰 Créer un compte</h2>
                <p>Rejoignez Budget App dès maintenant</p>
            </div>
            <div class="register-body">
                <!-- Affichage des messages -->
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>controllers/AuthController.php" id="registerForm">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">
                                <i class="fas fa-user"></i>Nom
                            </label>
                            <input type="text" id="nom" name="nom" class="form-control" 
                                   placeholder="Dupont" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom">
                                <i class="fas fa-user"></i>Prénom
                            </label>
                            <input type="text" id="prenom" name="prenom" class="form-control" 
                                   placeholder="Jean" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>Adresse email
                        </label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="jean.dupont@email.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mot_de_passe">
                            <i class="fas fa-lock"></i>Mot de passe
                        </label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" 
                               class="form-control" placeholder="••••••" required>
                        <div class="password-requirements">
                            <i class="fas fa-info-circle"></i> Minimum 6 caractères
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="mot_de_passe_confirm">
                            <i class="fas fa-lock"></i>Confirmer le mot de passe
                        </label>
                        <input type="password" id="mot_de_passe_confirm" name="mot_de_passe_confirm" 
                               class="form-control" placeholder="••••••" required>
                    </div>
                    
                    <div id="password-error" class="alert alert-danger" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="error-message"></span>
                    </div>
                    
                    <button type="submit" class="btn-register">
                        <i class="fas fa-check-circle me-2"></i>S'inscrire
                    </button>
                </form>
                
                <div class="login-link">
                    <i class="fas fa-sign-in-alt me-1"></i>
                    Déjà un compte ? <a href="login.php">Se connecter</a>
                </div>
            </div>
        </div>
        <div class="footer-note">
            <i class="fas fa-shield-alt me-1"></i> Vos données sont sécurisées
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation côté client
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('mot_de_passe').value;
            const confirm = document.getElementById('mot_de_passe_confirm').value;
            const errorDiv = document.getElementById('password-error');
            const errorMsg = document.getElementById('error-message');
            
            // Vérifier la longueur du mot de passe
            if (password.length < 6) {
                e.preventDefault();
                errorMsg.textContent = 'Le mot de passe doit contenir au moins 6 caractères.';
                errorDiv.style.display = 'block';
                return;
            }
            
            // Vérifier si les mots de passe correspondent
            if (password !== confirm) {
                e.preventDefault();
                errorMsg.textContent = 'Les mots de passe ne correspondent pas.';
                errorDiv.style.display = 'block';
                return;
            }
            
            // Tout est bon, cacher l'erreur
            errorDiv.style.display = 'none';
        });
        
        // Cacher l'erreur quand l'utilisateur commence à taper
        document.getElementById('mot_de_passe').addEventListener('input', function() {
            document.getElementById('password-error').style.display = 'none';
        });
        
        document.getElementById('mot_de_passe_confirm').addEventListener('input', function() {
            document.getElementById('password-error').style.display = 'none';
        });
    </script>
</body>
</html>