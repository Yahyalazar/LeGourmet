<?php
require_once 'config/database.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php?erreur=connexion_requise"); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php"); exit;
}

$utilisateur_id   = (int)$_SESSION['utilisateur_id'];
$nom_client       = htmlspecialchars(trim($_POST['nom_client'] ?? ''));
$email            = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$telephone        = htmlspecialchars(trim($_POST['telephone'] ?? ''));
$date_reservation = $_POST['date_reservation'] ?? '';
$nombre_personnes = max(1, min(20, (int)($_POST['nombre_personnes'] ?? 1)));
$creneau_id       = (int)($_POST['creneau_id'] ?? 0);
$commentaires     = htmlspecialchars(trim($_POST['commentaires'] ?? ''));

// Validation
if (empty($nom_client) || empty($telephone) || empty($date_reservation) || !$creneau_id) {
    header("Location: index.php?erreur=champs_manquants"); exit;
}

if ($date_reservation < date('Y-m-d')) {
    header("Location: index.php?erreur=date_invalide"); exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?erreur=email_invalide"); exit;
}

$code_confirmation = 'RES-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

try {
    $pdo->beginTransaction();

    // Auto-migrate: add missing columns if needed
    $cols = $pdo->query("SHOW COLUMNS FROM reservations")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('utilisateur_id', $cols))
        $pdo->exec("ALTER TABLE reservations ADD COLUMN utilisateur_id INT(11) DEFAULT NULL");
    if (!in_array('email', $cols))
        $pdo->exec("ALTER TABLE reservations ADD COLUMN email VARCHAR(150) NOT NULL DEFAULT ''");

    // Check slot validity
    $stmtSlot = $pdo->prepare("SELECT id FROM creneaux WHERE id = :id");
    $stmtSlot->execute([':id' => $creneau_id]);
    if (!$stmtSlot->fetch()) {
        $pdo->rollBack();
        header("Location: index.php?erreur=creneau_invalide"); exit;
    }

    // Insert reservation
    $sql = "INSERT INTO reservations
            (utilisateur_id, nom_client, email, telephone, date_reservation,
             nombre_personnes, creneau_id, statut, code_confirmation, commentaires)
            VALUES (:uid, :nom, :email, :tel, :date, :pers, :creneau, 'en_attente', :code, :comm)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid'    => $utilisateur_id,
        ':nom'    => $nom_client,
        ':email'  => $email,
        ':tel'    => $telephone,
        ':date'   => $date_reservation,
        ':pers'   => $nombre_personnes,
        ':creneau'=> $creneau_id,
        ':code'   => $code_confirmation,
        ':comm'   => $commentaires,
    ]);

    $pdo->commit();
    header("Location: index.php?success=1&code=" . urlencode($code_confirmation));
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Reservation error: " . $e->getMessage());
    header("Location: index.php?error=db");
    exit;
}
