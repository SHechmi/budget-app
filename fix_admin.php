<?php
require_once __DIR__ . '/config/database.php';

$email = 'admin@budget.com';
$new_password = 'admin123';

$hash = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $db = getDB();
    
    // Vérifier si l'utilisateur existe
    $stmt = $db->prepare("SELECT id, email FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Mettre à jour
        $stmt = $db->prepare("UPDATE utilisateurs SET mot_de_passe = ?, actif = 1, role = 'admin' WHERE email = ?");
        $stmt->execute([$hash, $email]);
        echo "✅ Admin mis à jour !<br>";
    } else {
        // Créer
        $stmt = $db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, actif) VALUES (?, ?, ?, ?, 'admin', 1)");
        $stmt->execute(['Admin', 'Budget', $email, $hash]);
        echo "✅ Admin créé !<br>";
    }
    
    echo "Email: " . $email . "<br>";
    echo "Mot de passe: " . $new_password . "<br>";
    echo "Hash: " . $hash . "<br>";
    echo "<hr>";
    
    // Vérification
    $stmt = $db->prepare("SELECT mot_de_passe FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (password_verify($new_password, $user['mot_de_passe'])) {
        echo "<span style='color:green'>✅ Vérification réussie !</span><br>";
        echo "<a href='views/login.php'>Aller à la page de connexion</a>";
    } else {
        echo "<span style='color:red'>❌ Échec de la vérification</span>";
    }
    
} catch(PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>