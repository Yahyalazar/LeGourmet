<?php
require_once 'config/database.php';
require_once 'includes/reservation_notifier.php';

if (! isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?erreur=connexion_requise");
    exit;
}

ensureReservationNotificationSchema($pdo);

if (empty($_SESSION['admin_notifications_csrf'])) {
    $_SESSION['admin_notifications_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['admin_notifications_csrf'];
$todayStr = date('Y-m-d');
$tomorrowStr = date('Y-m-d', strtotime('+1 day'));

try {
    $sql = "
        SELECT
            r.id,
            r.nom_client,
            r.email,
            r.telephone,
            r.date_reservation,
            r.nombre_personnes,
            r.statut,
            r.code_confirmation,
            r.confirmation_email_sent_at,
            r.reminder_email_sent_at,
            c.heure,
            c.service,
            t.numero AS numero_table,
            t.zone AS zone_table,
            t.capacite
        FROM reservations r
        LEFT JOIN creneaux c ON r.creneau_id = c.id
        LEFT JOIN tables_restaurant t ON r.table_id = t.id
        ORDER BY r.date_reservation DESC, c.heure ASC
    ";
    $reservations = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    die("Erreur lors du chargement des reservations.");
}

$total = count($reservations);
$confirmee = count(array_filter($reservations, fn($r) => $r['statut'] === 'confirmee'));
$attente = count(array_filter($reservations, fn($r) => $r['statut'] === 'en_attente'));
$annulee = count(array_filter($reservations, fn($r) => $r['statut'] === 'annulee'));
$aujCount = count(array_filter($reservations, fn($r) => $r['date_reservation'] === $todayStr));
$tomorrowReminderCount = count(array_filter(
    $reservations,
    fn($r) => $r['statut'] === 'confirmee'
        && $r['date_reservation'] === $tomorrowStr
        && ! empty($r['email'])
        && empty($r['reminder_email_sent_at'])
));

$notif = (string) ($_GET['notif'] ?? '');
$notifSent = (int) ($_GET['sent'] ?? 0);
$notifFailed = (int) ($_GET['failed'] ?? 0);

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 reveal">
  <div>
    <div class="eyebrow-sm">
      <i class="bi bi-speedometer2 me-1"></i>Administration
    </div>
    <h1 class="admin-title">Tableau de <em>bord</em></h1>
  </div>
  <div class="d-flex gap-2 flex-wrap align-items-center">
    <div class="admin-search">
      <i class="bi bi-search s-icon"></i>
      <input type="text" class="form-control" id="searchInput"
             placeholder="Rechercher un client..."
             aria-label="Recherche client">
    </div>
    <form action="envoyer_notifications_reservations.php" method="POST" class="d-inline">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="action" value="send_tomorrow_reminders">
      <button type="submit" class="btn btn-outline-warning btn-sm">
        <i class="bi bi-envelope-check me-1"></i>Rappels J-1
        <?php if ($tomorrowReminderCount > 0): ?>
          <span class="badge text-bg-dark ms-1"><?= $tomorrowReminderCount ?></span>
        <?php endif; ?>
      </button>
    </form>
    <a href="admin.php" class="btn btn-outline-primary btn-sm"
       data-bs-toggle="tooltip" title="Actualisation auto dans &lt;span id='refreshCount'&gt;30s&lt;/span&gt;">
      <i class="bi bi-arrow-clockwise me-1"></i>Actualiser
    </a>
  </div>
</div>

<?php if ($notif !== ''): ?>
  <div class="alert <?= in_array($notif, ['confirmation_failed', 'reminder_failed', 'csrf', 'unknown_action'], true) ? 'alert-danger' : 'alert-success' ?> reveal">
    <?php
    switch ($notif) {
        case 'confirmation_sent':
            echo "Email de confirmation envoye au client.";
            break;
        case 'reminder_sent':
            echo "Rappel de reservation envoye au client.";
            break;
        case 'reminder_batch':
            echo "Rappels J-1 traites : {$notifSent} envoye(s), {$notifFailed} echec(s).";
            break;
        case 'confirmation_failed':
            echo "Impossible d'envoyer l'email de confirmation.";
            break;
        case 'reminder_failed':
            echo "Impossible d'envoyer le rappel.";
            break;
        case 'csrf':
            echo "Action refusee : jeton de securite invalide.";
            break;
        default:
            echo "Action de notification inconnue.";
            break;
    }
    ?>
  </div>
<?php endif; ?>

<div class="stats-bar reveal">
  <div class="stat-card s-total">
    <div class="stat-num"><?= $total ?></div>
    <div class="stat-lbl"><i class="bi bi-collection me-1"></i>Total</div>
  </div>
  <div class="stat-card s-confirm">
    <div class="stat-num"><?= $confirmee ?></div>
    <div class="stat-lbl"><i class="bi bi-check-circle me-1"></i>Confirmees</div>
  </div>
  <div class="stat-card s-wait">
    <div class="stat-num"><?= $attente ?></div>
    <div class="stat-lbl"><i class="bi bi-clock me-1"></i>En attente</div>
  </div>
  <div class="stat-card s-cancel">
    <div class="stat-num"><?= $annulee ?></div>
    <div class="stat-lbl"><i class="bi bi-x-circle me-1"></i>Annulees</div>
  </div>
</div>

<div class="filter-pills reveal">
  <button class="fpill active" data-f="all">
    <i class="bi bi-grid me-1"></i>Toutes <span class="badge-count"><?= $total ?></span>
  </button>
  <button class="fpill f-confirm" data-f="confirmee">
    <i class="bi bi-check-circle me-1"></i>Confirmees <span class="badge-count"><?= $confirmee ?></span>
  </button>
  <button class="fpill f-wait" data-f="en_attente">
    <i class="bi bi-clock me-1"></i>En attente <span class="badge-count"><?= $attente ?></span>
  </button>
  <button class="fpill f-cancel" data-f="annulee">
    <i class="bi bi-x-circle me-1"></i>Annulees <span class="badge-count"><?= $annulee ?></span>
  </button>
  <?php if ($aujCount > 0): ?>
    <button class="fpill f-today" data-f="today">
      <i class="bi bi-calendar-day me-1"></i>Aujourd'hui <span class="badge-count"><?= $aujCount ?></span>
    </button>
  <?php endif; ?>
</div>

<?php if ($total === 0): ?>
  <div class="card no-hover reveal">
    <div class="card-body empty-state">
      <span class="empty-icon">Liste vide</span>
      <h4>Aucune reservation</h4>
      <p>Le tableau de bord est vide pour le moment.</p>
    </div>
  </div>
<?php else: ?>
  <div class="row g-3" id="reservationsGrid">
    <?php foreach ($reservations as $i => $res):
        $bdg = match ($res['statut']) {
            'confirmee' => 'bg-success',
            'annulee' => 'bg-danger',
            default => 'bg-warning',
        };
        $border = match ($res['statut']) {
            'confirmee' => 'border-success',
            'annulee' => 'border-danger',
            default => 'border-warning',
        };
        $lbl = match ($res['statut']) {
            'confirmee' => 'Confirmee',
            'annulee' => 'Annulee',
            default => 'En attente',
        };
        $icon = match ($res['statut']) {
            'confirmee' => 'check-circle',
            'annulee' => 'x-circle',
            default => 'clock',
        };
        $delay = ($i % 6) * 0.06;
        $canSendConfirmation = ! empty($res['email']) && empty($res['confirmation_email_sent_at']);
        $canSendReminder = ! empty($res['email'])
            && $res['statut'] === 'confirmee'
            && $res['date_reservation'] === $tomorrowStr
            && empty($res['reminder_email_sent_at']);
    ?>
    <div class="col-xl-4 col-md-6 reveal res-grid-item"
         data-statut="<?= htmlspecialchars($res['statut']) ?>"
         data-client="<?= strtolower(htmlspecialchars($res['nom_client'])) ?>"
         data-date="<?= htmlspecialchars($res['date_reservation']) ?>"
         style="transition-delay: <?= $delay ?>s">

      <div class="card admin-card <?= $border ?> h-100">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
          <span class="date-label">
            <i class="bi bi-calendar3 me-1" style="opacity:.5"></i>
            <?= date('d/m/Y', strtotime($res['date_reservation'])) ?>
            &middot; <?= htmlspecialchars($res['heure'] ?? '?') ?>
            <small class="ms-1 text-muted">(<?= ucfirst((string) ($res['service'] ?? '')) ?>)</small>
          </span>
          <span class="badge <?= $bdg ?>">
            <i class="bi bi-<?= $icon ?> me-1"></i><?= $lbl ?>
          </span>
        </div>

        <div class="card-body">
          <div class="client-name"><?= htmlspecialchars($res['nom_client']) ?></div>

          <?php if (! empty($res['email'])): ?>
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
              <?= (int) $res['nombre_personnes'] ?> personne<?= (int) $res['nombre_personnes'] > 1 ? 's' : '' ?>
            </span>
          </div>

          <div class="meta-line">
            <i class="bi bi-ui-checks"></i>
            <?php if ($res['numero_table']): ?>
              <span class="val">
                Table N<?= (int) $res['numero_table'] ?>
                <?= ! empty($res['zone_table']) ? ' &mdash; ' . htmlspecialchars($res['zone_table']) : '' ?>
                (<?= isset($res['capacite']) ? (int) $res['capacite'] : '?' ?> pers.)
              </span>
            <?php else: ?>
              <span style="color:var(--warning);font-size:.82rem">Non assignee</span>
            <?php endif; ?>
          </div>

          <div class="meta-line" style="border-top:1px solid rgba(255,255,255,.05);padding-top:.5rem;margin-top:.4rem">
            <i class="bi bi-tag"></i>
            <code><?= htmlspecialchars($res['code_confirmation']) ?></code>
          </div>

          <div class="mt-3 d-flex gap-2 flex-wrap">
            <?php if ($canSendConfirmation): ?>
              <form action="envoyer_notifications_reservations.php" method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="send_confirmation">
                <input type="hidden" name="reservation_id" value="<?= (int) $res['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-light">
                  <i class="bi bi-send me-1"></i>Email confirmation
                </button>
              </form>
            <?php elseif (! empty($res['confirmation_email_sent_at'])): ?>
              <span class="badge text-bg-dark">Confirmation envoyee</span>
            <?php endif; ?>

            <?php if ($canSendReminder): ?>
              <form action="envoyer_notifications_reservations.php" method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="send_reminder">
                <input type="hidden" name="reservation_id" value="<?= (int) $res['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-warning">
                  <i class="bi bi-bell me-1"></i>Rappel J-1
                </button>
              </form>
            <?php elseif (! empty($res['reminder_email_sent_at'])): ?>
              <span class="badge text-bg-secondary">Rappel envoye</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center">
          <a href="editer_reservation.php?id=<?= (int) $res['id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i>Modifier
          </a>
          <a href="supprimer_reservation.php?id=<?= (int) $res['id'] ?>"
             class="btn btn-sm btn-outline-danger"
             onclick="return confirmDel(this, '<?= htmlspecialchars(addslashes($res['nom_client'])) ?>')">
            <i class="bi bi-trash me-1"></i>Supprimer
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div id="noResults" class="card no-hover mt-3" style="display:none">
    <div class="card-body empty-state" style="padding:3rem">
      <span class="empty-icon" style="font-size:2rem">Recherche</span>
      <h4 style="font-size:1.3rem">Aucun resultat</h4>
      <p>Aucune reservation ne correspond a votre filtre ou recherche.</p>
    </div>
  </div>
<?php endif; ?>

<div class="refresh-bar"></div>

<?php require_once 'includes/footer.php'; ?>
