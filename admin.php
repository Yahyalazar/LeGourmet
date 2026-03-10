<?php
require_once 'config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?erreur=connexion_requise"); exit;
}

require_once 'includes/header.php';

try {
    $sql = "
        SELECT r.id, r.nom_client,
               COALESCE(r.email,'') AS email,
               r.telephone, r.date_reservation,
               r.nombre_personnes, r.statut,
               r.code_confirmation, r.commentaires, r.date_creation,
               c.heure, c.service,
               t.numero AS numero_table, t.zone AS zone_table, t.capacite
        FROM reservations r
        LEFT JOIN creneaux c ON r.creneau_id = c.id
        LEFT JOIN tables_restaurant t ON r.table_id = t.id
        ORDER BY r.date_reservation DESC, c.heure ASC
    ";
    $reservations = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }

$total     = count($reservations);
$confirmee = count(array_filter($reservations, fn($r) => $r['statut'] === 'confirmee'));
$attente   = count(array_filter($reservations, fn($r) => $r['statut'] === 'en_attente'));
$annulee   = count(array_filter($reservations, fn($r) => $r['statut'] === 'annulee'));
$today_str = date('Y-m-d');
$auj_count = count(array_filter($reservations, fn($r) => $r['date_reservation'] === $today_str));
?>

<!-- ── En-tête Admin ── -->
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 reveal">
  <div>
    <div class="eyebrow-sm">
      <i class="bi bi-speedometer2 me-1"></i>Administration
    </div>
    <h1 class="admin-title">
      Tableau de <em>bord</em>
    </h1>
  </div>
  <div class="d-flex gap-2 flex-wrap align-items-center">
    <!-- Barre de recherche — écoutée par main.js #searchInput -->
    <div class="admin-search">
      <i class="bi bi-search s-icon"></i>
      <input type="text" class="form-control" id="searchInput"
             placeholder="Rechercher un client…"
             aria-label="Recherche client">
    </div>
    <!-- Actualiser + compteur refresh géré par main.js -->
    <a href="admin.php" class="btn btn-outline-primary btn-sm"
       data-bs-toggle="tooltip" title="Actualisation auto dans &lt;span id='refreshCount'&gt;30s&lt;/span&gt;">
      <i class="bi bi-arrow-clockwise me-1"></i>Actualiser
    </a>
  </div>
</div>

<!-- ── Stats ── -->
<div class="stats-bar reveal">
  <div class="stat-card s-total">
    <div class="stat-num"><?= $total ?></div>
    <div class="stat-lbl"><i class="bi bi-collection me-1"></i>Total</div>
  </div>
  <div class="stat-card s-confirm">
    <div class="stat-num"><?= $confirmee ?></div>
    <div class="stat-lbl"><i class="bi bi-check-circle me-1"></i>Confirmées</div>
  </div>
  <div class="stat-card s-wait">
    <div class="stat-num"><?= $attente ?></div>
    <div class="stat-lbl"><i class="bi bi-clock me-1"></i>En attente</div>
  </div>
  <div class="stat-card s-cancel">
    <div class="stat-num"><?= $annulee ?></div>
    <div class="stat-lbl"><i class="bi bi-x-circle me-1"></i>Annulées</div>
  </div>
</div>

<!-- ── Filter pills — gérés par main.js ── -->
<div class="filter-pills reveal">
  <button class="fpill active"      data-f="all">
    <i class="bi bi-grid me-1"></i>Toutes <span class="badge-count"><?= $total ?></span>
  </button>
  <button class="fpill f-confirm"   data-f="confirmee">
    <i class="bi bi-check-circle me-1"></i>Confirmées <span class="badge-count"><?= $confirmee ?></span>
  </button>
  <button class="fpill f-wait"      data-f="en_attente">
    <i class="bi bi-clock me-1"></i>En attente <span class="badge-count"><?= $attente ?></span>
  </button>
  <button class="fpill f-cancel"    data-f="annulee">
    <i class="bi bi-x-circle me-1"></i>Annulées <span class="badge-count"><?= $annulee ?></span>
  </button>
  <?php if ($auj_count > 0): ?>
  <button class="fpill f-today"     data-f="today">
    <i class="bi bi-calendar-day me-1"></i>Aujourd'hui <span class="badge-count"><?= $auj_count ?></span>
  </button>
  <?php endif; ?>
</div>

<?php if ($total === 0): ?>
  <div class="card no-hover reveal">
    <div class="card-body empty-state">
      <span class="empty-icon">📋</span>
      <h4>Aucune réservation</h4>
      <p>Le tableau de bord est vide pour le moment.</p>
    </div>
  </div>

<?php else: ?>

