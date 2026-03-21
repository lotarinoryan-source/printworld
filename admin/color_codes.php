<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '_layout.php';
requireAdmin();

$db = db();
$msg = '';

// Handle form POST (save)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $client = trim($_POST['client_name'] ?? '');
        $codes  = $_POST['color_code'] ?? [];
        $names  = $_POST['color_name'] ?? [];
        if ($client) {
            $db->prepare("DELETE FROM client_color_codes WHERE client_name=?")->bind_param('s',$client) && null;
            $del = $db->prepare("DELETE FROM client_color_codes WHERE client_name=?");
            $del->bind_param('s', $client); $del->execute();
            $ins = $db->prepare("INSERT INTO client_color_codes (client_name,color_code,color_name) VALUES(?,?,?)");
            $n = 0;
            foreach ($codes as $i => $code) {
                $code = trim($code); $name = trim($names[$i] ?? '');
                if ($code) { $ins->bind_param('sss',$client,$code,$name); $ins->execute(); $n++; }
            }
            $msg = "success:Saved $n color code(s) for \"".htmlspecialchars($client)."\".";
        }
    }
}

$allClients = [];
$res = $db->query("SELECT * FROM client_color_codes ORDER BY client_name, id");
while ($row = $res->fetch_assoc()) $allClients[$row['client_name']][] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/pw.png">
  <title>Printworld - Color Sticker Codes</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .cc-search{position:relative;margin-bottom:18px}
    .cc-search input{width:100%;padding:10px 16px 10px 40px;border:1px solid #ddd;border-radius:4px;font-size:.9rem;box-sizing:border-box}
    .cc-search i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#aaa}
    .cc-card{background:#fff;border:1px solid #eee;border-radius:6px;margin-bottom:10px;overflow:hidden}
    .cc-head{display:flex;align-items:center;justify-content:space-between;padding:13px 18px;background:#fafafa;border-bottom:1px solid #eee;gap:10px}
    .cc-body{padding:14px 18px}
    .cc-badge{display:inline-flex;align-items:center;gap:6px;background:#f5f5f5;border:1px solid #eee;border-radius:4px;padding:4px 10px;font-size:.82rem;margin:3px}
    .cc-swatch{width:18px;height:18px;border-radius:3px;border:1px solid rgba(0,0,0,.12);flex-shrink:0;display:inline-block}
    .cc-row{display:flex;gap:8px;align-items:center;margin-bottom:8px}
    .cc-row input[type=color]{width:42px;height:36px;padding:2px;border:1px solid #ddd;border-radius:4px;cursor:pointer;flex-shrink:0}
    .pw-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:3000;align-items:center;justify-content:center;padding:20px}
    .pw-overlay.open{display:flex}
    .pw-box{background:#fff;padding:32px;width:100%;max-width:400px;border-radius:8px;text-align:center}
    .pw-toast-el{position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 22px;border-radius:4px;font-size:.85rem;color:#fff;opacity:0;transition:opacity .25s}
  </style>
</head>
<body class="admin-body">
<?php adminSidebar('color_codes'); ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1><i class="fas fa-palette" style="margin-right:8px"></i>Color Sticker Codes</h1>
    <button class="btn btn-dark" id="btn-add-new"><i class="fas fa-plus"></i> Add Color Codes</button>
  </div>

  <div id="page-msg" style="display:none" class="alert"></div>

  <div class="cc-search">
    <i class="fas fa-search"></i>
    <input type="text" id="search-box" placeholder="Search client / company name...">
  </div>

  <div id="cc-list">
    <?php if (empty($allClients)): ?>
    <div class="admin-card" style="text-align:center;padding:48px;color:#aaa">
      <i class="fas fa-palette" style="font-size:2rem;display:block;margin-bottom:12px"></i>
      No color codes yet. Click "Add Color Codes" to get started.
    </div>
    <?php else: ?>
      <?php foreach ($allClients as $clientName => $codes): ?>
      <div class="cc-card" data-client="<?= strtolower(htmlspecialchars($clientName)) ?>">
        <div class="cc-head">
          <div style="display:flex;align-items:center;gap:10px">
            <strong><?= htmlspecialchars($clientName) ?></strong>
            <span style="font-size:.78rem;color:#aaa"><?= count($codes) ?> color(s)</span>
          </div>
          <div style="display:flex;gap:8px">
            <button class="action-btn btn-edit-client" data-client="<?= htmlspecialchars($clientName) ?>">
              <i class="fas fa-pen"></i> Edit
            </button>
            <button class="action-btn danger btn-del-client" data-client="<?= htmlspecialchars($clientName) ?>">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
        <div class="cc-body">
          <div style="display:flex;flex-wrap:wrap;gap:4px">
            <?php foreach ($codes as $c): ?>
            <span class="cc-badge" id="badge-<?= $c['id'] ?>">
              <span class="cc-swatch" style="background:<?= htmlspecialchars($c['color_code']) ?>"></span>
              <span><?= htmlspecialchars($c['color_name'] ?: $c['color_code']) ?></span>
              <span style="color:#bbb;font-size:.72rem"><?= htmlspecialchars($c['color_code']) ?></span>
              <button class="btn-del-one" data-id="<?= $c['id'] ?>" data-label="<?= htmlspecialchars($c['color_name'] ?: $c['color_code']) ?>"
                style="background:none;border:none;cursor:pointer;color:#bbb;padding:0 0 0 4px;font-size:.8rem;line-height:1">&#x2715;</button>
            </span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<!-- Add/Edit Modal -->
<div id="form-modal" class="pw-overlay">
  <div style="background:#fff;padding:32px;width:100%;max-width:580px;border-radius:6px;max-height:90vh;overflow-y:auto">
    <h3 id="form-title" style="margin-bottom:20px">Add Color Codes</h3>
    <form method="POST" id="cc-form">
      <input type="hidden" name="action" value="save">
      <div class="form-group">
        <label>Client / Company Name *</label>
        <input type="text" name="client_name" id="f-client" class="form-control" placeholder="e.g. Mr DIY, Petron" required>
      </div>
      <div class="form-group">
        <label>Color Codes</label>
        <div id="cc-rows"></div>
        <button type="button" id="btn-add-row" class="action-btn" style="margin-top:8px">
          <i class="fas fa-plus"></i> Add Color Code
        </button>
      </div>
      <div style="display:flex;gap:12px;margin-top:16px">
        <button type="submit" class="btn btn-dark"><i class="fas fa-save"></i> Save</button>
        <button type="button" id="btn-close-form" class="action-btn">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div id="del-modal" class="pw-overlay">
  <div class="pw-box">
    <div style="width:54px;height:54px;background:#fff0f0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
      <i class="fas fa-trash" style="color:#c00;font-size:1.3rem"></i>
    </div>
    <h3 id="del-title" style="margin-bottom:8px;font-size:1.05rem">Delete Confirmation</h3>
    <p id="del-msg" style="color:#666;font-size:.88rem;margin-bottom:24px"></p>
    <div style="display:flex;gap:12px;justify-content:center">
      <button id="del-cancel" class="action-btn" style="min-width:100px">Cancel</button>
      <button id="del-confirm" style="min-width:100px;background:#c00;color:#fff;border:none;padding:10px 20px;border-radius:4px;cursor:pointer;font-weight:600;font-size:.88rem">Delete</button>
    </div>
  </div>
</div>

<script>
(function() {
  // ── Saved client data ───────────────────────────────────────────────────
  var clients = <?= json_encode(array_map(fn($n,$r)=>['name'=>$n,'codes'=>$r], array_keys($allClients), $allClients)) ?>;

  // ── Toast ───────────────────────────────────────────────────────────────
  function toast(msg, type) {
    var t = document.createElement('div');
    t.className = 'pw-toast-el';
    t.textContent = msg;
    t.style.background = type === 'error' ? '#c00' : '#111';
    document.body.appendChild(t);
    setTimeout(function(){ t.style.opacity = '1'; }, 10);
    setTimeout(function(){ t.style.opacity = '0'; setTimeout(function(){ t.remove(); }, 300); }, 3000);
  }

  // ── Modal helpers ───────────────────────────────────────────────────────
  function openModal(id)  { document.getElementById(id).classList.add('open'); }
  function closeModal(id) { document.getElementById(id).classList.remove('open'); }

  // ── Form modal ──────────────────────────────────────────────────────────
  function addRow(code, name) {
    var hex = (code && /^#[0-9a-fA-F]{3,6}$/.test(code)) ? code : '#000000';
    var d = document.createElement('div');
    d.className = 'cc-row';
    d.innerHTML = '<input type="color" value="' + hex + '">'
      + '<input type="text" name="color_code[]" class="form-control" value="' + (code||'') + '" placeholder="#FF0000 or PANTONE 485 C" style="flex:2">'
      + '<input type="text" name="color_name[]" class="form-control" value="' + (name||'') + '" placeholder="Color name (optional)" style="flex:2">'
      + '<button type="button" class="btn-rm-row" style="background:none;border:none;cursor:pointer;color:#c00;font-size:1.1rem;padding:0 4px"><i class="fas fa-times"></i></button>';
    // sync color picker ↔ text
    var picker = d.querySelector('input[type=color]');
    var text   = d.querySelectorAll('input[type=text]')[0];
    picker.addEventListener('input', function(){ text.value = this.value; });
    text.addEventListener('input', function(){
      if (/^#[0-9a-fA-F]{3,6}$/.test(this.value.trim())) picker.value = this.value.trim();
    });
    d.querySelector('.btn-rm-row').addEventListener('click', function(){ d.remove(); });
    document.getElementById('cc-rows').appendChild(d);
  }

  document.getElementById('btn-add-row').addEventListener('click', function(){ addRow(); });

  document.getElementById('btn-add-new').addEventListener('click', function() {
    document.getElementById('form-title').textContent = 'Add Color Codes';
    document.getElementById('f-client').value = '';
    document.getElementById('f-client').readOnly = false;
    document.getElementById('cc-rows').innerHTML = '';
    addRow();
    openModal('form-modal');
  });

  document.getElementById('btn-close-form').addEventListener('click', function(){ closeModal('form-modal'); });
  document.getElementById('form-modal').addEventListener('click', function(e){ if(e.target===this) closeModal('form-modal'); });

  // Edit buttons (delegated)
  document.getElementById('cc-list').addEventListener('click', function(e) {
    var editBtn = e.target.closest('.btn-edit-client');
    if (editBtn) {
      var name = editBtn.dataset.client;
      document.getElementById('form-title').textContent = 'Edit: ' + name;
      document.getElementById('f-client').value = name;
      document.getElementById('f-client').readOnly = true;
      document.getElementById('cc-rows').innerHTML = '';
      var c = clients.find(function(x){ return x.name === name; });
      if (c) c.codes.forEach(function(r){ addRow(r.color_code, r.color_name); });
      else addRow();
      openModal('form-modal');
    }
  });

  // ── Search ──────────────────────────────────────────────────────────────
  document.getElementById('search-box').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.cc-card').forEach(function(c){
      c.style.display = c.dataset.client.includes(q) ? '' : 'none';
    });
  });

  // ── Delete modal ────────────────────────────────────────────────────────
  var _delFn = null;
  function askDelete(title, msg, fn) {
    document.getElementById('del-title').textContent = title;
    document.getElementById('del-msg').textContent   = msg;
    _delFn = fn;
    openModal('del-modal');
  }
  document.getElementById('del-cancel').addEventListener('click', function(){ closeModal('del-modal'); _delFn = null; });
  document.getElementById('del-modal').addEventListener('click', function(e){ if(e.target===this){ closeModal('del-modal'); _delFn = null; } });
  document.getElementById('del-confirm').addEventListener('click', function(){
    closeModal('del-modal');
    if (_delFn) { _delFn(); _delFn = null; }
  });

  // ── AJAX delete ─────────────────────────────────────────────────────────
  var AJAX_URL = window.location.pathname.replace(/\/[^\/]+$/, '/') + 'ajax_color_delete.php';

  function doDelete(data, onOk) {
    var fd = new FormData();
    Object.keys(data).forEach(function(k){ fd.append(k, data[k]); });
    fetch(AJAX_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function(r) { return r.text(); })
      .then(function(txt) {
        var res;
        try { res = JSON.parse(txt); } catch(e) {
          console.error('Non-JSON:', txt.substring(0, 200));
          toast('Server error — see console.', 'error');
          return;
        }
        if (res.ok) { onOk(); }
        else { toast(res.msg || 'Delete failed.', 'error'); }
      })
      .catch(function(e){ console.error(e); toast('Network error.', 'error'); });
  }

  // ── Delete single badge (delegated) ─────────────────────────────────────
  document.getElementById('cc-list').addEventListener('click', function(e) {
    var btn = e.target.closest('.btn-del-one');
    if (!btn) return;
    var id    = btn.dataset.id;
    var label = btn.dataset.label;
    askDelete('Delete Confirmation', 'Delete "' + label + '" permanently?', function() {
      doDelete({ action: 'delete_one', id: id }, function() {
        var badge = document.getElementById('badge-' + id);
        if (badge) { badge.style.opacity = '0'; setTimeout(function(){ badge.remove(); }, 300); }
        toast('Color code deleted.');
      });
    });
  });

  // ── Delete entire client (delegated) ────────────────────────────────────
  document.getElementById('cc-list').addEventListener('click', function(e) {
    var btn = e.target.closest('.btn-del-client');
    if (!btn) return;
    var name = btn.dataset.client;
    askDelete('Delete Confirmation', 'Delete ALL color codes for "' + name + '" permanently?', function() {
      doDelete({ action: 'delete_client', client_name: name }, function() {
        var card = btn.closest('.cc-card');
        if (card) { card.style.opacity = '0'; setTimeout(function(){ card.remove(); }, 300); }
        toast('Deleted all codes for "' + name + '".');
      });
    });
  });

  // ── Show flash message ───────────────────────────────────────────────────
  <?php if ($msg): ?>
  (function(){
    var parts = <?= json_encode($msg) ?>.split(':');
    var type  = parts.shift();
    var text  = parts.join(':');
    toast(text, type === 'success' ? '' : 'error');
  })();
  <?php endif; ?>

})(); // end IIFE
</script>
</body>
</html>
