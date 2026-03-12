<?php
// Demarre la session afin de partager l'etat utilisateur sur tout le site.
session_start();

// Parametres centralises de connexion a la base de donnees.
define('DB_HOST', 'localhost');
define('DB_NAME', 'reservation_restaurant');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // Cree une connexion PDO avec des erreurs explicites et des resultats associatifs.
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Retourne un message generique pour ne pas exposer les details internes.
    http_response_code(503);
    die(json_encode(['error' => 'Service temporairement indisponible.']));
}
