<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$db = db();

// JSON endpoint — must be before any HTML output
if (isset($_GET['load_samples'])) {
    ini_set('display_errors', 0);
    ob_clean();
    $db->query("CREATE TABLE IF NOT EXISTS service_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $sid  = (int)$_GET['load_samples'];
    $rows = $db->query("SELECT id, image_path FROM service_images WHERE service_id=$sid ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit;
}

require_once '_layout.php';

$db = db();
$success = $error = '';

// Load all signage type/light options for JS
$signageTypesAll  = $db->query("SELECT * FROM signage_type_options ORDER BY service_slug, sort_order")->fetch_all(MYSQLI_ASSOC);
$signageLightsAll = $db->query("SELECT * FROM signage_light_options ORDER BY service_slug, id")->fetch_all(MYSQLI_ASSOC);

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
            if ($stmt->execute()) {
                $success = 'Service added.';
                // Save signage options if signage category
                if ($cat === 'signage') {
                    _saveSignageOptions($db, $slug, $_POST);
                }
            } else {
                $error = 'Slug already exists.';
            }
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
            if ($stmt->execute()) {
                $success = 'Service updated.';
                // Get slug for this service
                $sr = $db->prepare("SELECT slug FROM service_categories WHERE id=?");
                $sr->bind_param('i', $id); $sr->execute();
                $slugRow = $sr->get_result()->fetch_assoc();
                if ($slugRow && $cat === 'signage') {
                    _saveSignageOptions($db, $slugRow['slug'], $_POST);
                }
            } else {
                $error = 'Update failed.';
            }
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
    } elseif ($action === 'upload_samples') {
        $db->query("CREATE TABLE IF NOT EXISTS service_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_id INT NOT NULL,
            image_path VARCHAR(500) NOT NULL,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $id      = (int)$_POST['id'];
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $dir     = UPLOAD_DIR . 'services/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // Load existing paths to prevent duplicates
        $existing = [];
        $exRes = $db->query("SELECT image_path FROM service_images WHERE service_id=$id");
        while ($er = $exRes->fetch_assoc()) $existing[] = $er['image_path'];

        $uploaded = 0;
        $files = $_FILES['sample_images'] ?? [];
        if (!empty($files['name'][0])) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== 0) continue;
                if (!in_array($files['type'][$i], $allowed)) continue;
                // Hash check to prevent exact duplicates
                $hash = md5_file($files['tmp_name'][$i]);
                $ext  = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                $dest = $dir . 'svc_' . $id . '_' . $hash . '.' . $ext;
                $path = 'uploads/services/' . basename($dest);
                if (in_array($path, $existing)) continue; // skip duplicate
                if (!file_exists($dest)) {
                    move_uploaded_file($files['tmp_name'][$i], $dest);
                }
                $ins = $db->prepare("INSERT IGNORE INTO service_images (service_id, image_path) VALUES (?,?)");
                $ins->bind_param('is', $id, $path);
                $ins->execute();
                $uploaded++;
            }
        }
        $success = $uploaded . ' sample image(s) uploaded.';
    } elseif ($action === 'delete_sample') {
        $imgId = (int)$_POST['img_id'];
        $row   = $db->query("SELECT image_path FROM service_images WHERE id=$imgId")->fetch_assoc();
        if ($row) {
            // Path stored as 'uploads/services/file.jpg' — resolve from project root
            $full = rtrim(UPLOAD_DIR, '/uploads/') . '/' . $row['image_path'];
            if (file_exists($full)) @unlink($full);
            $db->query("DELETE FROM service_images WHERE id=$imgId");
        }
        $success = 'Sample image deleted.';
    }
}

$services = $db->query("SELECT * FROM service_categories ORDER BY category, sort_order, id");

// Reload signage options after any POST changes
$signageTypesAll  = $db->query("SELECT * FROM signage_type_options ORDER BY service_slug, sort_order")->fetch_all(MYSQLI_ASSOC);
$signageLightsAll = $db->query("SELECT * FROM signage_light_options ORDER BY service_slug, id")->fetch_all(MYSQLI_ASSOC);