<!-- ── Grille de cartes — filtrées / recherchées par main.js ── -->
<div class="row g-3" id="reservationsGrid">
  <?php foreach ($reservations as $i => $res):
    $bdg    = match($res['statut']) { 'confirmee'=>'bg-success', 'annulee'=>'bg-danger', default=>'bg-warning' };
    $border = match($res['statut']) { 'confirmee'=>'border-success', 'annulee'=>'border-danger', default=>'border-warning' };
    $lbl    = match($res['statut']) { 'confirmee'=>'Confirmée', 'annulee'=>'Annulée', default=>'En attente' };
    $icon   = match($res['statut']) { 'confirmee'=>'check-circle', 'annulee'=>'x-circle', default=>'clock' };
    $delay  = ($i % 6) * 0.06;
  ?>
  <div class="col-xl-4 col-md-6 reveal res-grid-item"
       data-statut="<?= $res['statut'] ?>"
       data-client="<?= strtolower(htmlspecialchars($res['nom_client'])) ?>"
       data-date="<?= $res['date_reservation'] ?>"
       style="transition-delay: <?= $delay ?>s">

    <div class="card admin-card <?= $border ?> h-100">

      <!-- Card Header -->
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
        <span class="date-label">
          <i class="bi bi-calendar3 me-1" style="opacity:.5"></i>
          <?= date('d/m/Y', strtotime($res['date_reservation'])) ?>
          &middot; <?= htmlspecialchars($res['heure'] ?? '?') ?>
          <small class="ms-1 text-muted">(<?= ucfirst($res['service'] ?? '') ?>)</small>
        </span>
        <span class="badge <?= $bdg ?>">
          <i class="bi bi-<?= $icon ?> me-1"></i><?= $lbl ?>
        </span>
      </div>

      <!-- Card Body -->
      <div class="card-body">
        <div class="client-name"><?= htmlspecialchars($res['nom_client']) ?></div>

        <?php if (!empty($res['email'])): ?>
        <div class="meta-line">
          <i class="bi bi-envelope"></i>
          <span class="val"><?= htmlspecialchars($res['email']) ?></span>
        </div>
        <?php endif; ?>

        <div class="meta-line">
          <i class="bi bi-telephone"></i>
          <span class="val"><?= htmlspecialchars($res['telephone']) ?></span>
        </div>

        <div class="meta-line">
          <i class="bi bi-people"></i>
          <span class="val">
            <?= $res['nombre_personnes'] ?> personne<?= $res['nombre_personnes'] > 1 ? 's' : '' ?>
          </span>
        </div>

        <div class="meta-line">
          <i class="bi bi-ui-checks"></i>
          <?php if ($res['numero_table']): ?>
            <span class="val">
              Table N°<?= $res['numero_table'] ?>
              <?= $res['zone_table'] ? ' &mdash; ' . htmlspecialchars($res['zone_table']) : '' ?>
              (<?= $res['capacite'] ?> pers.)
            </span>
          <?php else: ?>
            <span style="color:var(--warning);font-size:.82rem">Non assignée</span>
          <?php endif; ?>
        </div>

        <div class="meta-line" style="border-top:1px solid rgba(255,255,255,.05);padding-top:.5rem;margin-top:.4rem">
          <i class="bi bi-tag"></i>
          <code><?= htmlspecialchars($res['code_confirmation']) ?></code>
        </div>
      </div>

      <!-- Card Footer -->
      <div class="card-footer d-flex justify-content-between align-items-center">
        <a href="editer_reservation.php?id=<?= $res['id'] ?>"
           class="btn btn-sm btn-outline-primary">
          <i class="bi bi-pencil me-1"></i>Modifier
        </a>
        <!-- confirmDel() définie dans main.js -->
        <a href="supprimer_reservation.php?id=<?= $res['id'] ?>"
           class="btn btn-sm btn-outline-danger"
           onclick="return confirmDel(this, '<?= htmlspecialchars(addslashes($res['nom_client'])) ?>')">
          <i class="bi bi-trash me-1"></i>Supprimer
        </a>
      </div>

    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Message aucun résultat (affiché/masqué par main.js) -->
<div id="noResults" class="card no-hover mt-3" style="display:none">
  <div class="card-body empty-state" style="padding:3rem">
    <span class="empty-icon" style="font-size:2rem">🔍</span>
    <h4 style="font-size:1.3rem">Aucun résultat</h4>
    <p>Aucune réservation ne correspond à votre filtre ou recherche.</p>
  </div>
</div>

<?php endif; ?>

<!-- Barre de refresh (animée + JS countdown via main.js) -->
<div class="refresh-bar"></div>

<?php require_once 'includes/footer.php'; ?>
