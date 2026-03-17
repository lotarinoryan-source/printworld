<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '_layout.php';
requireAdmin();

$db = db();
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', sanitizeInput($_POST['slug'] ?? '')));
        $desc = sanitizeInput($_POST['description'] ?? '');
        $icon = sanitizeInput($_POST['icon'] ?? 'fa-print');
        $cat  = sanitizeInput($_POST['category'] ?? 'basic');
        if ($name && $slug) {
            $stmt = $db->prepare("INSERT INTO service_categories (name, slug, description, icon, category) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssss', $name, $slug, $desc, $icon, $cat);
            $stmt->execute() ? $success = 'Service added.' : $error = 'Slug already exists.';
        }
    } elseif ($action === 'edit') {
        $id     = (int)$_POST['id'];
        $name   = sanitizeInput($_POST['name'] ?? '');
        $desc   = sanitizeInput($_POST['description'] ?? '');
        $icon   = sanitizeInput($_POST['icon'] ?? 'fa-print');
        $cat    = sanitizeInput($_POST['category'] ?? 'basic');
        $active = (int)($_POST['is_active'] ?? 1);

        // Handle image upload
        $imgSql = '';
        $imgPath = null;
        if (!empty($_FILES['service_image']['name'])) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $file = $_FILES['service_image'];
            if (in_array($file['type'], $allowed) && $file['size'] <= MAX_FILE_SIZE) {
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $dir  = UPLOAD_DIR . 'services/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $dest = $dir . 'svc_' . $id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $imgPath = 'uploads/services/' . basename($dest);
                    $imgSql  = ', image_path=?';
                }
            } else {
                $error = 'Invalid image file.';
            }
        }

        if (!$error) {
            if ($imgPath) {
                $stmt = $db->prepare("UPDATE service_categories SET name=?,description=?,icon=?,category=?,is_active=?,image_path=? WHERE id=?");
                $stmt->bind_param('ssssisi', $name, $desc, $icon, $cat, $active, $imgPath, $id);
            } else {
                $stmt = $db->prepare("UPDATE service_categories SET name=?,description=?,icon=?,category=?,is_active=? WHERE id=?");
                $stmt->bind_param('ssssii', $name, $desc, $icon, $cat, $active, $id);
            }
            $stmt->execute() ? $success = 'Service updated.' : $error = 'Update failed.';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM service_categories WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute() ? $success = 'Service deleted.' : $error = 'Delete failed.';
    } elseif ($action === 'remove_image') {
        $id = (int)$_POST['id'];
        $s = $db->prepare("UPDATE service_categories SET image_path=NULL WHERE id=?");
        $s->bind_param('i', $id);
        $s->execute();
        $success = 'Image removed.';
    }
}

