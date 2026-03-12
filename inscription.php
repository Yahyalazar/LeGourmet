<?php
require_once 'config/database.php';
// Redirige l'utilisateur s'il est deja connecte.
if (isset($_SESSION['utilisateur_id'])) { header("Location: index.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupere et nettoie les donnees du formulaire d'inscription.
    $email = trim(htmlspecialchars($_POST['email'] ?? ''));
    $mdp   = $_POST['mot_de_passe'] ?? '';
    $conf  = $_POST['mot_de_passe_confirm'] ?? '';

    if (!$email || !$mdp || !$conf)
        $err = "Tous les champs sont obligatoires.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $err = "Adresse email invalide.";
    elseif (strlen($mdp) < 6)
        $err = "Mot de passe trop court (min. 6 caractères).";
    elseif ($mdp !== $conf)
        $err = "Les mots de passe ne correspondent pas.";
    else {
        // Verifie si l'email existe deja dans la base.
        $c = $pdo->prepare("SELECT id FROM utilisateurs WHERE email=:e");
        $c->execute([':e'=>$email]);
        if ($c->fetch()) {
            $err = "Cette adresse email est déjà utilisée.";
        } else {
            // Hash le mot de passe avant de creer le compte client.
            $hash = password_hash($mdp, PASSWORD_BCRYPT, ['cost'=>12]);
            $i = $pdo->prepare("INSERT INTO utilisateurs (email,mot_de_passe,role) VALUES (:e,:h,'client')");
            if ($i->execute([':e'=>$email,':h'=>$hash]))
                { header("Location: login.php?succes=inscription"); exit; }
            else $err = "Erreur lors de la création. Réessayez.";
        }
    }
}
require_once 'includes/header.php';
?>

<div class="auth-wrap">
<div class="container">
<div class="auth-box reveal">

  <div class="auth-logo">
    <span class="brand">Le Gourmet</span>
    <span class="tag">Créer votre compte</span>
  </div>

  <?php if (isset($err)): ?>
    <div class="alert alert-danger mb-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i><?= $err ?>
    </div>
  <?php endif; ?>

  <div class="card no-hover">
    <div class="card-header">
      <h4><i class="bi bi-person-plus me-2" style="color:var(--gold)"></i>Inscription</h4>
    </div>
    <div class="card-body">
      <form id="inscriptForm" action="inscription.php" method="POST" novalidate>

        <div class="mb-3">
          <label for="email" class="form-label">
            <i class="bi bi-envelope"></i> Email *
          </label>
          <input type="email" class="form-control" id="email" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="votre@email.com" required autocomplete="email">
        </div>

        <div class="mb-3">
          <label for="mot_de_passe" class="form-label">
            <i class="bi bi-lock"></i> Mot de passe *
            <span style="font-size:.58rem;text-transform:none;letter-spacing:0;font-weight:400;opacity:.6">(min. 6 car.)</span>
          </label>
          <div class="position-relative">
            <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                   placeholder="••••••••" required minlength="6" autocomplete="new-password"
                   style="padding-right:2.8rem">
            <button type="button" data-pwd-toggle="mot_de_passe"
                    style="position:absolute;right:.8rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--t3-c);cursor:pointer;font-size:1rem;padding:0;transition:color .22s"
                    aria-label="Voir le mot de passe">
              <i class="bi bi-eye"></i>
            </button>
          </div>
          <div class="pwd-strength-bar mt-2"><div class="bar" id="pwdBar"></div></div>
          <small id="pwdHint" style="font-family:var(--ff-u);font-size:.6rem;font-weight:600;letter-spacing:.06em"></small>
        </div>

        <div class="mb-4">
          <label for="mot_de_passe_confirm" class="form-label">
            <i class="bi bi-lock-fill"></i> Confirmer *
          </label>
          <div class="position-relative">
            <input type="password" class="form-control" id="mot_de_passe_confirm" name="mot_de_passe_confirm"
                   placeholder="••••••••" required autocomplete="new-password"
                   style="padding-right:2.8rem">
            <button type="button" data-pwd-toggle="mot_de_passe_confirm"
                    style="position:absolute;right:.8rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--t3-c);cursor:pointer;font-size:1rem;padding:0;transition:color .22s"
                    aria-label="Voir la confirmation">
              <i class="bi bi-eye"></i>
            </button>
          </div>
          <small id="matchHint" style="font-family:var(--ff-u);font-size:.62rem;font-weight:600;letter-spacing:.06em"></small>
        </div>

        <div class="divider-gold"><span>✦</span></div>

        <div class="d-grid mb-3">
          <button type="submit" class="btn btn-primary btn-lg" id="subBtn">
            <i class="bi bi-person-check me-2"></i>Créer mon compte
          </button>
        </div>
      </form>

      <div class="auth-sep"><span>ou</span></div>
      <p class="text-center mb-0" style="font-family:var(--ff-u);font-size:.78rem;color:var(--t3-c)">
        Déjà un compte ?
        <a href="login.php" style="color:var(--gold);font-style:italic">Se connecter</a>
      </p>
    </div>
  </div>

</div>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>
