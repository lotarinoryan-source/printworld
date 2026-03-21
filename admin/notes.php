<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '_layout.php';
requireAdmin();

$db = db();

$db->query("CREATE TABLE IF NOT EXISTS client_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('Unpaid','Paid') NOT NULL DEFAULT 'Unpaid',
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$rows = [];
$res = $db->query("SELECT * FROM client_notes ORDER BY client_name, date_added DESC");
while ($r = $res->fetch_assoc()) {
    $rows[$r['client_name']][] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/pw.png">
  <title>Printworld - Notes</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .nt-search { position:relative; margin-bottom:18px; }
    .nt-search input { width:100%; padding:10px 16px 10px 40px; border:1px solid #ddd; border-radius:4px; font-size:.9rem; box-sizing:border-box; }
    .nt-search i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#aaa; }
    .nt-client-row { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; background:#fff; border:1px solid #eee; border-radius:6px; margin-bottom:0; cursor:pointer; transition:box-shadow .15s; gap:12px; }
    .nt-client-row:hover { box-shadow:0 2px 12px rgba(0,0,0,.08); }
    .nt-client-name { font-weight:700; font-size:.95rem; display:flex; align-items:center; gap:8px; flex:1; min-width:0; }
    .nt-unpaid-badge { background:#fff3cd; color:#856404; border-radius:4px; padding:3px 10px; font-size:.78rem; font-weight:700; white-space:nowrap; }
    .nt-unpaid-zero  { background:#d1e7dd; color:#0a3622; border-radius:4px; padding:3px 10px; font-size:.78rem; font-weight:700; white-space:nowrap; }
    .nt-group { border:1px solid #eee; border-radius:6px; margin-bottom:10px; overflow:hidden; }
    .nt-group .nt-client-row { border-radius:0; border:none; border-bottom:1px solid transparent; margin-bottom:0; }
    .nt-group .nt-client-row.open-row { border-bottom-color:#eee; border-radius:0; }
    .nt-detail-panel { display:none; background:#f9f9f9; padding:20px; }
    .nt-detail-panel.open { display:block; }
    .nt-total-row { display:flex; justify-content:flex-end; align-items:center; gap:16px; padding:12px 0 0; border-top:2px solid #111; margin-top:8px; }
    .nt-total-label { font-size:.8rem; letter-spacing:1px; text-transform:uppercase; font-weight:700; }
    .nt-total-amount { font-size:1.3rem; font-weight:800; color:#c00; }
    .nt-total-amount.zero { color:#0a3622; }
    .status-select { border:1px solid #ddd; border-radius:4px; padding:4px 8px; font-size:.8rem; cursor:pointer; }
    .pw-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:3000; align-items:center; justify-content:center; padding:20px; }
    .pw-overlay.open { display:flex; }
    .pw-box { background:#fff; padding:32px; width:100%; max-width:420px; border-radius:8px; text-align:center; }
    .pw-toast-el { position:fixed; bottom:24px; right:24px; z-index:9999; padding:12px 22px; border-radius:4px; font-size:.85rem; color:#fff; opacity:0; transition:opacity .25s; }
    .btn-add-inside { background:#111; color:#fff; border:none; padding:9px 18px; border-radius:4px; font-size:.82rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; margin-bottom:14px; }
    .btn-add-inside:hover { background:#333; }
  </style>
</head>
<body class="admin-body">
<?php adminSidebar('notes'); ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1><i class="fas fa-sticky-note" style="margin-right:8px"></i>Notes — Unpaid Tracking</h1>
    <button class="btn btn-dark" id="btn-add-item"><i class="fas fa-plus"></i> New Client Item</button>
  </div>

  <div class="nt-search">
    <i class="fas fa-search"></i>
    <input type="text" id="nt-search" placeholder="Search client name...">
  </div>

  <div id="nt-list">
    <?php if (empty($rows)): ?>
    <div id="nt-empty" class="admin-card" style="text-align:center;padding:48px;color:#aaa">
      <i class="fas fa-sticky-note" style="font-size:2rem;display:block;margin-bottom:12px"></i>
      No records yet. Click "New Client Item" to start tracking.
    </div>
    <?php else: ?>
      <?php foreach ($rows as $clientName => $items): ?>
      <?php
        $totalUnpaid = array_sum(array_map(
            fn($i) => $i['status'] === 'Unpaid' ? (float)$i['price'] : 0,
            $items
        ));
      ?>
      <div class="nt-group" data-client="<?= strtolower(htmlspecialchars($clientName)) ?>">
        <div class="nt-client-row" onclick="toggleDetail(this)">
          <div class="nt-client-name">
            <i class="fas fa-chevron-right nt-chevron" style="font-size:.7rem;color:#aaa;transition:transform .2s;flex-shrink:0"></i>
            <span class="nt-cname"><?= htmlspecialchars($clientName) ?></span>
            <span class="nt-count" style="font-size:.78rem;color:#aaa;font-weight:400"><?= count($items) ?> item(s)</span>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;flex-wrap:wrap;justify-content:flex-end">
            <span class="nt-badge <?= $totalUnpaid > 0 ? 'nt-unpaid-badge' : 'nt-unpaid-zero' ?>">
              <?= $totalUnpaid > 0 ? '₱' . number_format($totalUnpaid, 2) . ' unpaid' : 'All Paid' ?>
            </span>
            <button class="action-btn danger btn-del-client" data-client="<?= htmlspecialchars($clientName) ?>"
              title="Delete all items for <?= htmlspecialchars($clientName) ?>">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
        <div class="nt-detail-panel">
          <button class="btn-add-inside btn-add-for-client" data-client="<?= htmlspecialchars($clientName) ?>">
            <i class="fas fa-plus"></i> Add Item for <?= htmlspecialchars($clientName) ?>
          </button>
          <div style="overflow-x:auto">
            <table class="data-table nt-table" style="min-width:560px">
              <thead>
                <tr><th>Date Added</th><th>Description</th><th>Price</th><th>Status</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                <tr id="nt-row-<?= $item['id'] ?>">
                  <td style="white-space:nowrap"><?= date('Y-m-d', strtotime($item['date_added'])) ?></td>
                  <td><?= htmlspecialchars($item['description']) ?></td>
                  <td class="nt-price" style="white-space:nowrap">₱<?= number_format((float)$item['price'], 2) ?></td>
                  <td>
                    <select class="status-select" data-id="<?= $item['id'] ?>">
                      <option value="Unpaid" <?= $item['status']==='Unpaid'?'selected':'' ?>>Unpaid</option>
                      <option value="Paid"   <?= $item['status']==='Paid'  ?'selected':'' ?>>Paid</option>
                    </select>
                  </td>
                  <td style="white-space:nowrap">
                    <button class="action-btn btn-edit-item"
                      data-id="<?= $item['id'] ?>"
                      data-client="<?= htmlspecialchars($clientName) ?>"
                      data-desc="<?= htmlspecialchars($item['description']) ?>"
                      data-price="<?= $item['price'] ?>"
                      data-status="<?= $item['status'] ?>">
                      <i class="fas fa-pen"></i> Edit
                    </button>
                    <button class="action-btn danger btn-del-item"
                      data-id="<?= $item['id'] ?>"
                      data-desc="<?= htmlspecialchars($item['description']) ?>">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="nt-total-row">
            <span class="nt-total-label">Total Unpaid</span>
            <span class="nt-total-amount <?= $totalUnpaid == 0 ? 'zero' : '' ?>">
              ₱<?= number_format($totalUnpaid, 2) ?>
            </span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<!-- Add / Edit Modal -->
<div id="item-modal" class="pw-overlay">
  <div style="background:#fff;padding:32px;width:100%;max-width:480px;border-radius:6px">
    <h3 id="item-modal-title" style="margin-bottom:20px">Add Item</h3>
    <div class="form-group">
      <label>Client Name *</label>
      <input type="text" id="f-client" class="form-control" placeholder="e.g. Mr DIY, Sailun">
    </div>
    <div class="form-group">
      <label>Description *</label>
      <input type="text" id="f-desc" class="form-control" placeholder="e.g. Tarpaulin 4x3ft">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Price (₱) *</label>
        <input type="number" id="f-price" class="form-control" min="0" step="0.01" placeholder="0.00">
      </div>
      <div class="form-group">
        <label>Status</label>
        <select id="f-status" class="form-control">
          <option value="Unpaid">Unpaid</option>
          <option value="Paid">Paid</option>
        </select>
      </div>
    </div>
    <input type="hidden" id="f-id" value="">
    <div style="display:flex;gap:12px;margin-top:8px">
      <button id="btn-save-item" class="btn btn-dark"><i class="fas fa-save"></i> Save</button>
      <button id="btn-close-item" class="action-btn">Cancel</button>
    </div>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div id="del-modal" class="pw-overlay">
  <div class="pw-box">
    <div style="width:54px;height:54px;background:#fff0f0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
      <i class="fas fa-trash" style="color:#c00;font-size:1.3rem"></i>
    </div>
    <h3 style="margin-bottom:8px;font-size:1.05rem">Delete Confirmation</h3>
    <p id="del-msg" style="color:#666;font-size:.88rem;margin-bottom:24px">Are you sure you want to delete permanently?</p>
    <div style="display:flex;gap:12px;justify-content:center">
      <button id="del-cancel" class="action-btn" style="min-width:100px">Cancel</button>
      <button id="del-confirm" style="min-width:100px;background:#c00;color:#fff;border:none;padding:10px 20px;border-radius:4px;cursor:pointer;font-weight:600;font-size:.88rem">Delete</button>
    </div>
  </div>
</div>

<script>
(function () {
  var AJAX = 'ajax_notes.php';

  // ── Toast ──────────────────────────────────────────────────────────────
  function toast(msg, type) {
    var t = document.createElement('div');
    t.className = 'pw-toast-el';
    t.textContent = msg;
    t.style.background = type === 'error' ? '#c00' : '#111';
    document.body.appendChild(t);
    setTimeout(function () { t.style.opacity = '1'; }, 10);
    setTimeout(function () { t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 300); }, 3000);
  }

  function openModal(id)  { document.getElementById(id).classList.add('open'); }
  function closeModal(id) { document.getElementById(id).classList.remove('open'); }

  // ── Toggle detail panel ────────────────────────────────────────────────
  window.toggleDetail = function (row) {
    var panel  = row.nextElementSibling;
    var icon   = row.querySelector('.nt-chevron');
    var isOpen = panel.classList.contains('open');
    document.querySelectorAll('.nt-detail-panel.open').forEach(function (p) {
      p.classList.remove('open');
      p.previousElementSibling.classList.remove('open-row');
      var i = p.previousElementSibling.querySelector('.nt-chevron');
      if (i) i.style.transform = '';
    });
    if (!isOpen) {
      panel.classList.add('open');
      row.classList.add('open-row');
      if (icon) icon.style.transform = 'rotate(90deg)';
    }
  };

  // ── Search ─────────────────────────────────────────────────────────────
  document.getElementById('nt-search').addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.nt-group').forEach(function (g) {
      g.style.display = g.dataset.client.includes(q) ? '' : 'none';
    });
  });

  // ── Open Add modal ─────────────────────────────────────────────────────
  function openAddModal(clientName, lockClient) {
    document.getElementById('item-modal-title').textContent = clientName ? 'Add Item — ' + clientName : 'Add Item';
    document.getElementById('f-id').value        = '';
    document.getElementById('f-client').value    = clientName || '';
    document.getElementById('f-client').readOnly = !!lockClient;
    document.getElementById('f-desc').value      = '';
    document.getElementById('f-price').value     = '';
    document.getElementById('f-status').value    = 'Unpaid';
    openModal('item-modal');
    setTimeout(function () {
      (lockClient ? document.getElementById('f-desc') : document.getElementById('f-client')).focus();
    }, 50);
  }

  // ── Delete modal shared state ──────────────────────────────────────────
  var _delFn = null;

  function askDelete(msg, fn) {
    document.getElementById('del-msg').textContent = msg;
    _delFn = fn;
    openModal('del-modal');
  }

  document.getElementById('del-cancel').addEventListener('click', function () {
    closeModal('del-modal'); _delFn = null;
  });
  document.getElementById('del-modal').addEventListener('click', function (e) {
    if (e.target === this) { closeModal('del-modal'); _delFn = null; }
  });
  document.getElementById('del-confirm').addEventListener('click', function () {
    closeModal('del-modal');
    if (_delFn) { _delFn(); _delFn = null; }
  });

  // ── Single document-level click handler ───────────────────────────────
  document.addEventListener('click', function (e) {

    // + Add for client (header button or inside-panel button)
    var addBtn = e.target.closest('.btn-add-for-client');
    if (addBtn) {
      e.stopPropagation();
      openAddModal(addBtn.dataset.client, true);
      return;
    }

    // Delete entire client
    var delClientBtn = e.target.closest('.btn-del-client');
    if (delClientBtn) {
      e.stopPropagation();
      var clientName = delClientBtn.dataset.client;
      askDelete('Are you sure you want to delete ALL items for "' + clientName + '"? This cannot be undone.', function () {
        var fd = new FormData();
        fd.append('action', 'delete_client');
        fd.append('client_name', clientName);
        fetch(AJAX, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res.ok) {
              var group = delClientBtn.closest('.nt-group');
              if (group) {
                group.style.transition = 'opacity .3s';
                group.style.opacity = '0';
                setTimeout(function () { group.remove(); }, 320);
              }
              toast('Deleted all items for "' + clientName + '".');
            } else {
              toast(res.msg || 'Delete failed.', 'error');
            }
          })
          .catch(function () { toast('Network error.', 'error'); });
      });
      return;
    }

    // Edit item
    var editBtn = e.target.closest('.btn-edit-item');
    if (editBtn) {
      document.getElementById('item-modal-title').textContent = 'Edit Item';
      document.getElementById('f-id').value        = editBtn.dataset.id;
      document.getElementById('f-client').value    = editBtn.dataset.client;
      document.getElementById('f-client').readOnly = true;
      document.getElementById('f-desc').value      = editBtn.dataset.desc;
      document.getElementById('f-price').value     = editBtn.dataset.price;
      document.getElementById('f-status').value    = editBtn.dataset.status;
      openModal('item-modal');
      setTimeout(function () { document.getElementById('f-desc').focus(); }, 50);
      return;
    }

    // Delete single item
    var delItemBtn = e.target.closest('.btn-del-item');
    if (delItemBtn) {
      var id   = delItemBtn.dataset.id;
      var desc = delItemBtn.dataset.desc;
      askDelete('Are you sure you want to delete "' + desc + '" permanently?', function () {
        var fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetch(AJAX, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res.ok) {
              var row = document.getElementById('nt-row-' + id);
              if (row) {
                recalcGroup(row);
                row.style.transition = 'opacity .3s';
                row.style.opacity = '0';
                setTimeout(function () {
                  var group = row.closest('.nt-group');
                  row.remove();
                  if (group) {
                    var n = group.querySelectorAll('tbody tr').length;
                    var countEl = group.querySelector('.nt-count');
                    if (countEl) countEl.textContent = n + ' item(s)';
                  }
                }, 320);
              }
              toast('Item deleted.');
            } else {
              toast(res.msg || 'Delete failed.', 'error');
            }
          })
          .catch(function () { toast('Network error.', 'error'); });
      });
      return;
    }

  });

  // ── Top-bar "New Client Item" ──────────────────────────────────────────
  document.getElementById('btn-add-item').addEventListener('click', function () {
    openAddModal('', false);
  });

  document.getElementById('btn-close-item').addEventListener('click', function () { closeModal('item-modal'); });
  document.getElementById('item-modal').addEventListener('click', function (e) { if (e.target === this) closeModal('item-modal'); });

  // ── Save item ──────────────────────────────────────────────────────────
  document.getElementById('btn-save-item').addEventListener('click', function () {
    var id     = document.getElementById('f-id').value.trim();
    var client = document.getElementById('f-client').value.trim();
    var desc   = document.getElementById('f-desc').value.trim();
    var price  = document.getElementById('f-price').value.trim();
    var status = document.getElementById('f-status').value;

    if (!client || !desc || price === '') { toast('Please fill in all required fields.', 'error'); return; }

    var fd = new FormData();
    fd.append('action', id ? 'edit' : 'add');
    if (id) fd.append('id', id);
    fd.append('client_name', client);
    fd.append('description', desc);
    fd.append('price', price);
    fd.append('status', status);

    fetch(AJAX, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.ok) { toast(res.msg || 'Error saving.', 'error'); return; }
        closeModal('item-modal');
        toast(res.msg || 'Saved.');
        if (id) {
          var row = document.getElementById('nt-row-' + id);
          if (row) {
            row.querySelector('td:nth-child(2)').textContent = desc;
            row.querySelector('.nt-price').textContent = '₱' + parseFloat(price).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            var sel = row.querySelector('.status-select');
            if (sel) sel.value = status;
            var eb = row.querySelector('.btn-edit-item');
            if (eb) { eb.dataset.desc = desc; eb.dataset.price = price; eb.dataset.status = status; }
            recalcGroup(row);
          }
        } else {
          var group = findGroup(client);
          if (group) {
            injectRow(group, res.id, client, desc, price, status, res.date);
            recalcGroup(group.querySelector('tbody tr'));
            var panel = group.querySelector('.nt-detail-panel');
            var hrow  = group.querySelector('.nt-client-row');
            if (!panel.classList.contains('open')) toggleDetail(hrow);
          } else {
            location.reload();
          }
        }
      })
      .catch(function () { toast('Network error.', 'error'); });
  });

  function findGroup(clientName) {
    var key = clientName.toLowerCase();
    var found = null;
    document.querySelectorAll('.nt-group').forEach(function (g) {
      if (g.dataset.client === key) found = g;
    });
    return found;
  }

  function injectRow(group, id, client, desc, price, status, dateStr) {
    var tbody = group.querySelector('tbody');
    var today = dateStr || new Date().toISOString().slice(0, 10);
    var pf    = '₱' + parseFloat(price).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    var tr    = document.createElement('tr');
    tr.id = 'nt-row-' + id;
    tr.innerHTML =
      '<td style="white-space:nowrap">' + today + '</td>' +
      '<td>' + escHtml(desc) + '</td>' +
      '<td class="nt-price" style="white-space:nowrap">' + pf + '</td>' +
      '<td><select class="status-select" data-id="' + id + '">' +
        '<option value="Unpaid"' + (status === 'Unpaid' ? ' selected' : '') + '>Unpaid</option>' +
        '<option value="Paid"'   + (status === 'Paid'   ? ' selected' : '') + '>Paid</option>' +
      '</select></td>' +
      '<td style="white-space:nowrap">' +
        '<button class="action-btn btn-edit-item" data-id="' + id + '" data-client="' + escHtml(client) + '" data-desc="' + escHtml(desc) + '" data-price="' + price + '" data-status="' + status + '">' +
          '<i class="fas fa-pen"></i> Edit</button> ' +
        '<button class="action-btn danger btn-del-item" data-id="' + id + '" data-desc="' + escHtml(desc) + '">' +
          '<i class="fas fa-trash"></i></button>' +
      '</td>';
    tbody.insertBefore(tr, tbody.firstChild);
    var countEl = group.querySelector('.nt-count');
    if (countEl) countEl.textContent = tbody.querySelectorAll('tr').length + ' item(s)';
    var empty = document.getElementById('nt-empty');
    if (empty) empty.remove();
  }

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Status toggle ──────────────────────────────────────────────────────
  document.getElementById('nt-list').addEventListener('change', function (e) {
    var sel = e.target.closest('.status-select');
    if (!sel) return;
    var fd = new FormData();
    fd.append('action', 'status');
    fd.append('id', sel.dataset.id);
    fd.append('status', sel.value);
    fetch(AJAX, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.ok) { recalcGroup(sel); toast('Status updated.'); }
        else { toast(res.msg || 'Update failed.', 'error'); }
      })
      .catch(function () { toast('Network error.', 'error'); });
  });

  // ── Recalc total unpaid ────────────────────────────────────────────────
  function recalcGroup(el) {
    var panel = el.closest('.nt-detail-panel');
    if (!panel) return;
    var total = 0;
    panel.querySelectorAll('tbody tr').forEach(function (tr) {
      var sel   = tr.querySelector('.status-select');
      var price = parseFloat(tr.querySelector('.nt-price').textContent.replace(/[₱,]/g, '')) || 0;
      if (sel && sel.value === 'Unpaid') total += price;
    });
    var amtEl = panel.querySelector('.nt-total-amount');
    if (amtEl) {
      amtEl.textContent = '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
      amtEl.classList.toggle('zero', total === 0);
    }
    var hrow  = panel.previousElementSibling;
    var badge = hrow ? hrow.querySelector('.nt-badge') : null;
    if (badge) {
      if (total > 0) {
        badge.className = 'nt-badge nt-unpaid-badge';
        badge.textContent = '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 }) + ' unpaid';
      } else {
        badge.className = 'nt-badge nt-unpaid-zero';
        badge.textContent = 'All Paid';
      }
    }
  }

})();
</script>
</body>
</html>