function _saveSignageOptions($db, $slug, $post) {
    // Replace type options
    $st = $db->prepare("DELETE FROM signage_type_options WHERE service_slug=?");
    $st->bind_param('s', $slug); $st->execute();
    $types = array_filter(array_map('trim', explode("\n", $post['signage_types'] ?? '')));
    $ins = $db->prepare("INSERT IGNORE INTO signage_type_options (service_slug,type_label,sort_order) VALUES (?,?,?)");
    foreach (array_values($types) as $i => $t) {
        $ins->bind_param('ssi', $slug, $t, $i); $ins->execute();
    }
    // Replace light options
    $sl = $db->prepare("DELETE FROM signage_light_options WHERE service_slug=?");
    $sl->bind_param('s', $slug); $sl->execute();
    $lights = $_POST['signage_lights'] ?? [];
    $insl = $db->prepare("INSERT IGNORE INTO signage_light_options (service_slug,light_label) VALUES (?,?)");
    foreach ($lights as $l) {
        $l = trim($l);
        if ($l) { $insl->bind_param('ss', $slug, $l); $insl->execute(); }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/pw.png">
  <title>Printworld - Services</title>
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
  <div style="background:#fff;padding:32px;width:100%;max-width:520px;border-radius:6px">
    <h3 style="margin-bottom:20px">Add Service</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label>Category</label>
        <select name="category" id="add-category" class="form-control" onchange="toggleSignageConfig('add',this.value)">
          <option value="basic">Basic Services</option>
          <option value="sublimation">Sublimation Services</option>
          <option value="signage">Signage Services</option>
        </select>
      </div>
      <div class="form-group"><label>Name *</label><input type="text" name="name" id="add-name" class="form-control" required oninput="autoSuggestIcon('add',this.value);autoSlug(this.value)"></div>
      <div class="form-group"><label>Slug (URL key) *</label><input type="text" name="slug" id="add-slug" class="form-control" required placeholder="e.g. mug-printing"></div>
      <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
      <div class="form-group">
        <label>Icon (FontAwesome)</label>
        <div style="display:flex;align-items:center;gap:10px">
          <input type="text" name="icon" id="add-icon" class="form-control" value="fa-sign-hanging" oninput="updateIconPreview('add',this.value)" style="flex:1">
          <span id="add-icon-preview" style="font-size:1.5rem;width:32px;text-align:center;color:#333"><i class="fas fa-sign-hanging"></i></span>
        </div>
        <p style="font-size:0.72rem;color:#aaa;margin-top:3px">Auto-suggested from name. You can change it manually.</p>
      </div>

      <!-- Signage Config (shown only when category=signage) -->
      <div id="add-signage-config" style="display:none;border-top:1px solid #eee;padding-top:16px;margin-top:8px">
        <p style="font-size:0.82rem;font-weight:700;margin-bottom:12px;color:#555"><i class="fas fa-sliders" style="margin-right:6px"></i>Signage Configuration</p>
        <div class="form-group">
          <label>Signage Type Options <small style="font-weight:400;color:#aaa">(one per line)</small></label>
          <textarea name="signage_types" class="form-control" rows="5" placeholder="Double Face&#10;Single Face&#10;Single Frame&#10;Double Face Frame&#10;Special Design"></textarea>
          <p style="font-size:0.75rem;color:#888;margin-top:4px">Each line = one option in the client dropdown.</p>
        </div>
        <div class="form-group">
          <label>Allowed Light Options</label>
          <div style="display:flex;gap:20px;margin-top:6px">
            <label style="display:flex;align-items:center;gap:6px;font-weight:400;cursor:pointer">
              <input type="checkbox" name="signage_lights[]" value="Lighted" checked> Lighted
            </label>
            <label style="display:flex;align-items:center;gap:6px;font-weight:400;cursor:pointer">
              <input type="checkbox" name="signage_lights[]" value="Non-lighted" checked> Non-lighted
            </label>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:12px;margin-top:8px">
        <button type="submit" class="btn btn-dark">Add Service</button>
        <button type="button" class="action-btn" onclick="document.getElementById('add-modal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;overflow-y:auto;padding:20px">
  <div style="background:#fff;padding:32px;width:100%;max-width:520px;border-radius:6px;max-height:90vh;overflow-y:auto;margin:auto">
    <h3 style="margin-bottom:20px">Edit Service</h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit-id">
      <div class="form-group"><label>Category</label>
        <select name="category" id="edit-category" class="form-control" onchange="toggleSignageConfig('edit',this.value)">
          <option value="basic">Basic Services</option>
          <option value="sublimation">Sublimation Services</option>
          <option value="signage">Signage Services</option>
        </select>
      </div>
      <div class="form-group"><label>Name *</label><input type="text" name="name" id="edit-name" class="form-control" required></div>
      <div class="form-group"><label>Description</label><textarea name="description" id="edit-desc" class="form-control" rows="2"></textarea></div>
      <div class="form-group">
        <label>Icon (FontAwesome)</label>
        <div style="display:flex;align-items:center;gap:10px">
          <input type="text" name="icon" id="edit-icon" class="form-control" oninput="updateIconPreview('edit',this.value)" style="flex:1">
          <span id="edit-icon-preview" style="font-size:1.5rem;width:32px;text-align:center;color:#333"><i class="fas fa-print"></i></span>
        </div>
      </div>
      <div class="form-group"><label>Status</label>
        <select name="is_active" id="edit-active" class="form-control">
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>

      <!-- Signage Config (shown only when category=signage) -->
      <div id="edit-signage-config" style="display:none;border-top:1px solid #eee;padding-top:16px;margin-top:8px">
        <p style="font-size:0.82rem;font-weight:700;margin-bottom:12px;color:#555"><i class="fas fa-sliders" style="margin-right:6px"></i>Signage Configuration</p>
        <div class="form-group">
          <label>Signage Type Options <small style="font-weight:400;color:#aaa">(one per line)</small></label>
          <textarea name="signage_types" id="edit-signage-types" class="form-control" rows="5"></textarea>
          <p style="font-size:0.75rem;color:#888;margin-top:4px">Each line = one option in the client dropdown.</p>
        </div>
        <div class="form-group">
          <label>Allowed Light Options</label>
          <div style="display:flex;gap:20px;margin-top:6px">
            <label style="display:flex;align-items:center;gap:6px;font-weight:400;cursor:pointer">
              <input type="checkbox" name="signage_lights[]" id="edit-light-lighted" value="Lighted"> Lighted
            </label>
            <label style="display:flex;align-items:center;gap:6px;font-weight:400;cursor:pointer">
              <input type="checkbox" name="signage_lights[]" id="edit-light-nonlighted" value="Non-lighted"> Non-lighted
            </label>
          </div>
        </div>
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

    <!-- Sample Images Upload (separate form) -->
    <div style="border-top:1px solid #eee;margin-top:20px;padding-top:20px">
      <p style="font-size:.82rem;font-weight:700;margin-bottom:10px;color:#555"><i class="fas fa-images" style="margin-right:6px;color:#2563eb"></i>Sample Images Gallery</p>
      <div id="edit-samples-grid" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px"></div>
      <form method="POST" enctype="multipart/form-data" id="samples-upload-form">
        <input type="hidden" name="action" value="upload_samples">
        <input type="hidden" name="id" id="samples-svc-id">
        <input type="file" name="sample_images[]" multiple accept="image/*" class="form-control" style="margin-bottom:8px">
        <button type="submit" class="action-btn"><i class="fas fa-upload"></i> Upload Samples</button>
      </form>
    </div>
  </div>
</div>

<script>
// Signage options from DB (keyed by slug)
var signageTypesDB = {};
var signageLightsDB = {};
<?php
$stMap = []; foreach ($signageTypesAll  as $r) $stMap[$r['service_slug']][] = $r['type_label'];
$slMap = []; foreach ($signageLightsAll as $r) $slMap[$r['service_slug']][] = $r['light_label'];
echo 'signageTypesDB  = ' . json_encode($stMap) . ";\n";
echo 'signageLightsDB = ' . json_encode($slMap) . ";\n";
?>

function toggleSignageConfig(prefix, cat) {
  var el = document.getElementById(prefix + '-signage-config');
  if (el) el.style.display = (cat === 'signage') ? 'block' : 'none';
}

function editService(svc) {
  document.getElementById('edit-id').value       = svc.id;
  document.getElementById('edit-category').value = svc.category || 'basic';
  document.getElementById('edit-name').value     = svc.name;
  document.getElementById('edit-desc').value     = svc.description || '';
  document.getElementById('edit-icon').value     = svc.icon || 'fa-print';
  document.getElementById('edit-active').value   = svc.is_active;

  // Signage config
  toggleSignageConfig('edit', svc.category);
  if (svc.category === 'signage') {
    var slug = svc.slug;
    var types  = signageTypesDB[slug]  || [];
    var lights = signageLightsDB[slug] || [];
    document.getElementById('edit-signage-types').value = types.join('\n');
    document.getElementById('edit-light-lighted').checked    = lights.indexOf('Lighted') !== -1;
    document.getElementById('edit-light-nonlighted').checked = lights.indexOf('Non-lighted') !== -1;
  }

  var imgDiv = document.getElementById('edit-current-img');
  if (svc.image_path) {
    imgDiv.innerHTML =
      '<div style="display:inline-flex;align-items:flex-start;gap:8px;margin-bottom:8px">'
      + '<div style="position:relative">'
      + '<img src="../' + svc.image_path + '" style="height:72px;border-radius:5px;border:1px solid #eee;display:block">'
      + '<button onclick="deleteMainImage(' + svc.id + ',this)" title="Delete main image"'
      + ' style="position:absolute;top:3px;right:3px;background:#c00;color:#fff;border:none;border-radius:3px;width:20px;height:20px;cursor:pointer;font-size:.6rem;display:flex;align-items:center;justify-content:center">'
      + '<i class="fas fa-trash"></i></button>'
      + '</div>'
      + '<small style="color:#888;align-self:flex-end">Current image — upload new to replace</small>'
      + '</div>';
  } else {
    imgDiv.innerHTML = '<small style="color:#aaa">No image yet</small>';
  }
  document.getElementById('edit-modal').style.display = 'flex';
  document.getElementById('samples-svc-id').value = svc.id;
  updateIconPreview('edit', svc.icon || 'fa-print');

  // Load sample images via fetch
  fetch('?load_samples=' + svc.id)
    .then(function(r){ return r.json(); })
    .then(function(imgs){
      var grid = document.getElementById('edit-samples-grid');
      grid.innerHTML = '';
      if (!imgs.length) { grid.innerHTML = '<small style="color:#aaa">No sample images yet.</small>'; return; }
      imgs.forEach(function(img){
        var d = document.createElement('div');
        d.style.cssText = 'position:relative;width:90px;height:70px;flex-shrink:0';
        d.innerHTML = '<img src="../' + img.image_path + '" style="width:90px;height:70px;object-fit:cover;border-radius:5px;border:1px solid #eee;display:block">'
          + '<button onclick="deleteSample(' + img.id + ',this.parentNode)" title="Delete image"'
          + ' style="position:absolute;top:3px;right:3px;background:#c00;color:#fff;border:none;border-radius:3px;width:20px;height:20px;cursor:pointer;font-size:.7rem;line-height:1;padding:0;display:flex;align-items:center;justify-content:center">'
          + '<i class="fas fa-trash" style="font-size:.6rem"></i></button>';
        grid.appendChild(d);
      });
    });
}
document.getElementById('add-modal').addEventListener('click', function(e){ if(e.target===this) this.style.display='none'; });
document.getElementById('edit-modal').addEventListener('click', function(e){ if(e.target===this) this.style.display='none'; });

// ── Auto-slug from name ──────────────────────────────────────────────────────
function autoSlug(name) {
  var slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  document.getElementById('add-slug').value = slug;
}

// ── Icon keyword map ─────────────────────────────────────────────────────────
var iconMap = [
  { keys: ['mug','cup','tumbler'],                icon: 'fa-mug-hot' },
  { keys: ['keychain','key chain','keytag'],       icon: 'fa-key' },
  { keys: ['tarpaulin','tarp','streamer'],         icon: 'fa-image' },
  { keys: ['shirt','t-shirt','tshirt','polo'],     icon: 'fa-shirt' },
  { keys: ['jersey'],                              icon: 'fa-person-running' },
  { keys: ['jacket','hoodie'],                     icon: 'fa-vest' },
  { keys: ['neon'],                                icon: 'fa-lightbulb' },
  { keys: ['acrylic'],                             icon: 'fa-layer-group' },
  { keys: ['stainless','steel','metal'],           icon: 'fa-circle-dot' },
  { keys: ['panaflex','flex'],                     icon: 'fa-rectangle-ad' },
  { keys: ['billboard'],                           icon: 'fa-rectangle-ad' },
  { keys: ['signage','sign'],                      icon: 'fa-sign-hanging' },
  { keys: ['banner'],                              icon: 'fa-flag' },
  { keys: ['frame','framing'],                     icon: 'fa-border-all' },
  { keys: ['photo','picture','portrait'],          icon: 'fa-camera' },
  { keys: ['sticker','decal','vinyl'],             icon: 'fa-star' },
  { keys: ['id','card','lace'],                    icon: 'fa-id-card' },
  { keys: ['notebook','journal'],                  icon: 'fa-book' },
  { keys: ['calendar'],                            icon: 'fa-calendar' },
  { keys: ['bag','tote','pouch'],                  icon: 'fa-bag-shopping' },
  { keys: ['umbrella'],                            icon: 'fa-umbrella' },
  { keys: ['pen','ballpen','pencil'],              icon: 'fa-pen' },
  { keys: ['cap','hat'],                           icon: 'fa-hat-cowboy' },
  { keys: ['pillow','cushion'],                    icon: 'fa-couch' },
  { keys: ['canvas'],                              icon: 'fa-palette' },
  { keys: ['sublimation'],                         icon: 'fa-fire' },
  { keys: ['print','printing'],                    icon: 'fa-print' },
];

function suggestIcon(name) {
  var lower = name.toLowerCase();
  for (var i = 0; i < iconMap.length; i++) {
    for (var j = 0; j < iconMap[i].keys.length; j++) {
      if (lower.indexOf(iconMap[i].keys[j]) !== -1) return iconMap[i].icon;
    }
  }
  return null;
}

function autoSuggestIcon(prefix, name) {
  var icon = suggestIcon(name);
  if (icon) {
    document.getElementById(prefix + '-icon').value = icon;
    updateIconPreview(prefix, icon);
  }
}

function updateIconPreview(prefix, icon) {
  var clean = icon.replace(/^fas?\s+/, '').trim();
  var el = document.getElementById(prefix + '-icon-preview');
  if (el) el.innerHTML = '<i class="fas ' + clean + '"></i>';
}

function deleteSample(imgId, container) {
  if (!confirm('Are you sure you want to delete this image permanently?')) return;
  var fd = new FormData();
  fd.append('action', 'delete_sample');
  fd.append('img_id', imgId);
  fetch('services.php', { method: 'POST', body: fd })
    .then(function(){
      container.style.transition = 'opacity .3s';
      container.style.opacity = '0';
      setTimeout(function(){ container.remove(); }, 320);
    });
}

function deleteMainImage(svcId, btn) {
  if (!confirm('Are you sure you want to delete the main image permanently?')) return;
  var fd = new FormData();
  fd.append('action', 'remove_image');
  fd.append('id', svcId);
  fetch('services.php', { method: 'POST', body: fd })
    .then(function(){
      var imgDiv = document.getElementById('edit-current-img');
      if (imgDiv) imgDiv.innerHTML = '<small style="color:#aaa">No image yet</small>';
    });
}
</script>
</body>
</html>
