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