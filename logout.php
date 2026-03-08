<?php
// logout.php
session_start();
session_unset();    // Vide toutes les variables de session
session_destroy();  // Détruit la session
header("Location: index.php"); // Redirige vers la page d'accueil
exit;
?>