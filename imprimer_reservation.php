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
        SELECT r.*, c.heure, c.service, t.numero AS numero_table, t.zone AS zone_table
        FROM reservations r
        LEFT JOIN creneaux c ON r.creneau_id = c.id
        LEFT JOIN tables_restaurant t ON r.table_id = t.id
        WHERE r.id = :id AND r.utilisateur_id = :uid
    ");
    $stmt->execute([':id' => $id, ':uid' => $uid]);
    $res = $stmt->fetch();

    if (!$res) { header("Location: mes_reservations.php"); exit; }
} catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }

$ts   = strtotime($res['date_reservation']);
$date = date('l d F Y', $ts);
$moisFR = ['January'=>'Janvier','February'=>'Février','March'=>'Mars','April'=>'Avril',
           'May'=>'Mai','June'=>'Juin','July'=>'Juillet','August'=>'Août',
           'September'=>'Septembre','October'=>'Octobre','November'=>'Novembre','December'=>'Décembre'];
$dayFR  = ['Monday'=>'Lundi','Tuesday'=>'Mardi','Wednesday'=>'Mercredi','Thursday'=>'Jeudi',
           'Friday'=>'Vendredi','Saturday'=>'Samedi','Sunday'=>'Dimanche'];
foreach ($moisFR as $en=>$fr) $date = str_replace($en,$fr,$date);
foreach ($dayFR  as $en=>$fr) $date = str_replace($en,$fr,$date);

