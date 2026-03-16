<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '_layout.php';
requireAdmin();

$db = db();
$msg = $err = '';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = sanitizeInput($_POST['status']);
    if (in_array($status, ['pending','viewed','quoted','completed'])) {
        $s = $db->prepare("UPDATE quotation_requests SET status=? WHERE id=?");
        $s->bind_param('si', $status, $id);
        $s->execute();
    }
    header('Location: quotations.php?view=' . $id);
    exit;
}

// View single request
$viewId = (int)($_GET['view'] ?? 0);
$req = null; $reqItems = [];
if ($viewId) {
    $s = $db->prepare("SELECT * FROM quotation_requests WHERE id=?");
    $s->bind_param('i', $viewId);
    $s->execute();
    $req = $s->get_result()->fetch_assoc();
    if ($req && $req['status'] === 'pending') {
        $db->query("UPDATE quotation_requests SET status='viewed' WHERE id=$viewId");
        $req['status'] = 'viewed';
    }
    if ($req) {
        $si = $db->prepare("SELECT * FROM request_items WHERE request_id=?");
        $si->bind_param('i', $viewId);
        $si->execute();
        $reqItems = $si->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Search/list
$search = sanitizeInput($_GET['s'] ?? '');
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$sql = "SELECT * FROM quotation_requests WHERE 1=1";
if ($search) $sql .= " AND (customer_name LIKE '%" . $db->real_escape_string($search) . "%' OR email LIKE '%" . $db->real_escape_string($search) . "%' OR request_number LIKE '%" . $db->real_escape_string($search) . "%')";
if ($statusFilter) $sql .= " AND status='" . $db->real_escape_string($statusFilter) . "'";
$sql .= " ORDER BY created_at DESC";
$requests = $db->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Quotation Requests — <?= SITE_NAME ?> Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php adminSidebar('quotations'); ?>
<main class="admin-main">

<?php if ($req): ?>
<!-- ===== DETAIL VIEW ===== -->
<div class="admin-topbar">
  <h1>Request: <?= htmlspecialchars($req['request_number']) ?></h1>
  <a href="quotations.php" class="action-btn"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start">
  <div>
    <!-- Client Info -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-header"><h3>Client Information</h3></div>
      <table class="data-table">
        <tr><td style="color:var(--gray-400);width:130px">Name</td><td><strong><?= htmlspecialchars($req['customer_name']) ?></strong></td></tr>
        <tr><td style="color:var(--gray-400)">Company</td><td><?= htmlspecialchars($req['company_name'] ?: '—') ?></td></tr>
        <tr><td style="color:var(--gray-400)">Email</td><td><a href="mailto:<?= htmlspecialchars($req['email']) ?>"><?= htmlspecialchars($req['email']) ?></a></td></tr>
        <tr><td style="color:var(--gray-400)">Phone</td><td><?= htmlspecialchars($req['contact_number']) ?></td></tr>
        <tr><td style="color:var(--gray-400)">Date</td><td><?= date('F d, Y g:i A', strtotime($req['created_at'])) ?></td></tr>
        <?php if ($req['message']): ?>
        <tr><td style="color:var(--gray-400)">Message</td><td><?= nl2br(htmlspecialchars($req['message'])) ?></td></tr>
        <?php endif; ?>
        <?php if ($req['signage_address']): ?>
        <tr><td style="color:var(--gray-400)">Location</td><td><?= htmlspecialchars($req['signage_address']) ?></td></tr>
        <?php endif; ?>
        <?php if ($req['design_file']): ?>
        <tr><td style="color:var(--gray-400)">Design File</td><td><a href="../<?= htmlspecialchars($req['design_file']) ?>" target="_blank" class="action-btn"><i class="fas fa-file"></i> View File</a></td></tr>
        <?php endif; ?>
      </table>
    </div>

    <!-- Requested Items -->
    <div class="admin-card">
      <div class="admin-card-header"><h3>Requested Items</h3></div>
      <table class="data-table">
        <thead><tr><th>#</th><th>Service</th><th>Description</th><th>Qty</th></tr></thead>
        <tbody>
          <?php foreach ($reqItems as $i => $item): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($item['service_type']) ?></strong></td>
            <td><?= nl2br(htmlspecialchars($item['description'])) ?></td>
            <td><?= $item['quantity'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div>
    <!-- Status -->
    <div class="admin-card" style="margin-bottom:16px">
      <div class="admin-card-header"><h3>Status</h3></div>
      <form method="POST">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="id" value="<?= $req['id'] ?>">
        <div class="form-group">
          <select name="status" class="form-control">
            <?php foreach (['pending','viewed','quoted','completed'] as $s): ?>
            <option value="<?= $s ?>" <?= $req['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-dark" style="width:100%;justify-content:center">Update Status</button>
      </form>
    </div>

    <!-- Actions -->
    <div class="admin-card">
      <div class="admin-card-header"><h3>Actions</h3></div>
      <div style="display:flex;flex-direction:column;gap:10px">
        <a href="create_quotation.php?request_id=<?= $req['id'] ?>" class="btn btn-dark" style="justify-content:center">
          <i class="fas fa-file-invoice"></i> Create Final Quotation
        </a>
        <a href="create_quotation.php?request_id=<?= $req['id'] ?>&premium=1" class="btn btn-outline" style="justify-content:center;color:var(--black);border-color:var(--gray-200)">
          <i class="fas fa-star"></i> Premium Quotation
        </a>
        <a href="mailto:<?= htmlspecialchars($req['email']) ?>" class="btn btn-outline" style="justify-content:center;color:var(--black);border-color:var(--gray-200)">
          <i class="fas fa-envelope"></i> Email Client
        </a>
        <?php if ($req['request_pdf_path'] && file_exists('../' . $req['request_pdf_path'])): ?>
        <a href="../download.php?file=<?= urlencode(basename($req['request_pdf_path'])) ?>&type=request" class="btn btn-outline" style="justify-content:center;color:var(--black);border-color:var(--gray-200)">
          <i class="fas fa-download"></i> Download Request PDF
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ===== LIST VIEW ===== -->
<div class="admin-topbar">
  <h1>Quotation Requests</h1>
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
    <input type="text" name="s" class="form-control" placeholder="Search name, email..." value="<?= htmlspecialchars($search) ?>" style="width:200px">
    <select name="status" class="form-control" style="width:130px">
      <option value="">All Status</option>
      <?php foreach (['pending','viewed','quoted','completed'] as $s): ?>
      <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-dark">Filter</button>
  </form>
</div>

<div class="admin-card">
  <table class="data-table">
    <thead>
      <tr><th>Request #</th><th>Client</th><th>Email</th><th>Phone</th><th>Items</th><th>Status</th><th>Date</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php
      $hasRows = false;
      while ($row = $requests->fetch_assoc()):
        $hasRows = true;
        $itemCount = $db->query("SELECT COUNT(*) as c FROM request_items WHERE request_id=" . (int)$row['id'])->fetch_assoc()['c'];
      ?>
      <tr>
        <td><strong><?= htmlspecialchars($row['request_number']) ?></strong></td>
        <td><?= htmlspecialchars($row['customer_name']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><?= htmlspecialchars($row['contact_number']) ?></td>
        <td style="text-align:center"><?= $itemCount ?></td>
        <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
        <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
        <td>
          <a href="quotations.php?view=<?= $row['id'] ?>" class="action-btn">View</a>
          <a href="create_quotation.php?request_id=<?= $row['id'] ?>" class="action-btn">Quote</a>
        </td>
      </tr>
      <?php endwhile; ?>
      <?php if (!$hasRows): ?>
      <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--gray-400)">No requests found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
</main>
</body>
</html>
