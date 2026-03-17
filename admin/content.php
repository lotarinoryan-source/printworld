<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '_layout.php';
requireAdmin();

$db = db();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE site_content SET content_value=? WHERE content_key=?");
    foreach ($_POST['content'] as $key => $value) {
        $key   = sanitizeInput($key);
        $value = strip_tags(trim($value));
        $stmt->bind_param('ss', $value, $key);
        $stmt->execute();
    }
    $success = 'Content updated successfully.';
}

$result = $db->query("SELECT * FROM site_content ORDER BY id");
$content = [];
while ($row = $result->fetch_assoc()) {
    $content[$row['content_key']] = $row['content_value'];
}

$fields = [
    'Hero Section'        => ['hero_title' => 'Hero Title', 'hero_subtitle' => 'Hero Subtitle'],
    'About Section'       => ['about_title' => 'About Title', 'about_text' => 'About Text'],
    'Contact Info'        => ['contact_address' => 'Address', 'contact_phone' => 'Phone', 'contact_email' => 'Email', 'contact_hours' => 'Business Hours'],
    'PDF Quotation — Terms &amp; Conditions' => ['quotation_tnc' => 'Terms &amp; Conditions (one item per line, no bullet needed)'],
];
$textareaKeys = ['about_text', 'hero_subtitle', 'quotation_tnc'];
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
<?php adminSidebar('content'); ?>
<main class="admin-main">
  <div class="admin-topbar"><h1>Site Content</h1></div>

  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

  <form method="POST">
    <?php foreach ($fields as $section => $items): ?>
    <div class="admin-card" style="margin-bottom:24px">
      <div class="admin-card-header"><h3><?= $section ?></h3></div>
      <?php foreach ($items as $key => $label): ?>
      <div class="form-group">
        <label><?= $label ?></label>
        <?php if (in_array($key, $textareaKeys)): ?>
        <textarea name="content[<?= $key ?>]" class="form-control" rows="<?= $key === 'quotation_tnc' ? 10 : 3 ?>"><?= htmlspecialchars($content[$key] ?? '') ?></textarea>
        <?php if ($key === 'quotation_tnc'): ?>
        <p style="font-size:0.75rem;color:#888;margin-top:4px"><i class="fas fa-info-circle"></i> Each line becomes one bullet point in the PDF. Press Enter to add a new item.</p>
        <?php endif; ?>
        <?php else: ?>
        <input type="text" name="content[<?= $key ?>]" class="form-control" value="<?= htmlspecialchars($content[$key] ?? '') ?>">
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-dark"><i class="fas fa-save"></i> Save Content</button>
  </form>
</main>
</body>
</html>
