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

// Load sample images
$db->query("CREATE TABLE IF NOT EXISTS service_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$svcId   = (int)$svc['id'];
$samples = $db->query("SELECT id, image_path FROM service_images WHERE service_id=$svcId ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);

// Combine main image + samples, deduplicated
$gallery = [];
$seen    = [];
if ($svc['image_path']) {
    $gallery[] = ['path' => $svc['image_path'], 'id' => null];
    $seen[$svc['image_path']] = true;
}
foreach ($samples as $s) {
    if (!isset($seen[$s['image_path']])) {
        $gallery[] = ['path' => $s['image_path'], 'id' => $s['id']];
        $seen[$s['image_path']] = true;
    }
}

$catLabels = ['basic' => 'Basic Services', 'sublimation' => 'Sublimation Services', 'signage' => 'Signage Services'];
$catLabel  = $catLabels[$svc['category'] ?? 'basic'] ?? 'Services';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" href="assets/pw.png">
<title>Printworld - Services</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .svc-hero { background:#111; color:#fff; padding:80px 0 40px; }
    .svc-hero .breadcrumb { font-size:0.8rem; color:rgba(255,255,255,0.4); margin-bottom:12px; }
    .svc-hero .breadcrumb a { color:rgba(255,255,255,0.4); text-decoration:none; }
    .svc-hero .breadcrumb a:hover { color:#fff; }
    .svc-hero h1 { font-size:2.2rem; font-weight:900; letter-spacing:1px; margin-bottom:10px; }
    .svc-hero p { color:rgba(255,255,255,0.6); font-size:1rem; max-width:560px; }
    .svc-body { padding:60px 0; background:#f8f8f8; min-height:60vh; }
    .svc-gallery { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px; margin-bottom:40px; }
    .svc-gallery-item { position:relative; aspect-ratio:4/3; overflow:hidden; border-radius:8px; cursor:pointer; box-shadow:0 2px 12px rgba(0,0,0,.08); background:#fff; }
    .svc-gallery-item img { width:100%; height:100%; object-fit:cover; transition:transform .4s ease; }
    .svc-gallery-item:hover img { transform:scale(1.06); }
    .svc-gallery-item .svc-overlay { position:absolute; inset:0; background:rgba(0,0,0,.35); opacity:0; transition:opacity .3s; display:flex; align-items:center; justify-content:center; }
    .svc-gallery-item:hover .svc-overlay { opacity:1; }
    .svc-gallery-item .svc-overlay i { color:#fff; font-size:1.8rem; }
    .svc-no-image { text-align:center; padding:80px 40px; color:#aaa; }
    .svc-no-image i { font-size:3rem; margin-bottom:16px; display:block; }
    .svc-cta { text-align:center; margin-top:48px; }
    /* Lightbox with zoom+drag */
    .svc-lb { display:none; position:fixed; inset:0; background:rgba(0,0,0,.95); z-index:9999; flex-direction:column; }
    .svc-lb.open { display:flex; }
    .svc-lb-toolbar { display:flex; align-items:center; justify-content:space-between; padding:12px 20px; flex-shrink:0; }
    .svc-lb-counter { color:rgba(255,255,255,.6); font-size:.85rem; }
    .svc-lb-controls { display:flex; gap:8px; }
    .svc-lb-btn { background:rgba(255,255,255,.12); color:#fff; border:none; border-radius:5px; padding:7px 12px; cursor:pointer; font-size:.85rem; transition:background .2s; display:flex; align-items:center; gap:5px; }
    .svc-lb-btn:hover { background:rgba(255,255,255,.25); }
    .svc-lb-viewport { flex:1; overflow:hidden; position:relative; display:flex; align-items:center; justify-content:center; }
    .svc-lb-img-wrap { position:absolute; cursor:grab; user-select:none; touch-action:none; }
    .svc-lb-img-wrap.dragging { cursor:grabbing; }
    .svc-lb-img-wrap img { display:block; max-width:90vw; max-height:80vh; object-fit:contain; pointer-events:none; border-radius:3px; }
    .svc-lb-nav { position:absolute; top:50%; transform:translateY(-50%); color:#fff; font-size:1.6rem; cursor:pointer; background:rgba(255,255,255,.1); border:none; padding:14px 16px; border-radius:5px; transition:background .2s; z-index:2; }
    .svc-lb-nav:hover { background:rgba(255,255,255,.25); }
    .svc-lb-nav.prev { left:12px; }
    .svc-lb-nav.next { right:12px; }
    .svc-lb-zoom-hint { position:absolute; bottom:16px; left:50%; transform:translateX(-50%); color:rgba(255,255,255,.35); font-size:.75rem; pointer-events:none; }
    @media(max-width:768px){
      .svc-hero { padding:90px 0 32px; }
      .svc-hero h1 { font-size:1.6rem; }
      .svc-body { padding:40px 0; }
      .svc-gallery { grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:10px; }
    }
    @media(max-width:480px){
      .svc-hero h1 { font-size:1.3rem; }
      .svc-hero p { font-size:.9rem; }
      .svc-gallery { grid-template-columns:1fr 1fr; }
    }
  </style>
</head>
<body>

<!-- NAV -->
<nav class="navbar" id="navbar">
  <div class="nav-inner">
    <a href="index.php" class="nav-logo">
      <img src="assets/pw.png" alt="Printworld Logo" style="height:36px;width:36px;object-fit:contain;border-radius:6px">
      <?= SITE_NAME ?><span>.</span>
    </a>
    <ul class="nav-links">
      <li><a href="index.php#services">Services</a></li>
      <li><a href="index.php#gallery">Gallery</a></li>
      <li><a href="index.php#about">About</a></li>
      <li><a href="index.php#contact">Contact</a></li>
      <li><a href="quotation.php" class="nav-cta">Request Quote</a></li>
    </ul>
    <div class="nav-toggle" aria-label="Toggle menu">
      <span></span><span></span><span></span>
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
    <?php if (!empty($gallery)): ?>
    <div class="svc-gallery">
      <?php foreach ($gallery as $idx => $img): ?>
      <div class="svc-gallery-item" onclick="openLb(<?= $idx ?>)">
        <img src="<?= htmlspecialchars($img['path']) ?>" alt="<?= htmlspecialchars($svc['name']) ?> sample <?= $idx+1 ?>" loading="lazy">
        <div class="svc-overlay"><i class="fas fa-expand"></i></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="svc-no-image">
      <i class="fas <?= htmlspecialchars($svc['icon'] ?? 'fa-print') ?>"></i>
      <p style="font-size:1rem;font-weight:600;margin-bottom:8px"><?= htmlspecialchars($svc['name']) ?></p>
      <p style="font-size:0.9rem">No images uploaded yet for this service.</p>
    </div>
    <?php endif; ?>

    <div class="svc-cta">
      <p style="color:#666;margin-bottom:20px;font-size:0.95rem">Interested in this service? Request a quotation now.</p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="quotation.php" class="btn btn-dark"><i class="fas fa-file-invoice"></i> Request a Quotation</a>
        <a href="index.php#services" class="btn btn-outline" style="color:#111;border-color:#ccc"><i class="fas fa-arrow-left"></i> Back to Services</a>
      </div>
    </div>
  </div>
</div>

<!-- Lightbox with zoom + drag -->
<div class="svc-lb" id="svc-lb">
  <div class="svc-lb-toolbar">
    <div class="svc-lb-counter" id="svc-lb-counter"></div>
    <div class="svc-lb-controls">
      <button class="svc-lb-btn" onclick="lbZoom(0.25)"><i class="fas fa-magnifying-glass-plus"></i> Zoom In</button>
      <button class="svc-lb-btn" onclick="lbZoom(-0.25)"><i class="fas fa-magnifying-glass-minus"></i> Zoom Out</button>
      <button class="svc-lb-btn" onclick="lbReset()"><i class="fas fa-compress"></i> Reset</button>
      <button class="svc-lb-btn" onclick="closeLb()"><i class="fas fa-xmark"></i> Close</button>
    </div>
  </div>
  <div class="svc-lb-viewport" id="svc-lb-viewport">
    <div class="svc-lb-img-wrap" id="svc-lb-wrap">
      <img id="svc-lb-img" src="" alt="">
    </div>
    <button class="svc-lb-nav prev" onclick="lbNav(-1)"><i class="fas fa-chevron-left"></i></button>
    <button class="svc-lb-nav next" onclick="lbNav(1)"><i class="fas fa-chevron-right"></i></button>
    <div class="svc-lb-zoom-hint">Scroll to zoom · Drag to pan · Pinch to zoom on mobile</div>
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
<script>
(function () {
  var imgs   = <?= json_encode(array_column($gallery, 'path')) ?>;
  var idx    = 0;
  var scale  = 1;
  var tx = 0, ty = 0;
  var dragging = false;
  var dragStartX, dragStartY, dragTx, dragTy;
  var pinchDist = null;

  var lb      = document.getElementById('svc-lb');
  var wrap    = document.getElementById('svc-lb-wrap');
  var img     = document.getElementById('svc-lb-img');
  var counter = document.getElementById('svc-lb-counter');

  function applyTransform() {
    wrap.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')';
  }

  function resetView() {
    scale = 1; tx = 0; ty = 0;
    wrap.style.transition = 'transform .25s ease';
    applyTransform();
    setTimeout(function () { wrap.style.transition = ''; }, 260);
  }

  function showImg() {
    img.src = imgs[idx];
    counter.textContent = (idx + 1) + ' / ' + imgs.length;
    resetView();
  }

  window.openLb = function (i) {
    idx = i;
    showImg();
    lb.classList.add('open');
  };
  window.closeLb = function () { lb.classList.remove('open'); };
  window.lbNav   = function (dir) { idx = (idx + dir + imgs.length) % imgs.length; showImg(); };
  window.lbZoom  = function (delta) {
    scale = Math.min(5, Math.max(0.5, scale + delta));
    wrap.style.transition = 'transform .15s ease';
    applyTransform();
    setTimeout(function () { wrap.style.transition = ''; }, 160);
  };
  window.lbReset = resetView;

  // Click backdrop to close
  lb.addEventListener('click', function (e) {
    if (e.target === lb || e.target.id === 'svc-lb-viewport') closeLb();
  });

  // Keyboard
  document.addEventListener('keydown', function (e) {
    if (!lb.classList.contains('open')) return;
    if (e.key === 'ArrowRight') lbNav(1);
    if (e.key === 'ArrowLeft')  lbNav(-1);
    if (e.key === 'Escape')     closeLb();
    if (e.key === '+' || e.key === '=') lbZoom(0.25);
    if (e.key === '-') lbZoom(-0.25);
    if (e.key === '0') lbReset();
  });

  // Mouse wheel zoom (centered on cursor)
  document.getElementById('svc-lb-viewport').addEventListener('wheel', function (e) {
    e.preventDefault();
    var delta = e.deltaY < 0 ? 0.15 : -0.15;
    var newScale = Math.min(5, Math.max(0.5, scale + delta));
    // Adjust translation so zoom centers on mouse position
    var rect = wrap.getBoundingClientRect();
    var mx = e.clientX - rect.left - rect.width  / 2;
    var my = e.clientY - rect.top  - rect.height / 2;
    tx += mx * (1 - newScale / scale);
    ty += my * (1 - newScale / scale);
    scale = newScale;
    applyTransform();
  }, { passive: false });

  // Mouse drag
  wrap.addEventListener('mousedown', function (e) {
    if (e.button !== 0) return;
    dragging = true;
    dragStartX = e.clientX; dragStartY = e.clientY;
    dragTx = tx; dragTy = ty;
    wrap.classList.add('dragging');
    e.preventDefault();
  });
  document.addEventListener('mousemove', function (e) {
    if (!dragging) return;
    tx = dragTx + (e.clientX - dragStartX);
    ty = dragTy + (e.clientY - dragStartY);
    applyTransform();
  });
  document.addEventListener('mouseup', function () {
    dragging = false;
    wrap.classList.remove('dragging');
  });

  // Touch: drag + pinch-to-zoom
  wrap.addEventListener('touchstart', function (e) {
    if (e.touches.length === 1) {
      dragging = true;
      dragStartX = e.touches[0].clientX; dragStartY = e.touches[0].clientY;
      dragTx = tx; dragTy = ty;
      pinchDist = null;
    } else if (e.touches.length === 2) {
      dragging = false;
      pinchDist = Math.hypot(
        e.touches[0].clientX - e.touches[1].clientX,
        e.touches[0].clientY - e.touches[1].clientY
      );
    }
    e.preventDefault();
  }, { passive: false });

  wrap.addEventListener('touchmove', function (e) {
    if (e.touches.length === 1 && dragging) {
      tx = dragTx + (e.touches[0].clientX - dragStartX);
      ty = dragTy + (e.touches[0].clientY - dragStartY);
      applyTransform();
    } else if (e.touches.length === 2 && pinchDist !== null) {
      var newDist = Math.hypot(
        e.touches[0].clientX - e.touches[1].clientX,
        e.touches[0].clientY - e.touches[1].clientY
      );
      scale = Math.min(5, Math.max(0.5, scale * (newDist / pinchDist)));
      pinchDist = newDist;
      applyTransform();
    }
    e.preventDefault();
  }, { passive: false });

  wrap.addEventListener('touchend', function () {
    dragging = false; pinchDist = null;
  });

  // Double-tap to reset
  var lastTap = 0;
  wrap.addEventListener('touchend', function (e) {
    var now = Date.now();
    if (now - lastTap < 300) resetView();
    lastTap = now;
  });

  // Double-click to reset on desktop
  wrap.addEventListener('dblclick', resetView);

})();
</script>
</body>
</html>
