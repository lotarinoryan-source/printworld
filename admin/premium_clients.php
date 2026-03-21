<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '_layout.php';
requireAdmin();

$db  = db();
$msg = $err = '';

$defaultTnc = "Full payment must be made within 30 calendar days from project completion.\nPrintworld shall not be held liable for any acts of God that may occur before or during delivery, installation or execution of materials.\nSignages for this project will be installed before the store opening.\nPrintworld will tap to the nearest electricity supply up to 2 meters in excess to this provision will be charged to client.\n10% weekly interest will be charged as penalty for late payment.\nAny intentional scratches or damages on the product will void the warranty.\n(5) years of Avery Sticker warranty\n(6) months of LED warranty.\n(1) year of faulty workmanship.";

// ── Add client ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name    = trim($_POST['company_name'] ?? '');
    $contact = trim($_POST['contact_person'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $addr    = trim($_POST['address'] ?? '');
    $dear    = trim($_POST['dear'] ?? '');
    $tnc     = trim($_POST['terms_conditions'] ?? '');
    if (empty($name)) {
        $err = 'Company name is required.';
    } else {
        $s = $db->prepare('INSERT INTO premium_clients (company_name, contact_person, email, phone, address, dear, terms_conditions) VALUES (?,?,?,?,?,?,?)');
        $s->bind_param('sssssss', $name, $contact, $email, $phone, $addr, $dear, $tnc);
        $s->execute();
        $msg = 'Premium client added.';
    }
}

// ── Toggle active ────────────────────────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $db->query("UPDATE premium_clients SET is_active = NOT is_active WHERE id=" . (int)$_GET['toggle']);
    header('Location: premium_clients.php'); exit;
}

// ── Delete client ────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $db->query("DELETE FROM premium_clients WHERE id=" . (int)$_GET['delete']);
    header('Location: premium_clients.php'); exit;
}

// ── Edit / save client ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_save'])) {
    $eid     = (int)$_POST['edit_id'];
    $name    = trim($_POST['edit_company_name'] ?? '');
    $contact = trim($_POST['edit_contact_person'] ?? '');
    $email   = trim($_POST['edit_email'] ?? '');
    $phone   = trim($_POST['edit_phone'] ?? '');
    $addr    = trim($_POST['edit_address'] ?? '');
    $dear    = trim($_POST['edit_dear'] ?? '');
    $tnc     = trim($_POST['edit_terms_conditions'] ?? '');
    if (!empty($name)) {
        $s = $db->prepare('UPDATE premium_clients SET company_name=?, contact_person=?, email=?, phone=?, address=?, dear=?, terms_conditions=? WHERE id=?');
        $s->bind_param('sssssssi', $name, $contact, $email, $phone, $addr, $dear, $tnc, $eid);
        $s->execute();
        // Auto-regenerate all linked PDFs
        require_once '../includes/pdf_generator.php';
        $linked = $db->query("SELECT * FROM final_quotations WHERE premium_client_id=$eid AND is_premium=1")->fetch_all(MYSQLI_ASSOC);
        $regenCount = 0;
        foreach ($linked as $lq) {
            $items = $db->query("SELECT * FROM final_quotation_items WHERE quotation_id={$lq['id']}")->fetch_all(MYSQLI_ASSOC);
            $lq['terms_conditions'] = $tnc;
            $lq['company_name']     = $name;
            if (!$lq['prem_address']) $lq['prem_address'] = $addr;
            if (!empty($lq['branch_id'])) {
                $br = $db->query("SELECT * FROM client_branches WHERE id=" . (int)$lq['branch_id'])->fetch_assoc();
                if ($br) {
                    $lq['prem_branch'] = $br['branch_name'];
                    if ($br['address']) $lq['prem_address'] = $br['address'];
                    if ($br['dear'])    $lq['prem_dear']    = $br['dear'];
                }
            }
            $pdfPath = generateQuotationPDF($lq, $items);
            $upd = $db->prepare('UPDATE final_quotations SET pdf_path=? WHERE id=?');
            $upd->bind_param('si', $pdfPath, $lq['id']);
            $upd->execute();
            $regenCount++;
        }
        $msg = 'Client updated' . ($regenCount > 0 ? " and {$regenCount} PDF(s) regenerated." : '.');
    }
}

$clients = $db->query('SELECT * FROM premium_clients ORDER BY company_name')->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/pw.png">
  <title>Printworld - Premium Clients</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:1000;align-items:center;justify-content:center;overflow-y:auto;padding:20px}
    .modal-overlay.open{display:flex}
    .modal-box{background:#fff;border-radius:8px;width:100%;max-width:600px;padding:28px 32px;box-shadow:0 8px 40px rgba(0,0,0,0.2);margin:auto}
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

<div style="display:grid;grid-template-columns:1fr 400px;gap:24px;align-items:start">

  <!-- Client list -->
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
            <button type="button" class="action-btn" onclick='openEdit(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)'>
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

  <!-- Add Form -->
  <div class="admin-card">
    <div class="admin-card-header"><h3>Add Premium Client</h3></div>
    <form method="POST" style="padding:4px 0">
      <div class="form-row">
        <div class="form-group">
          <label>Company Name *</label>
          <input type="text" name="company_name" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Contact Person</label>
          <input type="text" name="contact_person" class="form-control">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" class="form-control">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label>Address</label>
        <input type="text" name="address" class="form-control">
      </div>
      <div class="form-group">
        <label>Dear (Recipient)</label>
        <input type="text" name="dear" class="form-control" placeholder="e.g. Ms. Eva">
      </div>
      <div class="form-group">
        <label>Terms &amp; Conditions <small style="color:#888;font-weight:400">(leave blank to use default)</small></label>
        <textarea name="terms_conditions" class="form-control" rows="5" placeholder="Enter custom T&C per line, or leave blank for default..."><?= htmlspecialchars($defaultTnc) ?></textarea>
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
      <div class="form-row">
        <div class="form-group">
          <label>Company Name *</label>
          <input type="text" name="edit_company_name" id="edit-company" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Contact Person</label>
          <input type="text" name="edit_contact_person" id="edit-contact" class="form-control">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="edit_email" id="edit-email" class="form-control">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="edit_phone" id="edit-phone" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label>Address</label>
        <input type="text" name="edit_address" id="edit-address" class="form-control">
      </div>
      <div class="form-group">
        <label>Dear (Recipient)</label>
        <input type="text" name="edit_dear" id="edit-dear" class="form-control">
      </div>
      <div class="form-group">
        <label>Terms &amp; Conditions</label>
        <textarea name="edit_terms_conditions" id="edit-tnc" class="form-control" rows="6"></textarea>
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
function openEdit(c) {
  document.getElementById('edit-id').value      = c.id;
  document.getElementById('edit-company').value = c.company_name || '';
  document.getElementById('edit-contact').value = c.contact_person || '';
  document.getElementById('edit-email').value   = c.email || '';
  document.getElementById('edit-phone').value   = c.phone || '';
  document.getElementById('edit-address').value = c.address || '';
  document.getElementById('edit-dear').value    = c.dear || '';
  document.getElementById('edit-tnc').value     = c.terms_conditions || '';
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
