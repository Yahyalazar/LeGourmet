<?php
// mes_reservations.php
require_once 'config/database.php';

// Sécurité : Réservé aux clients connectés
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

require_once 'includes/header.php';

try {
    // Récupération UNIQUEMENT des réservations de ce client
    $sql = "
        SELECT r.*, c.heure, c.service, t.numero AS numero_table
        FROM reservations r
        LEFT JOIN creneaux c ON r.creneau_id = c.id
        LEFT JOIN tables_restaurant t ON r.table_id = t.id
        WHERE r.utilisateur_id = :user_id
        ORDER BY r.date_reservation DESC, c.heure DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['utilisateur_id']]);
    $mes_reservations = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<div class="row justify-content-center mb-5">
    <div class="col-md-8">
        <h2 class="mb-4">Mes Réservations</h2>

        <?php if(count($mes_reservations) === 0): ?>
            <div class="card shadow-sm text-center p-5 border-0 bg-light">
                <h4 class="text-muted mb-3">Vous n'avez aucune réservation en cours.</h4>
                <p>Découvrez notre menu et réservez votre table dès maintenant !</p>
                <div>
                    <a href="index.php" class="btn btn-primary btn-lg mt-2">Créer une réservation</a>
                </div>
            </div>

        <?php else: ?>
            <?php foreach($mes_reservations as $res): ?>
                <?php 
                    // Couleurs des badges de statut
                    $badgeClass = 'bg-warning text-dark';
                    $texteStatut = 'En attente de confirmation';
                    
                    if ($res['statut'] === 'confirmee') {
                        $badgeClass = 'bg-success';
                        $texteStatut = 'Confirmée';
                    } elseif ($res['statut'] === 'annulee') {
                        $badgeClass = 'bg-danger';
                        $texteStatut = 'Annulée';
                    }
                ?>

                <div class="card mb-4 shadow-sm border-0" style="overflow: hidden;">
                    <div class="row g-0">
                        <div class="col-md-3 bg-dark text-white d-flex flex-column justify-content-center align-items-center p-3">
                            <h5 class="mb-0"><?= date('d/m/Y', strtotime($res['date_reservation'])) ?></h5>
                            <span class="fs-4 fw-bold text-info"><?= htmlspecialchars($res['heure']) ?></span>
                            <small class="text-light"><?= ucfirst(htmlspecialchars($res['service'])) ?></small>
                        </div>
                        
                        <div class="col-md-9">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title text-primary">Table pour <?= $res['nombre_personnes'] ?> personnes</h5>
                                    <span class="badge rounded-pill <?= $badgeClass ?> fs-6"><?= $texteStatut ?></span>
                                </div>
                                
                                <p class="card-text mt-3 mb-1">
                                    <strong>Code de confirmation :</strong> <code><?= htmlspecialchars($res['code_confirmation']) ?></code>
                                </p>
                                
                                <p class="card-text mb-0">
                                    <strong>Table assignée :</strong> 
                                    <?php if($res['numero_table']): ?>
                                        <span class="badge bg-secondary">Table N° <?= $res['numero_table'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Sera attribuée à votre arrivée</span>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if(!empty($res['commentaires']) && $res['statut'] !== 'en_attente'): ?>
                                    <div class="alert alert-light mt-3 mb-0 p-2 border">
                                        <strong>Message du restaurant :</strong> <?= htmlspecialchars($res['commentaires']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
