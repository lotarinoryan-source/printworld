<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug'] ?? ''));
if (!$slug) { header('Location: index.php#services'); exit; }

$db   = db();
$stmt = $db->prepare("SELECT * FROM service_categories WHERE slug=? AND is_active=1 LIMIT 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$svc  = $stmt->get_result()->fetch_assoc();
if (!$svc) { header('Location: index.php#services'); exit; }

$catLabels = ['basic' => 'Basic Services', 'sublimation' => 'Sublimation Services', 'signage' => 'Signage Services'];
$catLabel  = $catLabels[$svc['category'] ?? 'basic'] ?? 'Services';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" href="assets/pw.png">
<title>Printworld</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .svc-hero { background:#111; color:#fff; padding:60px 0 40px; }
    .svc-hero .breadcrumb { font-size:0.8rem; color:rgba(255,255,255,0.4); margin-bottom:12px; }
    .svc-hero .breadcrumb a { color:rgba(255,255,255,0.4); text-decoration:none; }
    .svc-hero .breadcrumb a:hover { color:#fff; }
    .svc-hero h1 { font-size:2.2rem; font-weight:900; letter-spacing:1px; margin-bottom:10px; }
    .svc-hero p { color:rgba(255,255,255,0.6); font-size:1rem; max-width:560px; }
    .svc-body { padding:60px 0; background:#f8f8f8; min-height:60vh; }
    .svc-image-wrap { background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08); max-width:720px; margin:0 auto; }
    .svc-image-wrap img { width:100%; display:block; max-height:520px; object-fit:contain; background:#fafafa; }
    .svc-no-image { text-align:center; padding:80px 40px; color:#aaa; }
    .svc-no-image i { font-size:3rem; margin-bottom:16px; display:block; }
    .svc-cta { text-align:center; margin-top:48px; }
  </style>
</head>
<body>

<!-- NAV -->
<nav class="navbar" id="navbar">
  <div class="container nav-container">
    <a href="index.php" class="nav-logo"><?= SITE_NAME ?></a>
    <div class="nav-links">
      <a href="index.php#services">Services</a>
      <a href="index.php#gallery">Gallery</a>
      <a href="index.php#about">About</a>
      <a href="index.php#contact">Contact</a>
      <a href="quotation.php" class="btn btn-dark btn-sm">Get a Quote</a>
    </div>
  </div>
</nav>

<div class="svc-hero">
  <div class="container">
    <div class="breadcrumb">
      <a href="index.php">Home</a> &rsaquo;
      <a href="index.php#services"><?= htmlspecialchars($catLabel) ?></a> &rsaquo;
      <?= htmlspecialchars($svc['name']) ?>
    </div>
    <h1><i class="fas <?= htmlspecialchars($svc['icon'] ?? 'fa-print') ?>" style="margin-right:12px"></i><?= htmlspecialchars($svc['name']) ?></h1>
    <?php if ($svc['description']): ?>
    <p><?= htmlspecialchars($svc['description']) ?></p>
    <?php endif; ?>
  </div>
</div>

<div class="svc-body">
  <div class="container">
    <?php if ($svc['image_path']): ?>
    <div class="svc-image-wrap">
      <img src="<?= htmlspecialchars($svc['image_path']) ?>" alt="<?= htmlspecialchars($svc['name']) ?>">
    </div>
    <?php else: ?>
    <div class="svc-no-image">
      <i class="fas <?= htmlspecialchars($svc['icon'] ?? 'fa-print') ?>"></i>
      <p style="font-size:1rem;font-weight:600;margin-bottom:8px"><?= htmlspecialchars($svc['name']) ?></p>
      <p style="font-size:0.9rem">No image uploaded yet for this service.</p>
    </div>
    <?php endif; ?>

    <div class="svc-cta">
      <p style="color:#666;margin-bottom:20px;font-size:0.95rem">Interested in this service? Request a quotation now.</p>
      <a href="quotation.php" class="btn btn-dark"><i class="fas fa-file-invoice"></i> Request a Quotation</a>
      <a href="index.php#services" class="btn btn-outline" style="margin-left:12px;color:#111;border-color:#ccc"><i class="fas fa-arrow-left"></i> Back to Services</a>
    </div>
  </div>
</div>

<footer>
  <div class="container">
    <div class="footer-logo"><?= SITE_NAME ?></div>
    <p style="margin-bottom:12px">Premium Printing Solutions · Digos City, Davao del Sur</p>
    <p style="font-size:0.78rem">© <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
  </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
