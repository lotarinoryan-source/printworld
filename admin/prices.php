<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '_layout.php';
requireAdmin();

$db = db();
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';

    if ($action === 'add') {
        $itemName = sanitizeInput($_POST['item_name'] ?? '');
        $price    = (float)($_POST['price'] ?? 0);
        $unit     = sanitizeInput($_POST['unit'] ?? 'per piece');
        $itemKey  = strtolower(preg_replace('/[^a-z0-9]+/', '_', $itemName));
        if ($itemName && $itemKey) {
            $stmt = $db->prepare("INSERT INTO prices (item_key, item_name, price, unit) VALUES (?,?,?,?)");
            $stmt->bind_param('ssds', $itemKey, $itemName, $price, $unit);
            $stmt->execute() ? $success = 'Price item added.' : $error = 'Item already exists or invalid.';
        } else {
            $error = 'Item name is required.';
        }
    } elseif ($action === 'delete') {
        $key = sanitizeInput($_POST['item_key'] ?? '');
        if ($key) {
            $stmt = $db->prepare("DELETE FROM prices WHERE item_key=?");
            $stmt->bind_param('s', $key);
            $stmt->execute() ? $success = 'Item deleted.' : $error = 'Delete failed.';
        }
    } else {
        // Bulk update existing prices
        $stmt = $db->prepare("UPDATE prices SET price=? WHERE item_key=?");
        foreach ($_POST['prices'] as $key => $value) {
            $price = (float)$value;
            $key   = sanitizeInput($key);
            $stmt->bind_param('ds', $price, $key);
            $stmt->execute();
        }
        $success = 'Prices updated.';
    }
}

$prices = getAllPrices();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/pw.png">
  <title>Printworld - Prices</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php adminSidebar('prices'); ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1>Price Management</h1>
    <button class="btn btn-dark" onclick="document.getElementById('add-modal').style.display='flex'">
      <i class="fas fa-plus"></i> Add Item
    </button>
  </div>

  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

  <div class="admin-card">
    <div class="admin-card-header"><h3>Service Prices</h3></div>
    <div style="padding:0 0 16px">
      <div style="position:relative;max-width:340px">
        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#aaa;font-size:.85rem"></i>
        <input type="text" id="price-search" placeholder="Search item name..."
          style="width:100%;padding:9px 14px 9px 36px;border:1px solid #ddd;border-radius:4px;font-size:.88rem;box-sizing:border-box"
          oninput="filterPrices(this.value)">
      </div>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <table class="data-table">
        <thead>
          <tr><th>Item Name</th><th>Unit</th><th>Price (₱)</th><th></th></tr>
        </thead>
        <tbody id="prices-tbody">
          <?php foreach ($prices as $key => $p): ?>
          <tr data-name="<?= strtolower(htmlspecialchars($p['item_name'])) ?>">
            <td><strong><?= htmlspecialchars($p['item_name']) ?></strong></td>
            <td style="color:var(--gray-400);font-size:0.85rem"><?= htmlspecialchars($p['unit']) ?></td>
            <td style="width:200px">
              <div class="price-input-group">
                <span class="currency">₱</span>
                <input type="number" name="prices[<?= htmlspecialchars($key) ?>]"
                       class="form-control" value="<?= number_format($p['price'], 2, '.', '') ?>"
                       step="0.01" min="0" required>
              </div>
            </td>
            <td style="width:60px">
              <form method="POST" onsubmit="return confirm('Delete this item?')" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="item_key" value="<?= htmlspecialchars($key) ?>">
                <button type="submit" class="action-btn danger"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div style="margin-top:24px">
        <button type="submit" class="btn btn-dark"><i class="fas fa-save"></i> Save Prices</button>
      </div>
    </form>
  </div>
</main>

<!-- Add Item Modal -->
<div id="add-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;padding:32px;width:100%;max-width:420px;border-radius:6px">
    <h3 style="margin-bottom:20px">Add Price Item</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label>Item Name *</label>
        <input type="text" name="item_name" class="form-control" placeholder="e.g. Sticker Printing" required>
      </div>
      <div class="form-group">
        <label>Price (₱) *</label>
        <div class="price-input-group">
          <span class="currency">₱</span>
          <input type="number" name="price" class="form-control" value="0" step="0.01" min="0" required>
        </div>
      </div>
      <div class="form-group">
        <label>Unit</label>
        <input type="text" name="unit" class="form-control" value="per piece" placeholder="e.g. per piece, per sq ft">
      </div>
      <div style="display:flex;gap:12px;margin-top:8px">
        <button type="submit" class="btn btn-dark"><i class="fas fa-plus"></i> Add Item</button>
        <button type="button" class="action-btn" onclick="document.getElementById('add-modal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('add-modal').addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});

function filterPrices(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#prices-tbody tr').forEach(function(tr) {
    tr.style.display = tr.dataset.name.includes(q) ? '' : 'none';
  });
}
</script>
</body>
</html>