$services = $db->query("SELECT * FROM service_categories ORDER BY category, sort_order, id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/pw.png">
  <title>Printworld</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php adminSidebar('services'); ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1>Services</h1>
    <button class="btn btn-dark" onclick="document.getElementById('add-modal').style.display='flex'">
      <i class="fas fa-plus"></i> Add Service
    </button>
  </div>

  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

  <div class="admin-card">
    <table class="data-table">
      <thead>
        <tr><th>Category</th><th>Image</th><th>Name</th><th>Slug</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php while ($svc = $services->fetch_assoc()): ?>
        <tr>
          <td><span style="font-size:0.75rem;background:#f0f0f0;padding:2px 8px;border-radius:3px;text-transform:capitalize"><?= htmlspecialchars($svc['category'] ?? 'basic') ?></span></td>
          <td>
            <?php if ($svc['image_path']): ?>
              <img src="../<?= htmlspecialchars($svc['image_path']) ?>" style="width:48px;height:36px;object-fit:cover;border-radius:3px;border:1px solid #eee">
            <?php else: ?>
              <span style="color:#ccc;font-size:0.8rem">—</span>
            <?php endif; ?>
          </td>
          <td><strong><?= htmlspecialchars($svc['name']) ?></strong></td>
          <td><code><?= htmlspecialchars($svc['slug']) ?></code></td>
          <td><span class="badge <?= $svc['is_active'] ? 'badge-responded' : 'badge-pending' ?>"><?= $svc['is_active'] ? 'Active' : 'Inactive' ?></span></td>
          <td style="display:flex;gap:6px;flex-wrap:wrap">
            <button class="action-btn" onclick="editService(<?= htmlspecialchars(json_encode($svc)) ?>)">
              <i class="fas fa-pen"></i> Edit
            </button>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this service?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $svc['id'] ?>">
              <button type="submit" class="action-btn danger"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- Add Modal -->
<div id="add-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;overflow-y:auto;padding:20px">
  <div style="background:#fff;padding:32px;width:100%;max-width:480px;border-radius:6px">
    <h3 style="margin-bottom:20px">Add Service</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label>Category</label>
        <select name="category" class="form-control">
          <option value="basic">Basic Services</option>
          <option value="sublimation">Sublimation Services</option>
          <option value="signage">Signage Services</option>
        </select>
      </div>
      <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label>Slug (URL key) *</label><input type="text" name="slug" class="form-control" required placeholder="e.g. mug-printing"></div>
      <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
      <div class="form-group"><label>Icon (FontAwesome)</label><input type="text" name="icon" class="form-control" value="fa-print"></div>
      <div style="display:flex;gap:12px;margin-top:8px">
        <button type="submit" class="btn btn-dark">Add Service</button>
        <button type="button" class="action-btn" onclick="document.getElementById('add-modal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;overflow-y:auto;padding:20px">
  <div style="background:#fff;padding:32px;width:100%;max-width:480px;border-radius:6px">
    <h3 style="margin-bottom:20px">Edit Service</h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit-id">
      <div class="form-group"><label>Category</label>
        <select name="category" id="edit-category" class="form-control">
          <option value="basic">Basic Services</option>
          <option value="sublimation">Sublimation Services</option>
          <option value="signage">Signage Services</option>
        </select>
      </div>
      <div class="form-group"><label>Name *</label><input type="text" name="name" id="edit-name" class="form-control" required></div>
      <div class="form-group"><label>Description</label><textarea name="description" id="edit-desc" class="form-control" rows="2"></textarea></div>
      <div class="form-group"><label>Icon (FontAwesome)</label><input type="text" name="icon" id="edit-icon" class="form-control"></div>
      <div class="form-group"><label>Status</label>
        <select name="is_active" id="edit-active" class="form-control">
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>
      <div class="form-group">
        <label>Service Image</label>
        <div id="edit-current-img" style="margin-bottom:8px"></div>
        <input type="file" name="service_image" class="form-control" accept="image/*">
        <p style="font-size:0.75rem;color:#888;margin-top:4px">Upload a photo for this service (JPG, PNG, WebP). Replaces existing.</p>
      </div>
      <div style="display:flex;gap:12px;margin-top:8px">
        <button type="submit" class="btn btn-dark"><i class="fas fa-save"></i> Save Changes</button>
        <button type="button" class="action-btn" onclick="document.getElementById('edit-modal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function editService(svc) {
  document.getElementById('edit-id').value       = svc.id;
  document.getElementById('edit-category').value = svc.category || 'basic';
  document.getElementById('edit-name').value     = svc.name;
  document.getElementById('edit-desc').value     = svc.description || '';
  document.getElementById('edit-icon').value     = svc.icon || 'fa-print';
  document.getElementById('edit-active').value   = svc.is_active;
  var imgDiv = document.getElementById('edit-current-img');
  if (svc.image_path) {
    imgDiv.innerHTML = '<img src="../' + svc.image_path + '" style="height:60px;border-radius:4px;border:1px solid #eee;margin-bottom:4px"><br>'
      + '<small style="color:#888">Current image — upload new to replace</small>';
  } else {
    imgDiv.innerHTML = '<small style="color:#aaa">No image yet</small>';
  }
  document.getElementById('edit-modal').style.display = 'flex';
}
document.getElementById('add-modal').addEventListener('click', function(e){ if(e.target===this) this.style.display='none'; });
document.getElementById('edit-modal').addEventListener('click', function(e){ if(e.target===this) this.style.display='none'; });
</script>
</body>
</html>
