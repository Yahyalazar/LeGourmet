<?php
require_once 'config/database.php';

if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php"); exit;
}

if (empty($_GET['id'])) { header("Location: mes_reservations.php"); exit; }

$id  = (int)$_GET['id'];
$uid = (int)$_SESSION['utilisateur_id'];

try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.heure, c.service
        FROM reservations r
        LEFT JOIN creneaux c ON r.creneau_id = c.id
        WHERE r.id = :id AND r.utilisateur_id = :uid
    ");
    $stmt->execute([':id' => $id, ':uid' => $uid]);
    $res = $stmt->fetch();

    if (!$res) {
        header("Location: mes_reservations.php?erreur=non_trouve"); exit;
    }
    if ($res['statut'] === 'annulee') {
        header("Location: mes_reservations.php?erreur=deja_annulee"); exit;
    }

    $upd = $pdo->prepare("UPDATE reservations SET statut = 'annulee' WHERE id = :id AND utilisateur_id = :uid");
    $upd->execute([':id' => $id, ':uid' => $uid]);

    header("Location: mes_reservations.php?msg=annulee&code=" . urlencode($res['code_confirmation']));
    exit;

} catch (PDOException $e) {
    error_log("Annulation error: " . $e->getMessage());
    header("Location: mes_reservations.php?erreur=db"); exit;
}
