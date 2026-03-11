<?php
// traitement_reservation.php
require_once 'config/database.php';
require_once 'includes/reservation_notifier.php';

if (! isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php?erreur=connexion_requise");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

ensureReservationNotificationSchema($pdo);

$utilisateur_id = (int) $_SESSION['utilisateur_id'];
$nom_client = trim((string) ($_POST['nom_client'] ?? ''));
$telephone = trim((string) ($_POST['telephone'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ($_SESSION['email'] ?? '')));
$date_reservation = trim((string) ($_POST['date_reservation'] ?? ''));
$nombre_personnes = (int) ($_POST['nombre_personnes'] ?? 0);
$creneau_id = (int) ($_POST['creneau_id'] ?? 0);
$commentaires = trim((string) ($_POST['commentaires'] ?? ''));

if (
    $nom_client === ''
    || $telephone === ''
    || ! isValidReservationEmail($email)
    || $nombre_personnes < 1
    || $nombre_personnes > 20
    || $creneau_id < 1
    || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_reservation)
) {
    header("Location: index.php?erreur=formulaire_invalide");
    exit;
}

if ($date_reservation < date('Y-m-d')) {
    header("Location: index.php?erreur=date_invalide");
    exit;
}

if (function_exists('mb_substr')) {
    $commentaires = mb_substr($commentaires, 0, 300);
} else {
    $commentaires = substr($commentaires, 0, 300);
}

$code_confirmation = strtoupper(substr(uniqid('RES-'), 0, 10));

try {
    $sql_insert = "
        INSERT INTO reservations (
            utilisateur_id,
            nom_client,
            telephone,
            email,
            date_reservation,
            nombre_personnes,
            creneau_id,
            statut,
            code_confirmation,
            commentaires
        ) VALUES (
            :utilisateur_id,
            :nom,
            :tel,
            :email,
            :date_res,
            :personnes,
            :creneau,
            'en_attente',
            :code,
            :commentaires
        )
    ";

    $stmt = $pdo->prepare($sql_insert);
    $stmt->execute([
        ':utilisateur_id' => $utilisateur_id,
        ':nom' => $nom_client,
        ':tel' => $telephone,
        ':email' => $email,
        ':date_res' => $date_reservation,
        ':personnes' => $nombre_personnes,
        ':creneau' => $creneau_id,
        ':code' => $code_confirmation,
        ':commentaires' => $commentaires,
    ]);

    $reservationId = (int) $pdo->lastInsertId();
    sendReservationConfirmationEmail($pdo, $reservationId);

    header("Location: index.php?success=1&code=" . urlencode($code_confirmation));
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    die("Erreur lors de la reservation.");
}
