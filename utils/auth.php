<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';

// Redirige si non connecté
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        $_SESSION['error'] = "Veuillez vous connecter.";
        header('Location: ' . BASE_URL . 'views/login.php');
        exit;
    }
}

// Redirige si non admin
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        $_SESSION['error'] = "Accès refusé.";
        header('Location: ' . BASE_URL . 'views/dashboard.php');
        exit;
    }
}

// Vérifie si le compte est actif
function requireActive(): void {
    requireLogin();
    if (empty($_SESSION['actif']) || (int)$_SESSION['actif'] !== 1) {
        session_destroy();
        header('Location: ' . BASE_URL . 'views/login.php?msg=compte_inactif');
        exit;
    }
}