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
    // Ajoute les colonnes manquantes pour rester compatible avec une ancienne base.
    $columns = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'utilisateur_id'")->fetchAll();
    if (empty($columns))
        $pdo->exec("ALTER TABLE reservations ADD COLUMN utilisateur_id INT(11) DEFAULT NULL");

    $colEmail = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'email'")->fetchAll();
    if (empty($colEmail))
        $pdo->exec("ALTER TABLE reservations ADD COLUMN email VARCHAR(150) NOT NULL DEFAULT ''");

    // Charge les reservations du client avec le creneau et la table associes.
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

$mois      = ['','Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
// Ces compteurs servent au resume affiche au-dessus de la liste.
$total     = count($reservations);
$confirmee = count(array_filter($reservations, fn($r) => $r['statut'] === 'confirmee'));
$attente   = count(array_filter($reservations, fn($r) => $r['statut'] === 'en_attente'));
?>

<!-- ═══════════════════════════════════════════ STYLES ═══ -->
<style>
/* ── Alertes de page ── */
.gourmet-alert {
  display:flex;align-items:flex-start;gap:16px;
  padding:18px 22px;border-radius:12px;margin-bottom:28px;
  animation:gAlertIn .35s cubic-bezier(.22,1,.36,1);
}
@keyframes gAlertIn {
  from{opacity:0;transform:translateY(-10px)}
  to{opacity:1;transform:translateY(0)}
}
.gourmet-alert .g-icon {
  font-size:1.4rem;flex-shrink:0;margin-top:1px;
  filter:drop-shadow(0 0 7px currentColor);
}
.gourmet-alert .g-label {
  font-family:var(--ff-ui);font-size:.62rem;letter-spacing:.22em;
  text-transform:uppercase;margin-bottom:4px;
}
.gourmet-alert .g-text  { font-size:.93rem;color:var(--text);line-height:1.55; }
.gourmet-alert .g-sub   { font-size:.77rem;color:var(--muted);margin-top:5px; }
.gourmet-alert .g-code  {
  display:inline-block;background:rgba(201,168,76,.12);border:1px solid rgba(201,168,76,.3);
  color:var(--gold);font-family:'Courier New',monospace;font-size:.9rem;
  letter-spacing:.12em;padding:2px 10px;border-radius:5px;font-weight:700;
}
.ga-success { background:rgba(118,215,160,.06);border:1px solid rgba(118,215,160,.3); }
.ga-success .g-icon,.ga-success .g-label { color:#76D7A0; }
.ga-danger  { background:rgba(231,76,60,.07);border:1px solid rgba(231,76,60,.3); }
.ga-danger  .g-icon,.ga-danger  .g-label { color:#e74c3c; }
.ga-warning { background:rgba(250,215,160,.06);border:1px solid rgba(250,215,160,.28); }
.ga-warning .g-icon,.ga-warning .g-label { color:#FAD7A0; }

/* ── Modal annulation ── */
.modal-annul .modal-content {
  background:#0a0a0a;border:1px solid rgba(231,76,60,.22);border-radius:14px;overflow:hidden;
  box-shadow:0 40px 100px rgba(0,0,0,.9),0 0 0 1px rgba(231,76,60,.07);
}
.modal-annul .modal-header {
  background:#0f0505;border-bottom:1px solid rgba(231,76,60,.18);padding:20px 24px 16px;
}
.modal-annul .modal-title {
  font-family:var(--ff-ui);font-size:.66rem;letter-spacing:.22em;text-transform:uppercase;
  color:#e74c3c;display:flex;align-items:center;gap:10px;
}
.modal-annul .modal-title .icon-wrap {
  width:34px;height:34px;border-radius:50%;background:rgba(231,76,60,.14);
  border:1px solid rgba(231,76,60,.3);display:flex;align-items:center;justify-content:center;
  font-size:.95rem;color:#e74c3c;flex-shrink:0;
}
.modal-annul .modal-body {
  padding:24px;color:#bbb;font-size:.93rem;line-height:1.65;
}
.modal-annul .res-detail-box {
  background:#111;border:1px solid #1d1d1d;border-radius:10px;
  padding:14px 18px;margin-top:16px;display:flex;align-items:center;gap:14px;
}
.modal-annul .res-detail-box .d-code {
  font-family:'Courier New',monospace;font-size:1.05rem;letter-spacing:.15em;
  color:#e74c3c;font-weight:700;
}
.modal-annul .res-detail-box .d-date { font-size:.8rem;color:#555;margin-top:2px; }
.modal-annul .modal-footer {
  background:#080808;border-top:1px solid #111;padding:14px 24px;gap:10px;
}
.btn-garder {
  background:#1a1a1a;border:1px solid #2a2a2a;color:#777;
  font-family:var(--ff-ui);font-size:.6rem;letter-spacing:.18em;text-transform:uppercase;
  padding:10px 20px;border-radius:7px;cursor:pointer;transition:all .18s;
}
.btn-garder:hover { background:#222;color:#aaa;border-color:#333; }
.btn-annul-ok {
  background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.38);color:#e74c3c;
  font-family:var(--ff-ui);font-size:.6rem;letter-spacing:.18em;text-transform:uppercase;
  padding:10px 20px;border-radius:7px;cursor:pointer;transition:all .18s;
  text-decoration:none;display:inline-flex;align-items:center;gap:7px;
}
.btn-annul-ok:hover {
  background:rgba(231,76,60,.18);border-color:rgba(231,76,60,.6);color:#ff6b5b;
}
</style>

<!-- ═══════════════════════════════════════════ PAGE ═══ -->
<div class="page-hero reveal">
  <span class="eyebrow">Espace personnel</span>
  <h1>Mes <em>réservations</em></h1>
  <div class="divider"></div>
</div>

<div class="row justify-content-center">
<div class="col-xl-9 col-lg-11">

<?php
$msg    = $_GET['msg']    ?? '';
$erreur = $_GET['erreur'] ?? '';
$code   = htmlspecialchars($_GET['code'] ?? '');

if ($msg === 'annulee'): ?>
  <div class="gourmet-alert ga-danger reveal">
    <i class="bi bi-x-circle-fill g-icon"></i>
    <div>
      <div class="g-label">Réservation annulée</div>
      <div class="g-text">
        Votre réservation<?php if ($code): ?> <span class="g-code"><?= $code ?></span><?php endif; ?> a bien été annulée.
      </div>
      <div class="g-sub"><i class="bi bi-info-circle me-1" style="color:var(--gold)"></i>La confirmation a été enregistrée dans votre espace.</div>
    </div>
  </div>

<?php elseif ($erreur === 'deja_annulee'): ?>
  <div class="gourmet-alert ga-warning reveal">
    <i class="bi bi-exclamation-circle-fill g-icon"></i>
    <div>
      <div class="g-label">Déjà annulée</div>
      <div class="g-text">Cette réservation a déjà été annulée précédemment.</div>
    </div>
  </div>

<?php elseif ($erreur === 'non_trouve'): ?>
  <div class="gourmet-alert ga-warning reveal">
    <i class="bi bi-search g-icon"></i>
    <div>
      <div class="g-label">Introuvable</div>
      <div class="g-text">Aucune réservation correspondante n'a été trouvée sur votre compte.</div>
    </div>
  </div>

<?php elseif ($erreur === 'db' || $erreur): ?>
  <div class="gourmet-alert ga-danger reveal">
    <i class="bi bi-wifi-off g-icon"></i>
    <div>
      <div class="g-label">Erreur technique</div>
      <div class="g-text">Une erreur est survenue. Veuillez réessayer dans quelques instants.</div>
    </div>
  </div>
<?php endif; ?>

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
    $ts      = strtotime($res['date_reservation']);
    $stClass = match($res['statut']) { 'confirmee'=>'st-confirmee','annulee'=>'st-annulee',default=>'' };
    $bdgCls  = match($res['statut']) { 'confirmee'=>'bg-success','annulee'=>'bg-danger',default=>'bg-warning' };
    $bdgLbl  = match($res['statut']) { 'confirmee'=>'Confirmée','annulee'=>'Annulée',default=>'En attente' };
    $bdgIcon = match($res['statut']) { 'confirmee'=>'check-circle','annulee'=>'x-circle',default=>'clock' };
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

          <!-- Boutons actions -->
          <div class="d-flex gap-2 mt-3 flex-wrap">
            <a href="imprimer_reservation.php?id=<?= $res['id'] ?>"
               target="_blank"
               class="btn btn-sm btn-outline-primary">
              <i class="bi bi-printer me-1"></i>Imprimer / PDF
            </a>
            <?php if ($res['statut'] !== 'annulee'): ?>
            <button type="button"
                    class="btn btn-sm btn-outline-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#modalAnnuler"
                    data-id="<?= $res['id'] ?>"
                    data-code="<?= htmlspecialchars($res['code_confirmation']) ?>"
                    data-date="<?= date('d/m/Y', strtotime($res['date_reservation'])) ?>">
              <i class="bi bi-x-circle me-1"></i>Annuler
            </button>
            <?php endif; ?>
          </div>

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


<!-- ═══════════════════════════════════════ MODAL ANNULATION ═══ -->
<div class="modal fade modal-annul" id="modalAnnuler" tabindex="-1"
     aria-labelledby="modalAnnulerLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="modalAnnulerLabel">
          <span class="icon-wrap"><i class="bi bi-exclamation-triangle-fill"></i></span>
          Confirmer l'annulation
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity:.35"></button>
      </div>

      <div class="modal-body">
        <p style="margin:0;color:#aaa">Vous êtes sur le point d'annuler définitivement cette réservation. Cette action est <strong style="color:#e74c3c">irréversible</strong>.</p>
        <div class="res-detail-box">
          <i class="bi bi-ticket-perforated" style="color:#444;font-size:1.2rem;flex-shrink:0"></i>
          <div>
            <div class="d-code" id="modalCode"></div>
            <div class="d-date">Réservation du <strong id="modalDate" style="color:#777"></strong></div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-garder" data-bs-dismiss="modal">
          <i class="bi bi-arrow-left me-1"></i>Conserver
        </button>
        <a id="modalAnnulerLien" href="#" class="btn-annul-ok">
          <i class="bi bi-x-circle"></i>Confirmer l'annulation
        </a>
      </div>

    </div>
  </div>
</div>

<script>
// Peupler la modal
document.getElementById('modalAnnuler').addEventListener('show.bs.modal', function(e) {
  const btn = e.relatedTarget;
  document.getElementById('modalCode').textContent = btn.dataset.code;
  document.getElementById('modalDate').textContent  = btn.dataset.date;
  document.getElementById('modalAnnulerLien').href  = 'annuler_reservation.php?id=' + btn.dataset.id;
});

// Rendre le backdrop vraiment sombre
document.getElementById('modalAnnuler').addEventListener('shown.bs.modal', function() {
  const bd = document.querySelector('.modal-backdrop');
  if (bd) { bd.style.background='#000'; bd.style.opacity='.9'; }
});
</script>

<?php require_once 'includes/footer.php'; ?>
