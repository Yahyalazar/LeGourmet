<?php
    // index.php
    require_once 'config/database.php';

    // VÉRIFICATION DE SÉCURITÉ : Le client doit être connecté pour réserver
    if (! isset($_SESSION['utilisateur_id'])) {
    // S'il n'est pas connecté, on l'envoie vers la page de connexion avec un message spécifique
    header("Location: login.php?erreur=connexion_requise_reservation");
    exit;
    }

    require_once 'includes/header.php';

    // Récupération des créneaux pour le menu déroulant du formulaire
    // ... (le reste de votre code ne change pas)

    // Récupération des créneaux pour le menu déroulant du formulaire
    try {
    $stmt           = $pdo->query("SELECT id, heure, service FROM creneaux ORDER BY service, heure");
    $liste_creneaux = $stmt->fetchAll();
    } catch (PDOException $e) {
    die("Erreur lors de la récupération des créneaux : " . $e->getMessage());
    }
?>

<?php if (isset($_GET['success']) && $_GET['success'] == 1 && isset($_GET['code'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <h4 class="alert-heading">Réservation confirmée !</h4>
        <p>Votre table a été attribuée avec succès grâce à notre système automatique.</p>
        <hr>
        <p class="mb-0">Votre code de confirmation est : <strong class="fs-4"><?php echo htmlspecialchars($_GET['code']) ?></strong>.</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>

<?php elseif (isset($_GET['error']) && $_GET['error'] == 'full'): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <strong>Désolé !</strong> Il n'y a plus de table disponible pour ce nombre de personnes à ce créneau horaire. Veuillez choisir une autre date ou un autre service.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Réserver une table</h4>
            </div>
            <div class="card-body">
                <form action="traitement_reservation.php" method="POST">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nom_client" class="form-label">Nom complet *</label>
                            <input type="text" class="form-control" id="nom_client" name="nom_client" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telephone" class="form-label">Téléphone *</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="date_reservation" class="form-label">Date *</label>
                            <input type="date" class="form-control" id="date_reservation" name="date_reservation" required min="<?php echo date('Y-m-d') ?>">
                            <?php if (isset($_GET['erreur']) && $_GET['erreur'] === 'date_invalide'): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <strong>Erreur :</strong> Vous ne pouvez pas sélectionner une date dans le passé.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="creneau_id" class="form-label">Heure / Service *</label>
                            <select class="form-select" id="creneau_id" name="creneau_id" required>
                                <option value="">Choisissez un créneau...</option>
                                <?php foreach ($liste_creneaux as $creneau): ?>
                                    <option value="<?php echo $creneau['id'] ?>">
                                        <?php echo htmlspecialchars($creneau['heure']) ?> (Service du <?php echo htmlspecialchars($creneau['service']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="nombre_personnes" class="form-label">Personnes *</label>
                            <input type="number" class="form-control" id="nombre_personnes" name="nombre_personnes" min="1" max="20" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="commentaires" class="form-label">Demandes spéciales (Optionnel)</label>
                        <textarea class="form-control" id="commentaires" name="commentaires" rows="3"></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Confirmer la demande</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
    // Inclusion du pied de page
require_once 'includes/footer.php';
?>