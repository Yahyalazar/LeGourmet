<?php
require_once 'config/database.php';
require_once 'includes/reservation_notifier.php';

ensureReservationNotificationSchema($pdo);

if (PHP_SAPI === 'cli') {
    $result = sendNextDayReservationReminders($pdo);
    echo "Date cible: {$result['target_date']}\n";
    echo "Emails envoyes: {$result['sent']}\n";
    echo "Echecs: {$result['failed']}\n";
    exit($result['failed'] > 0 ? 1 : 0);
}

if (! isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?erreur=connexion_requise");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php");
    exit;
}

$token = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['admin_notifications_csrf'] ?? '');

if ($token === '' || $sessionToken === '' || ! hash_equals($sessionToken, $token)) {
    header("Location: admin.php?notif=csrf");
    exit;
}

$action = (string) ($_POST['action'] ?? '');

if ($action === 'send_confirmation') {
    $reservationId = (int) ($_POST['reservation_id'] ?? 0);
    $ok = $reservationId > 0 && sendReservationConfirmationEmail($pdo, $reservationId);
    header("Location: admin.php?notif=" . ($ok ? 'confirmation_sent' : 'confirmation_failed'));
    exit;
}

if ($action === 'send_reminder') {
    $reservationId = (int) ($_POST['reservation_id'] ?? 0);
    $ok = $reservationId > 0 && sendReservationReminderEmail($pdo, $reservationId);
    header("Location: admin.php?notif=" . ($ok ? 'reminder_sent' : 'reminder_failed'));
    exit;
}

if ($action === 'send_tomorrow_reminders') {
    $result = sendNextDayReservationReminders($pdo);
    header("Location: admin.php?notif=reminder_batch&sent=" . (int) $result['sent'] . "&failed=" . (int) $result['failed']);
    exit;
}

header("Location: admin.php?notif=unknown_action");
exit;
