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

    if ($action === 'upload' && !empty($_FILES['images']['name'][0])) {
        $count = count($_FILES['images']['name']);
        $uploaded = 0;
        for ($i = 0; $i < $count; $i++) {
            $file = [
                'name'     => $_FILES['images']['name'][$i],
                'type'     => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error'    => $_FILES['images']['error'][$i],
                'size'     => $_FILES['images']['size'][$i],
            ];
            if ($file['error'] === 0) {
                $path = handleFileUpload($file, 'gallery');
                if ($path) {
                    $title = sanitizeInput($_POST['title'] ?? '');
                    $cat   = sanitizeInput($_POST['category'] ?? '');
                    $stmt  = $db->prepare("INSERT INTO gallery (title, image_path, category) VALUES (?,?,?)");
                    $stmt->bind_param('sss', $title, $path, $cat);
                    $stmt->execute();
                    $uploaded++;
                }
            }
        }
        $success = "$uploaded image(s) uploaded.";
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("SELECT image_path FROM gallery WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && file_exists('../' . $row['image_path'])) {
            unlink('../' . $row['image_path']);
        }
        $del = $db->prepare("DELETE FROM gallery WHERE id=?");
        $del->bind_param('i', $id);
        $del->execute() ? $success = 'Image deleted.' : $error = 'Delete failed.';
    }
}

$images = $db->query("SELECT * FROM gallery ORDER BY sort_order ASC, created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Gallery — <?= SITE_NAME ?> Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php adminSidebar('gallery'); ?>
<main class="admin-main">
  <div class="admin-topbar"><h1>Gallery Management</h1></div>

  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

  <div class="admin-card" style="margin-bottom:24px">
    <div class="admin-card-header"><h3>Upload Images</h3></div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload">
      <div class="form-row">
        <div class="form-group">
          <label>Title (optional)</label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Tarpaulin Sample">
        </div>
        <div class="form-group">
          <label>Category</label>
          <input type="text" name="category" class="form-control" placeholder="e.g. Tarpaulin">
        </div>
      </div>
      <div class="upload-zone" onclick="document.getElementById('img-upload').click()">
        <i class="fas fa-cloud-upload-alt"></i>
        <p>Click to select images (JPG, PNG, WebP)</p>
        <small style="color:var(--gray-400)">Max 5MB per file</small>
      </div>
      <input type="file" id="img-upload" name="images[]" multiple accept="image/*" style="display:none" onchange="previewFiles(this)">
      <div id="preview-grid" class="gallery-upload-grid" style="margin-top:16px"></div>
      <button type="submit" class="btn btn-dark" style="margin-top:16px"><i class="fas fa-upload"></i> Upload</button>
    </form>
  </div>

  <div class="admin-card">
    <div class="admin-card-header"><h3>Gallery Images</h3></div>
    <div class="gallery-upload-grid">
      <?php while ($img = $images->fetch_assoc()): ?>
      <div class="gallery-thumb">
        <img src="../<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($img['title'] ?? '') ?>">
        <form method="POST" onsubmit="return confirm('Delete this image?')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $img['id'] ?>">
          <button type="submit" class="remove-btn" title="Delete"><i class="fas fa-times"></i></button>
        </form>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</main>
<script>
function previewFiles(input) {
  const grid = document.getElementById('preview-grid');
  grid.innerHTML = '';
  Array.from(input.files).forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.className = 'gallery-thumb';
      div.innerHTML = `<img src="${e.target.result}" alt="preview">`;
      grid.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}
</script>
</body>
</html>
