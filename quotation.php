<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
$db = db();
$svcResult = $db->query("SELECT * FROM service_categories WHERE is_active=1 ORDER BY category, sort_order, id");
$servicesByCategory = ['basic' => [], 'sublimation' => [], 'signage' => []];
while ($row = $svcResult->fetch_assoc()) { $servicesByCategory[$row['category'] ?? 'basic'][] = $row; }
$errors = $_SESSION['errors'] ?? []; unset($_SESSION['errors']);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" href="assets/pw.png">
<title>Printworld - Request Quotation</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
body{font-family:'Inter',Arial,sans-serif;background:#f5f5f5;margin:0}
.qt-wrap{max-width:900px;margin:0 auto;padding:40px 20px 80px}
.qt-title{font-size:2rem;font-weight:800;margin-bottom:6px}
.qt-sub{color:#666;margin-bottom:32px}
.qt-section{background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:28px;margin-bottom:24px}
.qt-section h3{font-size:1rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin:0 0 20px;padding-bottom:10px;border-bottom:2px solid #111}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:.85rem;font-weight:600;margin-bottom:6px;color:#333}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:5px;font-size:.9rem;font-family:inherit;box-sizing:border-box}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#111}
.form-group select:disabled{background:#f0f0f0;color:#999;cursor:not-allowed}
.tab-nav{display:flex;border-bottom:2px solid #e5e5e5;margin-bottom:24px}
.tab-btn{padding:10px 20px;font-size:.85rem;font-weight:600;cursor:pointer;border:none;background:none;color:#888;border-bottom:3px solid transparent;margin-bottom:-2px}
.tab-btn.active{color:#111;border-bottom-color:#111}
.tab-pane{display:none}.tab-pane.active{display:block}
.svc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:16px}
.svc-card{border:2px solid #e5e5e5;border-radius:8px;padding:16px 12px;text-align:center;cursor:pointer;background:#fff;transition:border-color .15s}
.svc-card:hover{border-color:#888}.svc-card.selected{border-color:#111;background:#f8f8f8}
.svc-card i{font-size:1.4rem;margin-bottom:8px;color:#555;display:block}
.svc-card span{font-size:.82rem;font-weight:600}
.svc-config{display:none;background:#f9f9f9;border:1px solid #e0e0e0;border-radius:6px;padding:20px;margin-top:12px}
.svc-config.show{display:block}
.add-item-btn{background:#111;color:#fff;border:none;padding:10px 20px;border-radius:5px;font-size:.85rem;font-weight:600;cursor:pointer;margin-top:12px}
.add-item-btn:hover{background:#333}
.cart-list{list-style:none;padding:0;margin:0}
.cart-item{display:flex;align-items:flex-start;justify-content:space-between;padding:12px 0;border-bottom:1px solid #eee;gap:12px}
.cart-item:last-child{border-bottom:none}
.cart-item-name{font-weight:700;font-size:.9rem}
.cart-item-desc{font-size:.8rem;color:#666;margin-top:2px}
.cart-remove{background:none;border:none;color:#c00;cursor:pointer;font-size:1rem;padding:0 4px}
.empty-cart{text-align:center;padding:24px;color:#aaa;font-size:.9rem}
.submit-btn{background:#111;color:#fff;border:none;padding:16px 40px;border-radius:6px;font-size:1rem;font-weight:700;cursor:pointer;width:100%;letter-spacing:1px}
.submit-btn:disabled{background:#ccc;cursor:not-allowed}
.submit-btn:not(:disabled):hover{background:#333}
.alert-error{background:#fee;border:1px solid #fcc;color:#c00;padding:12px 16px;border-radius:5px;margin-bottom:20px;font-size:.9rem}
.info-note{background:#fffbe6;border:1px solid #ffe58f;border-radius:5px;padding:10px 14px;font-size:.85rem;color:#7a5c00;margin-bottom:16px}
.lock-note{font-size:.78rem;color:#888;margin-top:4px;font-style:italic}
.svc-loading{text-align:center;padding:16px;color:#aaa;font-size:.9rem}
@media(max-width:600px){.form-row{grid-template-columns:1fr}.svc-grid{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<nav style="background:#111;padding:16px 32px;display:flex;align-items:center;justify-content:space-between">
  <a href="index.php" style="color:#fff;text-decoration:none;font-size:1.2rem;font-weight:800;letter-spacing:2px">PRINTWORLD</a>
  <a href="index.php" style="color:#aaa;text-decoration:none;font-size:.85rem"><i class="fas fa-arrow-left"></i> Back to Home</a>
</nav>
<div class="qt-wrap">
  <h1 class="qt-title">Request a Quotation</h1>
  <p class="qt-sub">Fill in your details and select the services you need. We will get back to you with pricing.</p>
  <?php if (!empty($errors)): ?>
  <div class="alert-error"><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
  <?php endif; ?>
  <form method="POST" action="process_quotation.php" enctype="multipart/form-data" id="qt-form">
    <input type="hidden" name="items_data" id="items_data" value="[]">
    <div class="qt-section">
      <h3><i class="fas fa-user" style="margin-right:8px"></i>Your Information</h3>
      <div class="form-row">
        <div class="form-group"><label>Full Name *</label><input type="text" name="customer_name" required placeholder="Juan dela Cruz"></div>
        <div class="form-group"><label>Company / Business Name</label><input type="text" name="company_name" placeholder="Optional"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Email Address *</label><input type="email" name="email" required placeholder="you@email.com"></div>
        <div class="form-group"><label>Contact Number *</label><input type="text" name="contact_number" required placeholder="09XXXXXXXXX"></div>
      </div>
      <div class="form-group"><label><i class="fas fa-map-marker-alt" style="margin-right:4px"></i>Location / Address</label><input type="text" name="location" placeholder="Your city, barangay, or full address"></div>
      <div class="form-group"><label>Additional Message</label><textarea name="message" rows="3" placeholder="Any special instructions or notes..."></textarea></div>
    </div>
    <div class="qt-section">
      <h3><i class="fas fa-print" style="margin-right:8px"></i>Select Services</h3>
      <div class="tab-nav">
        <button type="button" class="tab-btn active" onclick="switchTab('basic',this)">Basic Services</button>
        <button type="button" class="tab-btn" onclick="switchTab('sublimation',this)">Sublimation</button>
        <button type="button" class="tab-btn" onclick="switchTab('signage',this)">Signage</button>
      </div>
      <div class="tab-pane active" id="tab-basic">
        <div class="svc-grid">
          <?php foreach ($servicesByCategory['basic'] as $svc): ?>
          <div class="svc-card" onclick="selectService(this,'<?= htmlspecialchars($svc['slug']) ?>','<?= htmlspecialchars($svc['name']) ?>','basic')">
            <i class="fas <?= htmlspecialchars($svc['icon'] ?? 'fa-print') ?>"></i><span><?= htmlspecialchars($svc['name']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div id="config-basic" class="svc-config"></div>
      </div>
      <div class="tab-pane" id="tab-sublimation">
        <div class="info-note"><i class="fas fa-info-circle"></i> <strong>Free design</strong> for a minimum of 15 pcs. If you already have a design, just upload a photo below.</div>
        <div class="svc-grid">
          <?php foreach ($servicesByCategory['sublimation'] as $svc): ?>
          <div class="svc-card" onclick="selectService(this,'<?= htmlspecialchars($svc['slug']) ?>','<?= htmlspecialchars($svc['name']) ?>','sublimation')">
            <i class="fas <?= htmlspecialchars($svc['icon'] ?? 'fa-shirt') ?>"></i><span><?= htmlspecialchars($svc['name']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div id="config-sublimation" class="svc-config"></div>
      </div>
      <div class="tab-pane" id="tab-signage">
        <div id="signage-grid" class="svc-grid">
          <div class="svc-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
        </div>
        <div id="config-signage" class="svc-config"></div>
      </div>
    </div>
    <div class="qt-section">
      <h3><i class="fas fa-list-check" style="margin-right:8px"></i>Your Request Summary</h3>
      <ul class="cart-list" id="cart-list"><li class="empty-cart" id="cart-empty">No items added yet. Select a service above.</li></ul>
    </div>
    <div class="qt-section">
      <h3><i class="fas fa-file-image" style="margin-right:8px"></i>Design File (Optional)</h3>
      <div class="form-group">
        <label>Upload your design or reference photo (JPG, PNG, PDF - max 5MB)</label>
        <input type="file" name="design_file" accept=".jpg,.jpeg,.png,.pdf,.ai,.psd">
      </div>
    </div>
    <button type="submit" class="submit-btn" id="submit-btn" disabled><i class="fas fa-paper-plane"></i> &nbsp;SUBMIT REQUEST</button>
    <p style="text-align:center;font-size:.8rem;color:#999;margin-top:12px">We will review your request and send you a detailed quotation.</p>
  </form>
</div><script>
var cart = [];
var SIZE_BASIC = { 'tarpaulin': 'ft', 'sintraboard': 'inches' };
// Frame types that always force Non-lighted
var FRAME_NONLIT = ['Single Frame', 'Double Face Frame'];

function switchTab(cat, btn) {
  document.querySelectorAll('.tab-pane').forEach(function(p){ p.classList.remove('active'); });
  document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
  document.getElementById('tab-' + cat).classList.add('active');
  btn.classList.add('active');
  clearConfig();
  if (cat === 'signage') loadSignageCards();
}

function loadSignageCards() {
  var grid = document.getElementById('signage-grid');
  grid.innerHTML = '<div class="svc-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
  fetch('ajax_signage_config.php?action=services')
    .then(function(r){ return r.json(); })
    .then(function(services) {
      if (!services.length) { grid.innerHTML = '<p style="color:#aaa;font-size:.9rem">No signage services available.</p>'; return; }
      grid.innerHTML = services.map(function(svc) {
        var icon = svc.icon || 'fa-sign-hanging';
        var slug = svc.slug.replace(/'/g, "\\'");
        var name = svc.name.replace(/'/g, "\\'");
        return '<div class="svc-card" onclick="selectService(this,\'' + slug + '\',\'' + name + '\',\'signage\')">'
          + '<i class="fas ' + icon + '"></i><span>' + svc.name + '</span></div>';
      }).join('');
    })
    .catch(function(){ grid.innerHTML = '<p style="color:#c00;font-size:.9rem">Failed to load services.</p>'; });
}

function clearConfig() {
  ['basic','sublimation','signage'].forEach(function(c) {
    var el = document.getElementById('config-' + c);
    if (el) { el.innerHTML = ''; el.classList.remove('show'); }
  });
  document.querySelectorAll('.svc-card').forEach(function(c){ c.classList.remove('selected'); });
}

function selectService(card, slug, name, cat) {
  card.closest('.tab-pane').querySelectorAll('.svc-card').forEach(function(c){ c.classList.remove('selected'); });
  card.classList.add('selected');
  var d = document.getElementById('config-' + cat);
  d.classList.add('show');
  if (cat === 'basic') renderBasic(d, slug, name);
  else if (cat === 'sublimation') renderSublimation(d, slug, name);
  else if (cat === 'signage') renderSignage(d, slug, name);
}

function designNote() {
  return '<div class="info-note" style="margin-top:4px"><i class="fas fa-info-circle"></i> If you already have a design, please include the details in the message above or upload a photo of your design.</div>';
}

/* BASIC */
function renderBasic(div, slug, name) {
  var unit = SIZE_BASIC[slug];
  var sizeHtml = unit
    ? '<div class="form-row"><div class="form-group" style="margin:0"><label>Width (' + unit + ') *</label><input type="number" id="cfg-w" min="0.1" step="0.1" placeholder="e.g. 4"></div><div class="form-group" style="margin:0"><label>Height (' + unit + ') *</label><input type="number" id="cfg-h" min="0.1" step="0.1" placeholder="e.g. 3"></div></div><br>' : '';
  var qtyHtml = !unit
    ? '<div class="form-row"><div class="form-group" style="margin:0"><label>Quantity *</label><input type="number" id="cfg-qty" min="1" value="1"></div><div class="form-group" style="margin:0"><label>Notes (optional)</label><input type="text" id="cfg-notes" placeholder="e.g. red color, custom text"></div></div><br>'
    : '<div class="form-group"><label>Notes (optional)</label><input type="text" id="cfg-notes" placeholder="e.g. full color, event text"></div>';
  div.innerHTML = '<h4 style="margin:0 0 16px;font-size:.95rem">' + name + '</h4>' + sizeHtml + qtyHtml + designNote()
    + '<button type="button" class="add-item-btn" onclick="addBasic(\'' + slug + '\',\'' + name + '\')"><i class="fas fa-plus"></i> Add to Request</button>';
}

function addBasic(slug, name) {
  var unit = SIZE_BASIC[slug];
  var notes = (document.getElementById('cfg-notes') || {}).value || '';
  var desc = name, details = {}, qty = 1;
  if (unit) {
    var w = parseFloat(document.getElementById('cfg-w').value) || 0;
    var h = parseFloat(document.getElementById('cfg-h').value) || 0;
    if (!w || !h) { alert('Please enter width and height.'); return; }
    desc += ' - ' + w + unit + ' x ' + h + unit;
    details = { width: w, height: h, unit: unit };
  } else {
    qty = parseInt(document.getElementById('cfg-qty').value) || 1;
  }
  if (notes) desc += ' - ' + notes;
  cart.push({ service_type: 'basic', item_name: name, slug: slug, description: desc, quantity: qty, details: details });
  updateCart(); clearConfig();
}

/* SUBLIMATION */
function renderSublimation(div, slug, name) {
  div.innerHTML = '<h4 style="margin:0 0 16px;font-size:.95rem">' + name + '</h4>'
    + '<div class="form-row"><div class="form-group" style="margin:0"><label>Quantity *</label><input type="number" id="cfg-qty" min="1" value="1"></div><div class="form-group" style="margin:0"><label>Notes (optional)</label><input type="text" id="cfg-notes" placeholder="e.g. size, color preference"></div></div><br>'
    + designNote()
    + '<button type="button" class="add-item-btn" onclick="addSublimation(\'' + slug + '\',\'' + name + '\')"><i class="fas fa-plus"></i> Add to Request</button>';
}

function addSublimation(slug, name) {
  var qty = parseInt(document.getElementById('cfg-qty').value) || 1;
  var notes = document.getElementById('cfg-notes').value;
  var desc = name + (notes ? ' - ' + notes : '');
  cart.push({ service_type: 'sublimation', item_name: name, slug: slug, description: desc, quantity: qty, details: {} });
  updateCart(); clearConfig();
}

/* SIGNAGE - AJAX config + auto-select rules */
function renderSignage(div, slug, name) {
  div.innerHTML = '<p class="svc-loading"><i class="fas fa-spinner fa-spin"></i> Loading options...</p>';
  fetch('ajax_signage_config.php?action=config&slug=' + encodeURIComponent(slug))
    .then(function(r){ return r.json(); })
    .then(function(cfg) {
      var types  = cfg.types  || [];
      var lights = cfg.lights || ['Lighted', 'Non-lighted'];
      var onlyNonLit = lights.length === 1 && lights[0] === 'Non-lighted';

      // Auto-select rules: neon -> Single Face + Lighted
      var isNeon = /neon/i.test(name) || /neon/i.test(slug);
      var defaultType  = isNeon ? 'Single Face' : null;
      var defaultLight = isNeon ? 'Lighted'     : null;

      var optHtml = types.map(function(t) {
        var sel = (defaultType && t === defaultType) ? ' selected' : '';
        return '<option value="' + t + '"' + sel + '>' + t + '</option>';
      }).join('');
      var lightHtml = lights.map(function(l) {
        var sel = (defaultLight && l === defaultLight) ? ' selected' : '';
        return '<option value="' + l + '"' + sel + '>' + l + '</option>';
      }).join('');

      div.innerHTML =
        '<h4 style="margin:0 0 16px;font-size:.95rem">' + name + '</h4>'
        + '<div class="form-row">'
          + '<div class="form-group" style="margin:0"><label>Signage Type *</label>'
            + '<select id="cfg-option" onchange="onSignageChange()">' + optHtml + '</select>'
          + '</div>'
          + '<div class="form-group" style="margin:0"><label>Light Option</label>'
            + '<select id="cfg-light"' + (onlyNonLit ? ' disabled data-locked="config"' : '') + '>' + lightHtml + '</select>'
            + '<p class="lock-note" id="light-note" style="display:none">Single Frame / Double Face Frame is always Non-lighted.</p>'
          + '</div>'
        + '</div>'
        + '<div class="form-row">'
          + '<div class="form-group" style="margin:0"><label>Width (ft) *</label><input type="number" id="cfg-w" min="0.1" step="0.1" placeholder="e.g. 4"></div>'
          + '<div class="form-group" style="margin:0"><label>Height (ft) *</label><input type="number" id="cfg-h" min="0.1" step="0.1" placeholder="e.g. 3"></div>'
        + '</div><br>'
        + designNote()
        + '<div class="form-group"><label>Notes (optional)</label><input type="text" id="cfg-notes" placeholder="e.g. logo color, text content"></div>'
        + '<button type="button" class="add-item-btn" onclick="addSignage(\'' + slug.replace(/'/g, "\\'") + '\',\'' + name.replace(/'/g, "\\'") + '\')"><i class="fas fa-plus"></i> Add to Request</button>';

      // Only run frame-lock check if not neon (neon is already set to Lighted)
      if (!isNeon) onSignageChange();
    })
    .catch(function(){ div.innerHTML = '<p style="color:#c00">Failed to load signage options.</p>'; });
}

function onSignageChange() {
  var optEl   = document.getElementById('cfg-option');
  var lightEl = document.getElementById('cfg-light');
  var noteEl  = document.getElementById('light-note');
  if (!optEl || !lightEl) return;
  if (lightEl.getAttribute('data-locked') === 'config') return;
  var forceNonLit = FRAME_NONLIT.indexOf(optEl.value) !== -1;
  lightEl.disabled = forceNonLit;
  if (forceNonLit) {
    lightEl.value = 'Non-lighted';
  } else {
    if (lightEl.options.length > 1) lightEl.value = lightEl.options[0].value;
  }
  if (noteEl) noteEl.style.display = forceNonLit ? 'block' : 'none';
}

function addSignage(slug, name) {
  var opt   = document.getElementById('cfg-option').value;
  var light = document.getElementById('cfg-light').value;
  var w     = parseFloat(document.getElementById('cfg-w').value) || 0;
  var h     = parseFloat(document.getElementById('cfg-h').value) || 0;
  var notes = document.getElementById('cfg-notes').value;
  if (!opt)     { alert('Please select a signage type.'); return; }
  if (!w || !h) { alert('Please enter width and height.'); return; }
  var desc = name + ' - ' + opt + ' - ' + light + ' - ' + w + 'ft x ' + h + 'ft';
  if (notes) desc += ' - ' + notes;
  cart.push({ service_type: 'signage', item_name: name, slug: slug, description: desc, quantity: 1,
    details: { option: opt, light: light, width: w, height: h } });
  updateCart(); clearConfig();
}

/* CART */
function removeItem(idx) { cart.splice(idx, 1); updateCart(); }

function updateCart() {
  var list  = document.getElementById('cart-list');
  var empty = document.getElementById('cart-empty');
  var sb    = document.getElementById('submit-btn');
  list.querySelectorAll('.cart-item').forEach(function(el){ el.remove(); });
  if (cart.length === 0) {
    empty.style.display = 'block'; sb.disabled = true;
  } else {
    empty.style.display = 'none'; sb.disabled = false;
    cart.forEach(function(item, idx) {
      var li = document.createElement('li');
      li.className = 'cart-item';
      li.innerHTML = '<div style="flex:1"><div class="cart-item-name">' + item.item_name
        + ' <span style="font-size:.75rem;color:#888;font-weight:400;text-transform:capitalize">(' + item.service_type + ')</span></div>'
        + '<div class="cart-item-desc">' + item.description + ' - Qty: ' + item.quantity + '</div></div>'
        + '<button type="button" class="cart-remove" onclick="removeItem(' + idx + ')" title="Remove"><i class="fas fa-times"></i></button>';
      list.appendChild(li);
    });
  }
  document.getElementById('items_data').value = JSON.stringify(cart);
}

loadSignageCards();
updateCart();
</script>
</body>
</html>