$statutColor = match($res['statut']) { 'confirmee'=>'#27ae60','annulee'=>'#e74c3c',default=>'#f39c12' };
$statutLabel = match($res['statut']) { 'confirmee'=>'✅ Confirmée','annulee'=>'❌ Annulée',default=>'⏳ En attente' };
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Réservation <?= htmlspecialchars($res['code_confirmation']) ?> — Le Gourmet</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#f0ece3;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:16px 20px}

    /* Toolbar screen only */
    .toolbar{display:flex;gap:10px;margin-bottom:14px;width:100%;max-width:480px}
    .btn-back,.btn-print{flex:1;padding:9px;border:none;border-radius:8px;font-family:'Inter',sans-serif;font-size:.68rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;cursor:pointer;text-decoration:none;text-align:center;transition:all .2s}
    .btn-back{background:#333;color:#fff}
    .btn-back:hover{background:#222}
    .btn-print{background:#c9a84c;color:#fff}
    .btn-print:hover{background:#b8973a}

    /* Ticket */
    .ticket{background:#fff;width:100%;max-width:480px;border-radius:3px;box-shadow:0 14px 50px rgba(0,0,0,.15);position:relative;margin-top:10px}
    /* Bord déchiré haut */
    .ticket::before{content:'';display:block;height:14px;
      background:linear-gradient(-45deg,#f0ece3 25%,transparent 25%) -6px 0,
                  linear-gradient(45deg,#f0ece3 25%,transparent 25%) -6px 0,
                  linear-gradient(-45deg,transparent 75%,#f0ece3 75%) 0 0,
                  linear-gradient(45deg,transparent 75%,#f0ece3 75%) 0 0;
      background-size:14px 14px;background-color:#fff;
      position:absolute;top:-14px;left:0;right:0}
    /* Bord déchiré bas */
    .ticket::after{content:'';display:block;height:14px;
      background:linear-gradient(-45deg,#f0ece3 25%,transparent 25%) -6px 0,
                  linear-gradient(45deg,#f0ece3 25%,transparent 25%) -6px 0,
                  linear-gradient(-45deg,transparent 75%,#f0ece3 75%) 0 0,
                  linear-gradient(45deg,transparent 75%,#f0ece3 75%) 0 0;
      background-size:14px 14px;background-color:#fff;
      position:absolute;bottom:-14px;left:0;right:0}

    .t-head{background:#1a1a1a;padding:18px 30px 14px;text-align:center}
    .t-head .eyebrow{font-size:.58rem;letter-spacing:.4em;color:#c9a84c;text-transform:uppercase;font-family:'Inter',sans-serif;display:block;margin-bottom:4px}
    .t-head h1{font-family:'Playfair Display',serif;font-size:28px;color:#fff;font-weight:400;letter-spacing:2px}
    .t-head em{color:#c9a84c;font-style:italic}
    .gold-line{width:36px;height:1px;background:#c9a84c;margin:8px auto 0}

    .t-statut{padding:7px 30px;text-align:center;font-size:.65rem;letter-spacing:.22em;text-transform:uppercase;font-weight:600;color:<?= $statutColor ?>;background:<?= $statutColor ?>10;border-bottom:1px dashed #eee}

    .t-body{padding:16px 30px}

    .code-box{background:#1a1a1a;border-radius:7px;padding:13px;text-align:center;margin-bottom:14px}
    .code-lbl{font-size:.58rem;letter-spacing:.3em;text-transform:uppercase;color:#c9a84c;margin-bottom:4px;font-family:'Inter',sans-serif}
    .code-val{font-family:'Courier New',Courier,monospace;font-size:24px;letter-spacing:.18em;color:#c9a84c;font-weight:700}
    .code-hint{font-size:.65rem;color:#666;margin-top:4px;font-family:'Inter',sans-serif}

    table.details{width:100%;border-collapse:collapse}
    table.details tr{border-bottom:1px solid #f0f0f0}
    table.details tr:last-child{border-bottom:none}
    table.details td{padding:7px 0;vertical-align:top}
    table.details .lbl{font-size:.56rem;letter-spacing:.18em;text-transform:uppercase;color:#aaa;width:110px;padding-right:10px;font-family:'Inter',sans-serif}
    table.details .val{font-size:.85rem;color:#222;font-weight:500}

    .t-sep{display:flex;align-items:center;margin:0 -1px}
    .sep-circle{width:22px;height:22px;background:#f0ece3;border-radius:50%;flex-shrink:0;border:1px solid #ddd}
    .sep-line{flex:1;border-top:1px dashed #ddd}

    .t-foot{background:#fafafa;padding:12px 30px;text-align:center}
    .barcode{display:flex;justify-content:center;align-items:flex-end;gap:2px;height:32px;margin-bottom:5px}
    .bar{background:#333;border-radius:1px}
    .t-foot p{font-size:.65rem;color:#aaa;letter-spacing:.06em;font-family:'Inter',sans-serif;line-height:1.6}

    @media print{
      @page { margin: 0; size: auto; }
      html, body{
        background:#f0ece3 !important;
        -webkit-print-color-adjust:exact !important;
        print-color-adjust:exact !important;
        padding:20px !important;
        justify-content:center;
      }
      .toolbar{display:none !important}
      .ticket{
        box-shadow:none !important;
        margin-top:10px !important;
      }
    }
  </style>
</head>
<body>

  <div class="toolbar">
    <a href="mes_reservations.php" class="btn-back">← Mes réservations</a>
    <button onclick="window.print()" class="btn-print">🖨 Imprimer / PDF</button>
  </div>

  <div class="ticket">

    <div class="t-head">
      <span class="eyebrow">Restaurant</span>
      <h1>Le <em>Gourmet</em></h1>
      <div class="gold-line"></div>
    </div>

    <div class="t-statut"><?= $statutLabel ?></div>

    <div class="t-body">

      <div class="code-box">
        <div class="code-lbl">Code de réservation</div>
        <div class="code-val"><?= htmlspecialchars($res['code_confirmation']) ?></div>
        <div class="code-hint">Présentez ce code à votre arrivée</div>
      </div>

      <table class="details">
        <tr>
          <td class="lbl">Client</td>
          <td class="val"><?= htmlspecialchars($res['nom_client']) ?></td>
        </tr>
        <tr>
          <td class="lbl">Date</td>
          <td class="val"><?= $date ?></td>
        </tr>
        <tr>
          <td class="lbl">Heure</td>
          <td class="val"><?= htmlspecialchars($res['heure'] ?? '--:--') ?> <span style="color:#aaa;font-size:.85rem">(<?= ucfirst(htmlspecialchars($res['service'] ?? '')) ?>)</span></td>
        </tr>
        <tr>
          <td class="lbl">Couverts</td>
          <td class="val"><?= $res['nombre_personnes'] ?> personne<?= $res['nombre_personnes']>1?'s':'' ?></td>
        </tr>
        <tr>
          <td class="lbl">Table</td>
          <td class="val">
            <?php if ($res['numero_table']): ?>
              N°<?= $res['numero_table'] ?><?= $res['zone_table'] ? ' — '.htmlspecialchars($res['zone_table']) : '' ?>
            <?php else: ?>
              <span style="color:#bbb;font-style:italic;font-size:.88rem">Attribuée à l'arrivée</span>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <td class="lbl">Téléphone</td>
          <td class="val"><?= htmlspecialchars($res['telephone']) ?></td>
        </tr>
        <?php if (!empty($res['email'])): ?>
        <tr>
          <td class="lbl">Email</td>
          <td class="val" style="font-size:.88rem;color:#555"><?= htmlspecialchars($res['email']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($res['commentaires'])): ?>
        <tr>
          <td class="lbl">Note</td>
          <td class="val" style="font-size:.88rem;color:#555"><?= htmlspecialchars($res['commentaires']) ?></td>
        </tr>
        <?php endif; ?>
      </table>

    </div>

    <!-- Séparateur déchiré -->
    <div class="t-sep">
      <div class="sep-circle" style="margin-left:-14px"></div>
      <div class="sep-line"></div>
      <div class="sep-circle" style="margin-right:-14px"></div>
    </div>

    <!-- Footer code barre décoratif -->
    <div class="t-foot">
      <div class="barcode">
        <?php
          $c = $res['code_confirmation'];
          for ($i=0;$i<42;$i++) {
            $v = ord($c[$i % strlen($c)]);
            $h = 16 + ($v % 30);
            $w = ($i%3===0)?4:2;
            echo "<div class='bar' style='height:{$h}px;width:{$w}px'></div>";
          }
        ?>
      </div>
      <p><?= htmlspecialchars($res['code_confirmation']) ?></p>
      <p>Émis le <?= date('d/m/Y à H:i') ?> · Le Gourmet</p>
    </div>

  </div>

<script>
  // Impression manuelle uniquement via le bouton
</script>

</body>
</html>