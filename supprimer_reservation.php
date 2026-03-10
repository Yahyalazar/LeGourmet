<?php
// supprimer_reservation.php
require_once 'config/database.php';

// 1. Vérification de la présence de l'ID dans l'URL (GET)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    
    // On force la conversion en entier (int) pour la sécurité
    $id = (int)$_GET['id'];

    try {
        // 2. Préparation de la requête de suppression
        $sql = "DELETE FROM reservations WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        // 3. Exécution de la requête
        $stmt->execute([':id' => $id]);

        // 4. Redirection vers le tableau de bord avec un paramètre de succès
        header("Location: admin.php?msg=deleted");
        exit;

    } catch(PDOException $e) {
        die("Erreur lors de la suppression : " . $e->getMessage());
    }
} else {
    // Si on essaie d'accéder à la page sans ID, on redirige vers l'accueil admin
    header("Location: admin.php");
    exit;
}
?>
