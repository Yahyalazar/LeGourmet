<?php
// editer_reservation.php
require_once 'config/database.php';

// 1. TRAITEMENT DU FORMULAIRE (Mise à jour - POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $statut = htmlspecialchars($_POST['statut']);
    $table_id = !empty($_POST['table_id']) ? (int)$_POST['table_id'] : NULL;
    $commentaires = htmlspecialchars($_POST['commentaires']);

    try {
        // Requête de mise à jour sécurisée
        $sql_update = "UPDATE reservations SET statut = :statut, table_id = :table_id, commentaires = :commentaires WHERE id = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            ':statut' => $statut,
            ':table_id' => $table_id,
            ':commentaires' => $commentaires,
            ':id' => $id
        ]);
        
        // Redirection vers l'admin après succès
        header("Location: admin.php?msg=updated");
        exit;
    } catch(PDOException $e) {
        die("Erreur lors de la mise à jour : " . $e->getMessage());
    }
}

// 2. AFFICHAGE DU FORMULAIRE PRÉ-REMPLI (Lecture - GET)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin.php"); // Gestion d'erreur : si pas d'ID, on renvoie à l'accueil admin [cite: 117]
    exit;
}

$id_reservation = (int)$_GET['id'];

try {
    // Récupération de la réservation spécifique
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = :id");
    $stmt->execute([':id' => $id_reservation]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        die("Réservation introuvable."); // Gestion d'erreur si l'ID n'existe pas [cite: 117]
    }

    // Récupération de toutes les tables pour le menu déroulant
    $stmt_tables = $pdo->query("SELECT id, numero, capacite FROM tables_restaurant ORDER BY capacite ASC");
    $tables = $stmt_tables->fetchAll();

} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

require_once 'includes/header.php';
?>

<<<<<<< HEAD
<div class="row justify-content-center mb-5">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Modifier la réservation #<?= $reservation['id'] ?></h2>
            <a href="admin.php" class="btn btn-secondary">Retour à l'administration</a>
        </div>

        <div class="card shadow-sm border-primary">
            <div class="card-body">
                <form action="editer_reservation.php" method="POST">
                    <input type="hidden" name="id" value="<?= $reservation['id'] ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Client</p>
                            <h5><?= htmlspecialchars($reservation['nom_client']) ?></h5>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Contact</p>
                            <h5><?= htmlspecialchars($reservation['telephone']) ?></h5>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <p class="mb-1 text-muted">Date</p>
                            <strong><?= date('d/m/Y', strtotime($reservation['date_reservation'])) ?></strong>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1 text-muted">Personnes</p>
                            <strong><?= $reservation['nombre_personnes'] ?> personnes</strong>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1 text-muted">Code de confirmation</p>
                            <code><?= htmlspecialchars($reservation['code_confirmation']) ?></code>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="statut" class="form-label fw-bold">Statut de la réservation</label>
                            <select class="form-select" id="statut" name="statut" required>
                                <option value="en_attente" <?= $reservation['statut'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="confirmee" <?= $reservation['statut'] === 'confirmee' ? 'selected' : '' ?>>Confirmée</option>
                                <option value="annulee" <?= $reservation['statut'] === 'annulee' ? 'selected' : '' ?>>Annulée</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="table_id" class="form-label fw-bold">Assignation de la Table</label>
                            <select class="form-select" id="table_id" name="table_id">
                                <option value="">-- Aucune table assignée --</option>
                                <?php foreach($tables as $table): ?>
                                    <option value="<?= $table['id'] ?>" <?= $reservation['table_id'] == $table['id'] ? 'selected' : '' ?>>
                                        Table <?= $table['numero'] ?> (<?= $table['capacite'] ?> pers.)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="commentaires" class="form-label fw-bold">Notes de l'administrateur</label>
                        <textarea class="form-control" id="commentaires" name="commentaires" rows="3"><?= htmlspecialchars($reservation['commentaires'] ?? '') ?></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Mettre à jour la réservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
=======
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
>>>>>>> 78d311426f0af825cb1175be06a8ed488c667b24
