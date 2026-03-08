<?php
// Inclusion de la connexion à la base de données
require_once 'config/database.php';

// Vérification que le formulaire a bien été soumis via la méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Récupération et sécurisation des données du formulaire
    $nom_client = htmlspecialchars($_POST['nom_client']);
    $email = htmlspecialchars($_POST['email']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $date_reservation = $_POST['date_reservation'];
    $creneau_id = (int)$_POST['creneau_id'];
    $nombre_personnes = (int)$_POST['nombre_personnes'];
    $commentaires = htmlspecialchars($_POST['commentaires']);

    try {
        // 2. ALGORITHME D'ATTRIBUTION AUTOMATIQUE DES TABLES 
        // On cherche une table dont la capacité est suffisante et qui n'est pas déjà réservée
        $sql_recherche_table = "
            SELECT id FROM tables_restaurant 
            WHERE capacite >= :nombre_personnes 
            AND id NOT IN (
                SELECT table_id FROM reservations 
                WHERE date_reservation = :date_reservation 
                AND creneau_id = :creneau_id 
                AND statut IN ('en_attente', 'confirmee')
                AND table_id IS NOT NULL
            )
            ORDER BY capacite ASC -- On prend la table la plus petite possible pour optimiser l'espace
            LIMIT 1
        ";
        
        $stmt_table = $pdo->prepare($sql_recherche_table);
        $stmt_table->execute([
            ':nombre_personnes' => $nombre_personnes,
            ':date_reservation' => $date_reservation,
            ':creneau_id' => $creneau_id
        ]);
        
        $table_disponible = $stmt_table->fetch();
        
        if ($table_disponible) {
            // Une table est libre ! On la sélectionne.
            $table_id = $table_disponible['id'];
            $statut = 'confirmee'; 
            
            // Génération d'un code de confirmation aléatoire (ex: 8A4F9B) [cite: 36]
            $code_confirmation = strtoupper(substr(uniqid(), -6));
            
            // 3. Insertion de la réservation dans la base de données [cite: 17-32]
            $sql_insert = "
                INSERT INTO reservations 
                (nom_client, email, telephone, date_reservation, creneau_id, table_id, nombre_personnes, commentaires, statut, code_confirmation) 
                VALUES 
                (:nom, :email, :tel, :date_res, :creneau, :table, :nb_pers, :comms, :statut, :code)
            ";
            
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                ':nom' => $nom_client,
                ':email' => $email,
                ':tel' => $telephone,
                ':date_res' => $date_reservation,
                ':creneau' => $creneau_id,
                ':table' => $table_id,
                ':nb_pers' => $nombre_personnes,
                ':comms' => $commentaires,
                ':statut' => $statut,
                ':code' => $code_confirmation
            ]);
            
            // Redirection vers l'accueil avec un message de succès (à gérer dans index.php)
            header("Location: index.php?success=1&code=" . $code_confirmation);
            exit;
            
        } else {
            // 4. BLOCAGE DES CRÉNEAUX COMPLETS 
            // Aucune table n'a la capacité requise ou toutes sont prises
            header("Location: index.php?error=full");
            exit;
        }

    } catch(PDOException $e) {
        die("Erreur lors du traitement de la réservation : " . $e->getMessage());
    }
} else {
    // Si on essaie d'accéder au fichier sans valider le formulaire, on renvoie à l'accueil
    header("Location: index.php");
    exit;
}
?>