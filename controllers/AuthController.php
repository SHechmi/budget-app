<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/validation.php';
require_once __DIR__ . '/../utils/helpers.php';

class AuthController {

    public static function register(): void {
        $nom    = cleanInput($_POST['nom'] ?? '');
        $prenom = cleanInput($_POST['prenom'] ?? '');

        // BUG FIX 1: Ne jamais passer l'email par cleanInput/htmlspecialchars
        // car ça peut transformer des caractères et rendre l'email non trouvable en DB
        $email  = strtolower(trim($_POST['email'] ?? ''));
        $mdp    = $_POST['mot_de_passe'] ?? '';
        $mdp2   = $_POST['mot_de_passe_confirm'] ?? '';

        if (!$nom || !$prenom || !$email || !$mdp) {
            $_SESSION['error'] = "Tous les champs sont obligatoires.";
            redirect(BASE_URL . 'views/register.php');
        }
        if (!validEmail($email)) {
            $_SESSION['error'] = "Email invalide.";
            redirect(BASE_URL . 'views/register.php');
        }
        if (strlen($mdp) < 6) {
            $_SESSION['error'] = "Mot de passe trop court (6 caractères min).";
            redirect(BASE_URL . 'views/register.php');
        }
        if ($mdp !== $mdp2) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            redirect(BASE_URL . 'views/register.php');
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Cet email est déjà utilisé.";
            redirect(BASE_URL . 'views/register.php');
        }

        $countStmt = $db->query("SELECT COUNT(*) FROM utilisateurs");
        $isFirstUser = ((int)$countStmt->fetchColumn() === 0);
        $role  = $isFirstUser ? 'admin' : 'user';
        $actif = $isFirstUser ? 1 : 0;
        $hash  = password_hash($mdp, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, actif) VALUES (?, ?, ?, ?, ?, ?)");
        $ok = $stmt->execute([$nom, $prenom, $email, $hash, $role, $actif]);

        if ($ok) {
            $_SESSION['success'] = "Compte créé ! " . ($isFirstUser ? "Vous êtes administrateur." : "En attente de validation par un administrateur.");
            redirect(BASE_URL . 'views/login.php');
        } else {
            $_SESSION['error'] = "Erreur lors de la création du compte.";
            redirect(BASE_URL . 'views/register.php');
        }
    }

    public static function login(): void {
        // BUG FIX 1: Ne jamais passer l'email par cleanInput/htmlspecialchars
        // cleanInput() appelle htmlspecialchars() qui peut modifier l'email
        // et le rendre différent de celui stocké en base de données
        $email = strtolower(trim($_POST['email'] ?? ''));
        $mdp   = $_POST['mot_de_passe'] ?? '';

        if (!$email || !$mdp) {
            $_SESSION['error'] = "Email et mot de passe requis.";
            redirect(BASE_URL . 'views/login.php');
        }

        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error'] = "Email ou mot de passe incorrect.";
            redirect(BASE_URL . 'views/login.php');
        }

        if (!password_verify($mdp, $user['mot_de_passe'])) {
            $_SESSION['error'] = "Email ou mot de passe incorrect.";
            redirect(BASE_URL . 'views/login.php');
        }

        if ((int)$user['actif'] !== 1) {
            $_SESSION['error'] = "Compte en attente de validation ou suspendu.";
            redirect(BASE_URL . 'views/login.php');
        }

        // BUG FIX 2: Régénérer l'ID de session après login (sécurité anti-fixation)
        session_regenerate_id(true);

        $_SESSION['user_id']  = $user['id'];
        $_SESSION['nom']      = $user['nom'];
        $_SESSION['prenom']   = $user['prenom'];
        $_SESSION['email']    = $user['email'];
        $_SESSION['role']     = $user['role'];
        $_SESSION['actif']    = $user['actif'];
        $_SESSION['username'] = trim($user['prenom'] . ' ' . $user['nom']);

        // BUG FIX 3: Utiliser BASE_URL pour la redirection (chemin relatif "../views/" cassait selon l'emplacement)
        redirect(BASE_URL . 'views/dashboard.php');
    }

    public static function logout(): void {
        session_destroy();
        redirect(BASE_URL . 'views/login.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'register') AuthController::register();
    if ($action === 'login')    AuthController::login();
}
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    AuthController::logout();
}
?>