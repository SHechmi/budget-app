<?php
// ==============================================
// CONNEXION PDO MYSQL
// ==============================================

$host = 'localhost';
$dbname = 'budget_app';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Fournit une fonction utilitaire pour récupérer l'instance PDO
function getDB(): PDO
{
    global $pdo;
    return $pdo;
}
?>