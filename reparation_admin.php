<?php
require_once 'config/database.php';

// On génère le vrai hachage pour "admin123"
$nouveau_mdp_hache = password_hash('admin123', PASSWORD_DEFAULT);

try {
    // On met à jour l'utilisateur existant
    $sql = "UPDATE utilisateurs SET mot_de_passe = :mdp WHERE email = 'admin@legourmet.fr'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':mdp' => $nouveau_mdp_hache]);

    echo "<h2 style='color: green;'>✅ Mot de passe réparé avec succès !</h2>";
    echo "<a href='login.php'>Cliquez ici pour aller vous connecter avec admin123</a>";

} catch(PDOException $e) {
    die("Erreur de mise à jour : " . $e->getMessage());
}
?>