<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '_layout.php';
requireAdmin();

$db = db();

// Ensure site_settings table exists
$db->query("CREATE TABLE IF NOT EXISTS site_settings (
    `key`   VARCHAR(100) PRIMARY KEY,
    `value` TEXT NOT NULL DEFAULT ''
)");

// Helper: get/set setting
function getSetting($db, string $key, string $default = ''): string {
    $r = $db->query("SELECT `value` FROM site_settings WHERE `key`='" . $db->real_escape_string($key) . "' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    return $row ? $row['value'] : $default;
}
function setSetting($db, string $key, string $value): void {
    $k = $db->real_escape_string($key);
    $v = $db->real_escape_string($value);
    $db->query("INSERT INTO site_settings (`key`,`value`) VALUES ('$k','$v') ON DUPLICATE KEY UPDATE `value`='$v'");
}

$success = '';
$errors  = [];

// ── Change Admin Password ──────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current = $_POST['current_pw']  ?? '';
    $new     = $_POST['new_pw']      ?? '';
    $confirm = $_POST['confirm_pw']  ?? '';

    $stmt = $db->prepare("SELECT password FROM admins WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $_SESSION['admin_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($current, $row['password'])) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $upd  = $db->prepare("UPDATE admins SET password=? WHERE id=?");
        $upd->bind_param('si', $hash, $_SESSION['admin_id']);
        $upd->execute();
        $success = 'Password changed successfully.';
    }
}

// ── Secondary Password Settings ────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'save_secondary') {
    $gcashEnabled  = isset($_POST['gcash_lock_enabled']) ? '1' : '0';
    setSetting($db, 'gcash_lock_enabled', $gcashEnabled);

    $currentGcashPw = trim($_POST['gcash_current_pw'] ?? '');
    $newGcashPw     = trim($_POST['gcash_secondary_pw'] ?? '');

    if ($newGcashPw !== '') {
        $storedPw = getSetting($db, 'gcash_lock_password', GCASH_PASSWORD);
        if ($currentGcashPw === '') {
            $errors[] = 'Please enter the current GCash password to change it.';
        } elseif ($currentGcashPw !== $storedPw) {
            $errors[] = 'Current GCash password is incorrect.';
        } else {
            setSetting($db, 'gcash_lock_password', $newGcashPw);
            $success = 'Settings saved.';
        }
    } else {
        $success = 'Settings saved.';
    }
}

