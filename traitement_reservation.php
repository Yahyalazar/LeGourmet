<?php
// traitement_reservation.php
require_once 'config/database.php';

// Sécurité : On vérifie que l'utilisateur est bien connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php?erreur=connexion_requise");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Récupération des données du formulaire et de la session
    $utilisateur_id = $_SESSION['utilisateur_id']; // L'ID du client connecté
    $nom_client = htmlspecialchars($_POST['nom_client']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $date_reservation = $_POST['date_reservation'];
    $nombre_personnes = (int)$_POST['nombre_personnes'];
    $creneau_id = (int)$_POST['creneau_id'];
    $commentaires = htmlspecialchars($_POST['commentaires'] ?? '');
    $date_reservation = $_POST['date_reservation'];

    // --- NOUVEAU BLOC : VALIDATION DE LA DATE ---
    $date_aujourdhui = date('Y-m-d'); // Récupère la date du jour
    if ($date_reservation < $date_aujourdhui) {
        // Si la date choisie est avant aujourd'hui, on bloque et on renvoie une erreur
        header("Location: index.php?erreur=date_invalide");
        exit;
    }
    
    // 2. Génération d'un code de confirmation unique (ex: RES-4A8B2C)
    $code_confirmation = strtoupper(substr(uniqid('RES-'), 0, 10));

    try {
        // 3. Préparation de la requête SQL (C'est ici que l'erreur se trouvait)
        $sql_insert = "INSERT INTO reservations (utilisateur_id, nom_client, telephone, date_reservation, nombre_personnes, creneau_id, statut, code_confirmation, commentaires) 
                VALUES (:utilisateur_id, :nom, :tel, :date_res, :personnes, :creneau, 'en_attente', :code, :commentaires)";

        $stmt = $pdo->prepare($sql_insert);
        
        // 4. Exécution de la requête avec les paramètres sécurisés
        $stmt->execute([
            ':utilisateur_id' => $utilisateur_id,
            ':nom' => $nom_client,
            ':tel' => $telephone,
            ':date_res' => $date_reservation,
            ':personnes' => $nombre_personnes,
            ':creneau' => $creneau_id,
            ':code' => $code_confirmation,
            ':commentaires' => $commentaires
        ]);

        // 5. Redirection vers l'accueil avec un message de succès
        header("Location: index.php?success=1&code=" . $code_confirmation);
        exit;

    } catch(PDOException $e) {
        die("Erreur lors de la réservation : " . $e->getMessage());
    }
} else {
    // Si on accède à la page sans soumettre le formulaire
    header("Location: index.php");
    exit;
}
?>
