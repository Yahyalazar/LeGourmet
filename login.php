<?php
require_once 'config/database.php';

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['mot_de_passe'];

    // Recherche de l'utilisateur par son email
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // Vérification de l'utilisateur et du mot de passe
    // (Dans un vrai projet, utilisez password_verify(), ici on fait simple pour le test en local si non haché)
    if ($user && password_verify($password, $user['mot_de_passe'])) {
        // Connexion réussie : on crée les variables de session
        $_SESSION['utilisateur_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        // Redirection selon le rôle
        if ($user['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $erreur = "Email ou mot de passe incorrect.";
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white text-center">
                <h4>Connexion</h4>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['erreur']) && $_GET['erreur'] === 'connexion_requise_reservation'): ?>
                    <div class="alert alert-warning text-center">
                        <strong>Oups !</strong> Vous devez vous connecter ou créer un compte pour pouvoir réserver une table.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['succes']) && $_GET['succes'] === 'inscription'): ?>
                    <div class="alert alert-success">Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.</div>
                <?php endif; ?>
              
                <div class="mt-3 text-center">
                    <p>Pas encore de compte ? <a href="inscription.php">Créer un compte</a></p>
                </div>
                <?php if (isset($erreur)): ?>
                    <div class="alert alert-danger"><?= $erreur ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
