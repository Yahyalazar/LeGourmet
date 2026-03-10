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
    // Vérifier si la colonne utilisateur_id existe, sinon l'ajouter automatiquement
    $columns = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'utilisateur_id'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE reservations ADD COLUMN utilisateur_id INT(11) DEFAULT NULL");
    }
    // Vérifier si la colonne email existe
    $colEmail = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'email'")->fetchAll();
    if (empty($colEmail)) {
        $pdo->exec("ALTER TABLE reservations ADD COLUMN email VARCHAR(150) NOT NULL DEFAULT ''");
    }

    $sql = "
        SELECT r.*, c.heure, c.service, t.numero AS numero_table, t.zone AS zone_table
        FROM reservations r
        LEFT JOIN creneaux c ON r.creneau_id = c.id
        LEFT JOIN tables_restaurant t ON r.table_id = t.id
        WHERE r.utilisateur_id = :uid
        ORDER BY r.date_reservation DESC, c.heure DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $_SESSION['utilisateur_id']]);
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }

$mois = ['','Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];

$total     = count($reservations);
$confirmee = count(array_filter($reservations, fn($r) => $r['statut'] === 'confirmee'));
$attente   = count(array_filter($reservations, fn($r) => $r['statut'] === 'en_attente'));
?>

<div class="page-hero reveal">
  <span class="eyebrow">Espace personnel</span>
  <h1>Mes <em>réservations</em></h1>
  <div class="divider"></div>
</div>

<div class="row justify-content-center">
<div class="col-xl-9 col-lg-11">

<?php if ($total === 0): ?>
  <div class="card no-hover reveal">
    <div class="card-body empty-state">
      <span class="empty-icon">🍽</span>
      <h4>Aucune réservation</h4>
      <p>Vous n'avez encore rien réservé. Découvrez notre menu et faites votre première réservation.</p>
      <a href="index.php" class="btn btn-primary btn-lg mt-3">
        <i class="bi bi-calendar-plus me-2"></i>Réserver une table
      </a>
    </div>
  </div>

<?php else: ?>

  <!-- Mini stats -->
  <div class="d-flex gap-3 mb-4 flex-wrap reveal">
    <div style="font-family:var(--ff-ui);font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted)">
      <span style="color:var(--gold);font-family:var(--ff-display);font-size:1.3rem;font-weight:400"><?= $total ?></span> &nbsp;réservation<?= $total>1?'s':'' ?>
    </div>
    <?php if ($confirmee): ?>
    <div style="font-family:var(--ff-ui);font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted)">
      <span style="color:#76D7A0;font-family:var(--ff-display);font-size:1.3rem;font-weight:400"><?= $confirmee ?></span> &nbsp;confirmée<?= $confirmee>1?'s':'' ?>
    </div>
    <?php endif; ?>
    <?php if ($attente): ?>
    <div style="font-family:var(--ff-ui);font-size:.6rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted)">
      <span style="color:#FAD7A0;font-family:var(--ff-display);font-size:1.3rem;font-weight:400"><?= $attente ?></span> &nbsp;en attente
    </div>
    <?php endif; ?>
  </div>

  <?php foreach ($reservations as $i => $res):
    $ts = strtotime($res['date_reservation']);
    $stClass = match($res['statut']) { 'confirmee'=>'st-confirmee', 'annulee'=>'st-annulee', default=>'' };
    $bdgCls  = match($res['statut']) { 'confirmee'=>'bg-success', 'annulee'=>'bg-danger', default=>'bg-warning' };
    $bdgLbl  = match($res['statut']) { 'confirmee'=>'Confirmée', 'annulee'=>'Annulée', default=>'En attente' };
    $bdgIcon = match($res['statut']) { 'confirmee'=>'check-circle', 'annulee'=>'x-circle', default=>'clock' };
  ?>
  <div class="card res-card <?= $stClass ?> reveal mb-4" style="transition-delay:<?= $i * 0.07 ?>s">
    <div class="row g-0">
      <!-- Date panel -->
      <div class="col-3 col-md-2 res-date-panel" style="min-width:100px">
        <div class="res-day"><?= date('d', $ts) ?></div>
        <div class="res-month"><?= $mois[(int)date('n', $ts)] ?> <?= date('Y', $ts) ?></div>
        <div class="res-time"><?= htmlspecialchars($res['heure'] ?? '--:--') ?></div>
        <div class="res-svc"><?= ucfirst(htmlspecialchars($res['service'] ?? '')) ?></div>
      </div>
      <!-- Info -->
      <div class="col">
        <div class="card-body res-info">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <h5 class="mb-0">
              <i class="bi bi-people me-1" style="font-size:.85em;color:var(--gold)"></i>
              <?= $res['nombre_personnes'] ?> convive<?= $res['nombre_personnes'] > 1 ? 's' : '' ?>
            </h5>
            <span class="badge <?= $bdgCls ?>">
              <i class="bi bi-<?= $bdgIcon ?> me-1"></i><?= $bdgLbl ?>
            </span>
          </div>

          <div class="d-flex flex-wrap gap-4" style="margin-top:.6rem">
            <div>
              <div style="font-family:var(--ff-ui);font-size:.58rem;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:.2rem">Code</div>
              <code class="confirm-code"><?= htmlspecialchars($res['code_confirmation']) ?></code>
            </div>
            <div>
              <div style="font-family:var(--ff-ui);font-size:.58rem;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:.2rem">Table</div>
              <?php if ($res['numero_table']): ?>
                <span class="badge bg-secondary">
                  N°<?= $res['numero_table'] ?>
                  <?php if ($res['zone_table']): ?> — <?= htmlspecialchars($res['zone_table']) ?><?php endif; ?>
                </span>
              <?php else: ?>
                <span style="color:var(--muted);font-style:italic;font-size:.9rem">Attribuée à l'arrivée</span>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!empty($res['commentaires']) && $res['statut'] !== 'en_attente'): ?>
            <div class="alert alert-info mt-3 mb-0 p-2 d-flex gap-2">
              <i class="bi bi-chat-left-text" style="color:var(--gold);flex-shrink:0;margin-top:.15rem"></i>
              <div>
                <div style="font-family:var(--ff-ui);font-size:.58rem;letter-spacing:.15em;text-transform:uppercase;color:var(--gold);margin-bottom:.2rem">Message du restaurant</div>
                <?= htmlspecialchars($res['commentaires']) ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="text-center mt-3 reveal">
    <a href="index.php" class="btn btn-outline-primary">
      <i class="bi bi-calendar-plus me-2"></i>Nouvelle réservation
    </a>
  </div>

<?php endif; ?>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>
