<?php
require_once 'config/database.php';

// Security: admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}

if (empty($_GET['id'])) { header("Location: admin.php"); exit; }

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header("Location: admin.php?msg=deleted");
    exit;
} catch (PDOException $e) {
    error_log("Delete error: " . $e->getMessage());
    header("Location: admin.php?msg=error");
    exit;
}