// Load current settings
$gcashLockEnabled = getSetting($db, 'gcash_lock_enabled', '1');
$gcashLockPw      = getSetting($db, 'gcash_lock_password', GCASH_PASSWORD);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/pw.png">
  <title>Printworld - Settings</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .settings-section { background:#fff; border:1px solid #eee; border-radius:6px; padding:28px 32px; margin-bottom:24px; }
    .settings-section h3 { font-size:.85rem; letter-spacing:1.5px; text-transform:uppercase; margin-bottom:20px; padding-bottom:12px; border-bottom:1px solid #eee; display:flex; align-items:center; gap:10px; }
    .settings-section h3 i { color:#555; }
    .toggle-row { display:flex; align-items:center; justify-content:space-between; padding:14px 0; border-bottom:1px solid #f5f5f5; gap:16px; flex-wrap:wrap; }
    .toggle-row:last-child { border-bottom:none; }
    .toggle-label { font-size:.9rem; font-weight:600; }
    .toggle-desc  { font-size:.8rem; color:#888; margin-top:2px; }
    .toggle-switch { position:relative; width:48px; height:26px; flex-shrink:0; }
    .toggle-switch input { opacity:0; width:0; height:0; }
    .toggle-slider { position:absolute; inset:0; background:#ccc; border-radius:26px; cursor:pointer; transition:.3s; }
    .toggle-slider::before { content:''; position:absolute; width:20px; height:20px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s; }
    .toggle-switch input:checked + .toggle-slider { background:#16a34a; }
    .toggle-switch input:checked + .toggle-slider::before { transform:translateX(22px); }
    .pw-reveal { position:relative; }
    .pw-reveal input { padding-right:40px; }
    .pw-reveal .eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:#aaa; font-size:.9rem; }
    .pw-reveal .eye:hover { color:#111; }
  </style>
</head>
<body class="admin-body">
<?php adminSidebar('settings'); ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1><i class="fas fa-gear" style="margin-right:8px"></i>Admin Settings</h1>
  </div>

  <?php if ($success): ?>
  <div class="alert alert-success" style="margin-bottom:20px"><i class="fas fa-check-circle" style="margin-right:6px"></i><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
  <div class="alert alert-error" style="margin-bottom:20px">
    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Change Password -->
  <div class="settings-section">
    <h3><i class="fas fa-key"></i> Change Admin Password</h3>
    <form method="POST" style="max-width:440px">
      <input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label>Current Password</label>
        <div class="pw-reveal">
          <input type="password" name="current_pw" id="cur-pw" class="form-control" placeholder="••••••••" required>
          <span class="eye" onclick="togglePw('cur-pw',this)"><i class="fas fa-eye"></i></span>
        </div>
      </div>
      <div class="form-group">
        <label>New Password</label>
        <div class="pw-reveal">
          <input type="password" name="new_pw" id="new-pw" class="form-control" placeholder="Min. 6 characters" required>
          <span class="eye" onclick="togglePw('new-pw',this)"><i class="fas fa-eye"></i></span>
        </div>
      </div>
      <div class="form-group">
        <label>Confirm New Password</label>
        <div class="pw-reveal">
          <input type="password" name="confirm_pw" id="con-pw" class="form-control" placeholder="Repeat new password" required>
          <span class="eye" onclick="togglePw('con-pw',this)"><i class="fas fa-eye"></i></span>
        </div>
      </div>
      <button type="submit" class="btn btn-dark"><i class="fas fa-save"></i> Update Password</button>
    </form>
  </div>

  <!-- Secondary Page Locks -->
  <div class="settings-section">
    <h3><i class="fas fa-shield-halved"></i> Page Access Control</h3>
    <form method="POST">
      <input type="hidden" name="action" value="save_secondary">

      <div class="toggle-row">
        <div>
          <div class="toggle-label"><i class="fas fa-mobile-screen-button" style="color:#16a34a;margin-right:6px"></i>GCash Transactions Lock</div>
          <div class="toggle-desc">Require a secondary password before accessing the GCash Transactions page.</div>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="gcash_lock_enabled" id="gcash-toggle" <?= $gcashLockEnabled === '1' ? 'checked' : '' ?> onchange="toggleSection('gcash-pw-section',this.checked)">
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div id="gcash-pw-section" style="padding:16px 0 8px;<?= $gcashLockEnabled !== '1' ? 'display:none' : '' ?>">
        <div style="max-width:340px">
          <div class="form-group">
            <label>Current GCash Password</label>
            <div class="pw-reveal">
              <input type="password" name="gcash_current_pw" id="gcash-cur-pw" class="form-control"
                placeholder="Enter current GCash password"
                autocomplete="current-password">
              <span class="eye" onclick="togglePw('gcash-cur-pw',this)"><i class="fas fa-eye"></i></span>
            </div>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label>New GCash Password</label>
            <div class="pw-reveal">
              <input type="password" name="gcash_secondary_pw" id="gcash-pw" class="form-control"
                placeholder="Leave blank to keep current"
                autocomplete="new-password">
              <span class="eye" onclick="togglePw('gcash-pw',this)"><i class="fas fa-eye"></i></span>
            </div>
            <p style="font-size:.78rem;color:#888;margin-top:6px">Leave new password blank to keep it unchanged.</p>
          </div>
        </div>
      </div>

      <div style="margin-top:20px">
        <button type="submit" class="btn btn-dark"><i class="fas fa-save"></i> Save Settings</button>
      </div>
    </form>
  </div>

</main>
<script>
function togglePw(id, btn) {
  var inp = document.getElementById(id);
  var icon = btn.querySelector('i');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'fas fa-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'fas fa-eye';
  }
}
function toggleSection(id, show) {
  document.getElementById(id).style.display = show ? '' : 'none';
}
</script>
</body>
</html>
