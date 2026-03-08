<?php
// admin.php
require_once 'config/database.php';

// 2. VÉRIFICATION DE SÉCURITÉ (Redirection vers login.php)
// Si la variable de session 'role' n'existe pas, OU si elle est différente de 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // On redirige immédiatement vers la page de connexion
    header("Location: login.php?erreur=connexion_requise");
    exit; // On stoppe l'exécution du reste de la page
}

require_once 'includes/header.php';
// Requête SQL avec JOIN pour récupérer toutes les infos utiles
try {
    $sql = "
        SELECT 
            r.id, r.nom_client, r.telephone, r.date_reservation, r.nombre_personnes, r.statut, r.code_confirmation,
            c.heure, c.service,
            t.numero AS numero_table
        FROM reservations r
        LEFT JOIN creneaux c ON r.creneau_id = c.id
        LEFT JOIN tables_restaurant t ON r.table_id = t.id
        ORDER BY r.date_reservation DESC, c.heure ASC
    ";
    $stmt = $pdo->query($sql);
    $reservations = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Erreur de récupération des données : " . $e->getMessage());
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>Tableau de bord Administrateur</h2>
        <p class="text-muted">Gestion des réservations en temps réel.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="admin.php" class="btn btn-outline-primary">Actualiser les données</a>
    </div>
</div>

<div class="row">
    <?php if(count($reservations) > 0): ?>
        <?php foreach($reservations as $res): ?>
            <?php 
                // Gestion des couleurs des badges et bordures selon le statut
                $badgeClass = 'bg-secondary';
                $cardBorder = '';
                if ($res['statut'] === 'confirmee') { 
                    $badgeClass = 'bg-success'; 
                    $cardBorder = 'border-success'; 
                } elseif ($res['statut'] === 'en_attente') { 
                    $badgeClass = 'bg-warning text-dark'; 
                    $cardBorder = 'border-warning'; 
                } elseif ($res['statut'] === 'annulee') { 
                    $badgeClass = 'bg-danger'; 
                    $cardBorder = 'border-danger'; 
                }
            ?>
            
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100 <?= $cardBorder ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?= date('d/m/Y', strtotime($res['date_reservation'])) ?> à <?= htmlspecialchars($res['heure']) ?></strong>
                        <span class="badge <?= $badgeClass ?>"><?= ucfirst(str_replace('_', ' ', $res['statut'])) ?></span>
                    </div>
                    
                    <div class="card-body">
                        <h5 class="card-title text-primary"><?= htmlspecialchars($res['nom_client']) ?></h5>
                        <p class="card-text mb-1">📞 <?= htmlspecialchars($res['telephone']) ?></p>
                        <p class="card-text mb-1">👥 <?= $res['nombre_personnes'] ?> personnes</p>
                        <p class="card-text mb-1">
                            🪑 Table : 
                            <?php if($res['numero_table']): ?>
                                <strong>N° <?= $res['numero_table'] ?></strong>
                            <?php else: ?>
                                <span class="text-warning">Non assignée</span>
                            <?php endif; ?>
                        </p>
                        <hr>
                        <p class="card-text mb-0"><small class="text-muted">Code : <strong><?= htmlspecialchars($res['code_confirmation']) ?></strong></small></p>
                    </div>
                    
                    <div class="card-footer bg-white d-flex justify-content-between">
                        <a href="editer_reservation.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                        
                        <a href="supprimer_reservation.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ? Cette action est irréversible.');">Supprimer</a>
                    </div>
                </div>
            </div>
            
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info text-center shadow-sm">
                Aucune réservation pour le moment.
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // Rafraîchissement toutes les 30 secondes
    setTimeout(function() {
        window.location.reload();
    }, 30000);
</script>

<?php
require_once 'includes/footer.php';
?>