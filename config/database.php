<?php
session_start(); // À AJOUTER ICI : Démarre le système de connexion
// config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'reservation_restaurant'); 
define('DB_USER', 'root'); 
define('DB_PASS', ''); 

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>