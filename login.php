<?php
require_once 'config/database.php';
if (isset($_SESSION['utilisateur_id'])) { header("Location: index.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if (!$email || !$mdp) {
        $err = "Veuillez remplir tous les champs.";
    } else {
        $s = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :e");
        $s->execute([':e' => $email]);
        $u = $s->fetch();
        if ($u && password_verify($mdp, $u['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['utilisateur_id'] = $u['id'];
            $_SESSION['role']           = $u['role'];
            $_SESSION['email']          = $u['email'];
            header("Location: " . ($u['role'] === 'admin' ? 'admin.php' : 'index.php')); exit;
        } else {
            $err = "Email ou mot de passe incorrect.";
            usleep(400000);
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
    <span class="tag">Connexion à votre espace</span>
  </div>

  <?php if (isset($err)): ?>
    <div class="alert alert-danger mb-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <div class="card no-hover">
    <div class="card-header">
      <h4><i class="bi bi-door-open me-2" style="color:var(--gold)"></i>Connexion</h4>
    </div>
    <div class="card-body">
      <form id="loginForm" action="login.php" method="POST" novalidate>

        <div class="mb-3">
          <label for="email" class="form-label">
            <i class="bi bi-envelope"></i> Adresse Email
          </label>
          <input type="email" class="form-control" id="email" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="votre@email.com" required autocomplete="email">
        </div>

        <div class="mb-4">
          <label for="mot_de_passe" class="form-label">
            <i class="bi bi-lock"></i> Mot de passe
          </label>
          <div class="position-relative">
            <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                   placeholder="••••••••" required autocomplete="current-password"
                   style="padding-right:2.8rem">
            <button type="button" data-pwd-toggle="mot_de_passe"
                    style="position:absolute;right:.8rem;top:50%;transform:translateY(-50%);
                           background:none;border:none;color:var(--t3-c);cursor:pointer;
                           font-size:1rem;padding:0;transition:color .22s"
                    aria-label="Voir le mot de passe">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div class="d-grid mb-3">
          <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
            <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
          </button>
        </div>
      </form>

      <div class="auth-sep"><span>ou</span></div>

      <p class="text-center mb-0" style="font-family:var(--ff-u);font-size:.78rem;color:var(--t3-c)">
        Pas de compte ?
        <a href="inscription.php" style="color:var(--gold);font-style:italic">S'inscrire</a>
      </p>
    </div>
  </div>


</div>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>
