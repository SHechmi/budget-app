<?php
/**
 * Configuration de la base de données - MangaAnime
 *
 * Ce fichier contient la configuration de connexion à la base de données MySQL.
 * Modifiez les paramètres selon votre environnement.
 */

// Paramètres de connexion
$host = 'localhost';         // Adresse du serveur MySQL
$dbname = 'budget_app';     // Nom de la base de données
$username = 'root';          // Nom d'utilisateur MySQL
$password = '';              // Mot de passe MySQL (vide par défaut sur WAMP)

try {
    // Créer la connexion PDO
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        )
    );

} catch(PDOException $e) {
    // Afficher l'erreur en cas d'échec
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Démarrer la session si elle n'est pas démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>