<?php
// inscription.php
require_once 'config/database.php';

// Si l'utilisateur est déjà connecté, on le renvoie à l'accueil
if (isset($_SESSION['utilisateur_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['mot_de_passe'];
    $password_confirm = $_POST['mot_de_passe_confirm'];

    // 1. Vérification des mots de passe
    if ($password !== $password_confirm) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        // 2. Vérification si l'email existe déjà
        $stmt_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email");
        $stmt_check->execute([':email' => $email]);
        
        if ($stmt_check->fetch()) {
            $erreur = "Cette adresse email est déjà utilisée.";
        } else {
            // 3. Création du compte client
            $mdp_hache = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = $pdo->prepare("INSERT INTO utilisateurs (email, mot_de_passe, role) VALUES (:email, :mdp, 'client')");
            
            if ($stmt_insert->execute([':email' => $email, ':mdp' => $mdp_hache])) {
                // Redirection vers le login avec un message de succès
                header("Location: login.php?succes=inscription");
                exit;
            } else {
                $erreur = "Une erreur est survenue lors de l'inscription.";
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-primary text-white text-center">
                <h4>Créer un compte Client</h4>
            </div>
            <div class="card-body p-4">
                <?php if (isset($erreur)): ?>
                    <div class="alert alert-danger"><?= $erreur ?></div>
                <?php endif; ?>

                <form action="inscription.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Adresse Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="mot_de_passe" class="form-label fw-bold">Mot de passe *</label>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required minlength="6">
                    </div>
                    <div class="mb-4">
                        <label for="mot_de_passe_confirm" class="form-label fw-bold">Confirmer le mot de passe *</label>
                        <input type="password" class="form-control" id="mot_de_passe_confirm" name="mot_de_passe_confirm" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">M'inscrire</button>
                </form>
                
                <div class="mt-3 text-center">
                    <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
