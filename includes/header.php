<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#08080C">
  <meta name="description" content="Le Gourmet — Restaurant gastronomique, réservation en ligne.">
  <title>Le Gourmet</title>
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+SC:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/style.css">
</head>

<?php
$sessionEmail = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '';
$dataAttr     = $sessionEmail ? ' data-session-email="'.$sessionEmail.'"' : '';
?>
<body<?= $dataAttr ?>>

<!-- Toast container (JS) -->
<div id="toast-container" role="region" aria-live="polite" aria-label="Notifications"></div>

<!-- ════════════ NAVBAR ════════════ -->
<nav class="navbar navbar-expand-lg navbar-dark" role="navigation" aria-label="Navigation principale">
  <div class="container">

    <a class="navbar-brand" href="index.php" aria-label="Le Gourmet - Accueil">Le Gourmet</a>

    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#navMain"
            aria-controls="navMain" aria-expanded="false" aria-label="Ouvrir le menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

        <li class="nav-item">
          <a class="nav-link" href="index.php">
            <i class="bi bi-calendar-check"></i> Réserver
          </a>
        </li>

        <?php if (isset($_SESSION['role'])): ?>

          <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
              <a class="nav-link" href="admin.php" style="color:var(--gold) !important;">
                <i class="bi bi-speedometer2"></i> Admin
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="mes_reservations.php">
                <i class="bi bi-person-circle"></i> Mon Espace
              </a>
            </li>
          <?php endif; ?>

          <?php if ($sessionEmail): ?>
          <li class="nav-item d-none d-lg-flex align-items-center">
            <span class="nav-user-badge">
              <i class="bi bi-person-fill"></i>
              <?= $sessionEmail ?>
            </span>
          </li>
          <?php endif; ?>

          <li class="nav-item">
            <a class="nav-link-logout nav-link" href="logout.php"
               data-bs-toggle="tooltip" data-bs-placement="bottom" title="Se déconnecter">
              <i class="bi bi-box-arrow-right"></i>
              <span>Déconnexion</span>
            </a>
          </li>

        <?php else: ?>

          <li class="nav-item">
            <a class="nav-link" href="login.php">
              <i class="bi bi-door-open"></i> Connexion
            </a>
          </li>
          <li class="nav-item ms-lg-1">
            <a class="nav-link btn-nav-cta" href="inscription.php">S'inscrire</a>
          </li>

        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4 main-content">
