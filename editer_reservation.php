<?php
require_once 'config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = (int)$_POST['id'];
    $statut       = in_array($_POST['statut'], ['en_attente','confirmee','annulee']) ? $_POST['statut'] : 'en_attente';
    $table_id     = !empty($_POST['table_id']) ? (int)$_POST['table_id'] : NULL;
    $commentaires = htmlspecialchars($_POST['commentaires'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE reservations SET statut=:s, table_id=:t, commentaires=:c WHERE id=:id");
        $stmt->execute([':s'=>$statut, ':t'=>$table_id, ':c'=>$commentaires, ':id'=>$id]);
        header("Location: admin.php?msg=updated"); exit;
    } catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }
}

if (empty($_GET['id'])) { header("Location: admin.php"); exit; }
$id_res = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.heure, c.service
        FROM reservations r
        LEFT JOIN creneaux c ON r.creneau_id = c.id
        WHERE r.id = :id
    ");
    $stmt->execute([':id' => $id_res]);
    $r = $stmt->fetch();
    if (!$r) { header("Location: admin.php"); exit; }

    $tables = $pdo->query("SELECT id, numero, capacite, zone FROM tables_restaurant ORDER BY numero ASC")->fetchAll();
} catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }

require_once 'includes/header.php';
?>

<div class="page-hero reveal">
  <span class="eyebrow">Administration</span>
  <h1>Modifier la <em>réservation</em></h1>
  <div class="divider"></div>
</div>

<div class="row justify-content-center">
<div class="col-xl-8 col-lg-9 col-md-11">

  <div class="mb-3 reveal">
    <a href="admin.php" class="btn btn-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Retour au tableau de bord
    </a>
  </div>

  <div class="card no-hover reveal">
    <div class="card-header bg-dark d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h4><i class="bi bi-pencil-square me-2" style="color:var(--gold);font-size:.85em"></i>Réservation #<?= $r['id'] ?></h4>
      <code><?= htmlspecialchars($r['code_confirmation']) ?></code>
    </div>

    <div class="card-body">

      <!-- Read-only info -->
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1.4rem;margin-bottom:1.8rem">

        <?php
        $fields = [
          ['Client',      $r['nom_client'],    'person'],
          ['Téléphone',   $r['telephone'],      'telephone'],
          ['Email',       $r['email'] ?: '—',  'envelope'],
          ['Date',        date('d/m/Y', strtotime($r['date_reservation'])), 'calendar3'],
          ['Créneau',     ($r['heure'] ?? '?') . ' — ' . ucfirst($r['service'] ?? ''), 'clock'],
          ['Convives',    $r['nombre_personnes'] . ' pers.', 'people'],
        ];
        foreach ($fields as [$lbl, $val, $ico]):
        ?>
        <div>
          <div style="font-family:var(--ff-ui);font-size:.58rem;letter-spacing:.2em;text-transform:uppercase;color:var(--muted);margin-bottom:.3rem">
            <i class="bi bi-<?= $ico ?> me-1"></i><?= $lbl ?>
          </div>
          <div style="font-family:var(--ff-display);font-size:1rem;color:var(--cream)"><?= htmlspecialchars($val) ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($r['commentaires_client'] ?? $r['commentaires'])): ?>
        <?php $originalComment = $r['commentaires'] ?? ''; ?>
        <?php if (!empty($originalComment)): ?>
        <div class="alert alert-info mb-3 d-flex gap-2">
          <i class="bi bi-chat-left-quote" style="color:var(--gold);flex-shrink:0;margin-top:.1rem"></i>
          <div>
            <div style="font-family:var(--ff-ui);font-size:.58rem;letter-spacing:.15em;text-transform:uppercase;color:var(--gold);margin-bottom:.2rem">Demande du client</div>
            <?= htmlspecialchars($originalComment) ?>
          </div>
        </div>
        <?php endif; ?>
      <?php endif; ?>

      <hr>

      <!-- Editable form -->
      <form action="editer_reservation.php" method="POST" id="editForm">
        <input type="hidden" name="id" value="<?= $r['id'] ?>">

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="statut" class="form-label"><i class="bi bi-flag me-1"></i>Statut</label>
            <select class="form-select" id="statut" name="statut" required>
              <option value="en_attente" <?= $r['statut']==='en_attente'?'selected':'' ?>>⏳ En attente</option>
              <option value="confirmee"  <?= $r['statut']==='confirmee' ?'selected':'' ?>>✅ Confirmée</option>
              <option value="annulee"    <?= $r['statut']==='annulee'   ?'selected':'' ?>>❌ Annulée</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="table_id" class="form-label"><i class="bi bi-ui-checks me-1"></i>Table assignée</label>
            <select class="form-select" id="table_id" name="table_id">
              <option value="">— Aucune table —</option>
              <?php foreach ($tables as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $r['table_id']==$t['id']?'selected':'' ?>>
                  Table <?= $t['numero'] ?>
                  (<?= $t['capacite'] ?> pers.<?= $t['zone'] ? ' · '.$t['zone'] : '' ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="mb-4">
          <label for="commentaires" class="form-label"><i class="bi bi-chat-text me-1"></i>Message au client</label>
          <textarea class="form-control" id="commentaires" name="commentaires" rows="3"
                    placeholder="Information supplémentaire transmise au client…"><?= htmlspecialchars($r['commentaires'] ?? '') ?></textarea>
        </div>

        <!-- Status color preview -->
        <div id="statusPreview" style="font-family:var(--ff-ui);font-size:.62rem;letter-spacing:.12em;text-transform:uppercase;margin-bottom:1.2rem;opacity:.7"></div>

        <div class="divider-gold"><span>✦</span></div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-lg" id="saveBtn">
            <i class="bi bi-check2-all me-2"></i>Mettre à jour la réservation
          </button>
        </div>
      </form>

    </div>
  </div>
</div>
</div>

<script>
// Status preview
(function() {
  const sel = document.getElementById('statut');
  const prev = document.getElementById('statusPreview');
  const map = {
    'en_attente': { c:'var(--warning)', t:'⏳ Statut : En attente — la confirmation sera envoyée par le restaurant' },
    'confirmee':  { c:'var(--success)', t:'✅ Statut : Confirmée — le client verra sa réservation comme validée' },
    'annulee':    { c:'var(--danger)',  t:'❌ Statut : Annulée — la réservation sera marquée comme annulée' }
  };
  function upd() {
    const v = map[sel.value];
    if (v) { prev.textContent = v.t; prev.style.color = v.c; }
  }
  sel?.addEventListener('change', upd);
  upd();
})();

// Save feedback
document.getElementById('editForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('saveBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sauvegarde…';
});
</script>

<?php require_once 'includes/footer.php'; ?>
