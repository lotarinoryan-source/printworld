<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '_layout.php';
requireAdmin();

$db  = db();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name    = trim($_POST['company_name'] ?? '');
    $contact = trim($_POST['contact_person'] ?? '');
    $addr    = trim($_POST['address'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    if (empty($name)) {
        $err = 'Company name is required.';
    } else {
        $s = $db->prepare('INSERT INTO premium_clients (company_name, contact_person, address, email, phone) VALUES (?,?,?,?,?)');
        $s->bind_param('sssss', $name, $contact, $addr, $email, $phone);
        $s->execute();
        $msg = 'Premium client added.';
    }
}

if (isset($_GET['toggle'])) {
    $tid = (int)$_GET['toggle'];
    $db->query("UPDATE premium_clients SET is_active = NOT is_active WHERE id=$tid");
    header('Location: premium_clients.php'); exit;
}

if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $db->query("DELETE FROM premium_clients WHERE id=$did");
    header('Location: premium_clients.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_save'])) {
    $eid     = (int)$_POST['edit_id'];
    $name    = trim($_POST['edit_company_name'] ?? '');
    $contact = trim($_POST['edit_contact_person'] ?? '');
    $addr    = trim($_POST['edit_address'] ?? '');
    $email   = trim($_POST['edit_email'] ?? '');
    $phone   = trim($_POST['edit_phone'] ?? '');
    if (!empty($name)) {
        $s = $db->prepare('UPDATE premium_clients SET company_name=?, contact_person=?, address=?, email=?, phone=? WHERE id=?');
        $s->bind_param('sssssi', $name, $contact, $addr, $email, $phone, $eid);
        $s->execute();
        $msg = 'Client updated.';
    }
}

$clients = $db->query('SELECT * FROM premium_clients ORDER BY company_name')->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Premium Clients — <?= SITE_NAME ?> Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:1000;align-items:center;justify-content:center}
    .modal-overlay.open{display:flex}
    .modal-box{background:#fff;border-radius:8px;width:100%;max-width:480px;padding:28px 32px;box-shadow:0 8px 40px rgba(0,0,0,0.2)}
    .modal-box h3{font-size:1.1rem;font-weight:800;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between}
    .modal-close{background:none;border:none;font-size:1.3rem;cursor:pointer;color:#888;line-height:1}
  </style>
</head>
<body class="admin-body">
<?php adminSidebar('premium_clients'); ?>
<main class="admin-main">
<div class="admin-topbar">
  <h1><i class="fas fa-star" style="color:#f5c842;margin-right:8px"></i>Premium Clients</h1>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">

  <div class="admin-card">
    <div class="admin-card-header"><h3>All Premium Clients</h3></div>
    <table class="data-table">
      <thead>
        <tr><th>Company</th><th>Contact Person</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($clients)): ?>
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray-400)">No premium clients yet.</td></tr>
        <?php else: foreach ($clients as $c): ?>
        <tr>
          <td><strong><?= htmlspecialchars($c['company_name']) ?></strong></td>
          <td><?= htmlspecialchars($c['contact_person'] ?: '—') ?></td>
          <td><?= htmlspecialchars($c['email'] ?: '—') ?></td>
          <td><?= htmlspecialchars($c['phone'] ?: '—') ?></td>
          <td>
            <a href="?toggle=<?= $c['id'] ?>" style="text-decoration:none">
              <?= $c['is_active'] ? '<span class="badge badge-responded">Active</span>' : '<span class="badge badge-pending">Inactive</span>' ?>
            </a>
          </td>
          <td style="display:flex;gap:6px">
            <button type="button" class="action-btn" onclick='openEdit(<?= $c["id"] ?>,<?= json_encode($c["company_name"]) ?>,<?= json_encode($c["contact_person"] ?? "") ?>,<?= json_encode($c["address"] ?? "") ?>,<?= json_encode($c["email"] ?? "") ?>,<?= json_encode($c["phone"] ?? "") ?>)'>
              <i class="fas fa-pen"></i> Edit
            </button>
            <a href="?delete=<?= $c['id'] ?>" class="action-btn danger" onclick="return confirm('Delete this client?')">
              <i class="fas fa-trash"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="admin-card">
    <div class="admin-card-header"><h3>Add Premium Client</h3></div>
    <form method="POST" style="padding:4px 0">
      <div class="form-group">
        <label>Company Name *</label>
        <input type="text" name="company_name" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Contact Person</label>
        <input type="text" name="contact_person" class="form-control">
      </div>
      <div class="form-group">
        <label>Address</label>
        <input type="text" name="address" class="form-control">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control">
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control">
      </div>
      <button type="submit" name="add" class="btn btn-dark" style="width:100%;justify-content:center">
        <i class="fas fa-plus"></i> Add Client
      </button>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="edit-modal">
  <div class="modal-box">
    <h3>
      <span><i class="fas fa-pen" style="margin-right:8px"></i>Edit Client</span>
      <button class="modal-close" onclick="closeEdit()" type="button">&times;</button>
    </h3>
    <form method="POST">
      <input type="hidden" name="edit_save" value="1">
      <input type="hidden" name="edit_id" id="edit-id">
      <div class="form-group">
        <label>Company Name *</label>
        <input type="text" name="edit_company_name" id="edit-company" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Contact Person</label>
        <input type="text" name="edit_contact_person" id="edit-contact" class="form-control">
      </div>
      <div class="form-group">
        <label>Address</label>
        <input type="text" name="edit_address" id="edit-address" class="form-control">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="edit_email" id="edit-email" class="form-control">
      </div>
      <div class="form-group">
        <label>Phone / Contact Number</label>
        <input type="text" name="edit_phone" id="edit-phone" class="form-control">
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-dark" style="flex:1;justify-content:center">
          <i class="fas fa-save"></i> Save Changes
        </button>
        <button type="button" class="action-btn" onclick="closeEdit()">Cancel</button>
      </div>
    </form>
  </div>
</div>

</main>
<script>
function openEdit(id, company, contact, address, email, phone) {
  document.getElementById('edit-id').value      = id;
  document.getElementById('edit-company').value = company;
  document.getElementById('edit-contact').value = contact;
  document.getElementById('edit-address').value = address;
  document.getElementById('edit-email').value   = email;
  document.getElementById('edit-phone').value   = phone;
  document.getElementById('edit-modal').classList.add('open');
}
function closeEdit() {
  document.getElementById('edit-modal').classList.remove('open');
}
document.getElementById('edit-modal').addEventListener('click', function(e) {
  if (e.target === this) closeEdit();
});
</script>
</body>
</html>