<?php
require_once 'config/database.php';
// Cette page n'est accessible qu'aux utilisateurs connectes.
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php?erreur=connexion_requise_reservation"); exit;
}
require_once 'includes/header.php';

try {
    // Charge les creneaux disponibles pour alimenter la liste du formulaire.
    $creneaux = $pdo->query("SELECT id, heure, service FROM creneaux ORDER BY service, heure")->fetchAll();
} catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }

// Pre-remplit l'email depuis la session si disponible.
$email = htmlspecialchars($_SESSION['email'] ?? '');
?>

<div class="page-hero reveal">
  <span class="eyebrow">Restaurant Le Gourmet</span>
  <h1>Réserver <em>votre table</em></h1>
  <div class="divider"></div>
</div>

<div class="row justify-content-center">
  <div class="col-xl-7 col-lg-8 col-md-10 reveal">
    <div class="card no-hover">
      <div class="card-header">
        <h4><i class="bi bi-calendar-heart me-2" style="color:var(--gold)"></i>Formulaire de réservation</h4>
      </div>
      <div class="card-body">
        <form id="resForm" action="traitement_reservation.php" method="POST" novalidate>

          <!-- Ligne 1 : Nom + Téléphone -->
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label for="nom_client" class="form-label">
                <i class="bi bi-person"></i> Nom complet *
              </label>
              <input type="text" class="form-control" id="nom_client" name="nom_client"
                     placeholder="Jean Dupont" required autocomplete="name">
              <div class="invalid-feedback">Nom requis.</div>
            </div>
            <div class="col-6">
              <label for="telephone" class="form-label">
                <i class="bi bi-telephone"></i> Téléphone *
              </label>
              <input type="tel" class="form-control" id="telephone" name="telephone"
                     placeholder="+212 6 00 00 00 00" required autocomplete="tel">
              <div class="invalid-feedback">Téléphone requis.</div>
            </div>
          </div>

          <!-- Ligne 2 : Email -->
          <div class="mb-3">
            <label for="email" class="form-label">
              <i class="bi bi-envelope"></i> Email *
              <?php if ($email): ?>
                <span style="font-size:.6rem;font-weight:600;color:var(--gold);opacity:.8;text-transform:none;letter-spacing:0">
                  <i class="bi bi-check-circle ms-1"></i> depuis votre compte
                </span>
              <?php endif; ?>
            </label>
            <input type="email" class="form-control <?= $email ? 'is-valid' : '' ?>"
                   id="email" name="email" value="<?= $email ?>"
                   placeholder="votre@email.com" required autocomplete="email"
                   <?= $email ? 'readonly' : '' ?>>
            <?php if ($email): ?>
              <div class="valid-feedback" style="display:block">
                <i class="bi bi-check2"></i> Email associé à votre compte
              </div>
            <?php else: ?>
              <div class="invalid-feedback">Email invalide.</div>
            <?php endif; ?>
          </div>

          <!-- Ligne 3 : Date + Créneau + Convives -->
          <div class="row g-3 mb-3">
            <div class="col-4">
              <label for="date_reservation" class="form-label">
                <i class="bi bi-calendar3"></i> Date *
              </label>
              <input type="date" class="form-control" id="date_reservation"
                     name="date_reservation" required min="<?= date('Y-m-d') ?>">
              <div class="invalid-feedback">Date future requise.</div>
            </div>
            <div class="col-5">
              <label for="creneau_id" class="form-label">
                <i class="bi bi-clock"></i> Créneau *
              </label>
              <select class="form-select" id="creneau_id" name="creneau_id" required>
                <option value="">— Choisir —</option>
                <optgroup label="🌞 Midi">
                  <?php foreach ($creneaux as $c): if ($c['service'] !== 'midi') continue; ?>
                    <option value="<?= $c['id'] ?>"><?= $c['heure'] ?></option>
                  <?php endforeach; ?>
                </optgroup>
                <optgroup label="🌙 Soir">
                  <?php foreach ($creneaux as $c): if ($c['service'] !== 'soir') continue; ?>
                    <option value="<?= $c['id'] ?>"><?= $c['heure'] ?></option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
              <div class="invalid-feedback">Créneau requis.</div>
            </div>
            <div class="col-3">
              <label for="nombre_personnes" class="form-label">
                <i class="bi bi-people"></i> Pers. *
              </label>
              <input type="number" class="form-control" id="nombre_personnes"
                     name="nombre_personnes" min="1" max="20" placeholder="2" required>
              <div class="invalid-feedback">1–20 pers.</div>
            </div>
          </div>

          <!-- Ligne 4 : Commentaires -->
          <div class="mb-3">
            <label for="commentaires" class="form-label">
              <i class="bi bi-chat-left-text"></i>
              Demandes spéciales
              <span style="font-size:.6rem;font-weight:400;text-transform:none;letter-spacing:0;opacity:.55;font-style:italic">— optionnel</span>
            </label>
            <textarea class="form-control" id="commentaires" name="commentaires"
                      rows="2" maxlength="300"
                      placeholder="Allergie, occasion spéciale, chaise haute…"></textarea>
          </div>

          <div class="divider-gold"><span>✦</span></div>

          <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
              <i class="bi bi-check2-circle me-2"></i>Confirmer ma réservation
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
