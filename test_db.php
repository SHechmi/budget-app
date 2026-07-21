<?php
require_once __DIR__ . '/config/database.php';

$db = getDB();
$stmt = $db->query("SELECT id, email, actif, LEFT(mot_de_passe, 20) as pwd_preview FROM utilisateurs");
echo "<h2>Utilisateurs dans la base :</h2>";
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']} - Email: {$row['email']} - Actif: {$row['actif']} - Hash: {$row['pwd_preview']}...<br>";
}

echo "<hr>";
$test_email = 'admin@budget.com';
$stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = ?");
$stmt->execute([$test_email]);
$user = $stmt->fetch();

if ($user) {
    echo "Test avec admin@budget.com<br>";
    echo "Hash complet: " . $user['mot_de_passe'] . "<br>";
    echo "Vérification 'admin123': " . (password_verify('admin123', $user['mot_de_passe']) ? "OK" : "ECHEC");
}
?>