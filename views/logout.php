<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';

// Détruire la session
session_unset();
session_destroy();

// Rediriger vers la page de connexion
header("Location: " . BASE_URL . "views/login.php");
exit;
?>