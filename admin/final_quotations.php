<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '_layout.php';
requireAdmin();

$db = db();
$viewId = (int)($_GET['view'] ?? 0);
$sent   = isset($_GET['sent']) ? (int)$_GET['sent'] : -1;

// Handle resend
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    require_once '../includes/mailer.php';
    require_once '../includes/pdf_generator.php';
    $qid = (int)$_POST['quotation_id'];
    $q   = $db->query("SELECT * FROM final_quotations WHERE id=$qid")->fetch_assoc();
    if ($q) {
        $items = $db->query("SELECT * FROM final_quotation_items WHERE quotation_id=$qid")->fetch_all(MYSQLI_ASSOC);
        $ok = sendFinalQuotationToClient($q, $q['pdf_path']);
        header('Location: final_quotations.php?view='.$qid.'&sent='.($ok?1:0));
        exit;
    }
}

// Single quotation view
if ($viewId) {
    $q = $db->query("SELECT * FROM final_quotations WHERE id=$viewId")->fetch_assoc();
    if (!$q) { header('Location: final_quotations.php'); exit; }
    $items = $db->query("SELECT * FROM final_quotation_items WHERE quotation_id=$viewId ORDER BY id")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Quotation <?= htmlspecialchars($q['quotation_number']) ?> — <?= SITE_NAME ?> Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php adminSidebar('final_quotations'); ?>
<main class="admin-main">
<div class="admin-topbar">
  <h1><i class="fas fa-file-circle-check" style="margin-right:8px"></i><?= htmlspecialchars($q['quotation_number']) ?></h1>
  <div style="display:flex;gap:10px">
    <?php if (!empty($q['pdf_path'])): ?>
    <a href="../download.php?file=<?= urlencode(basename($q['pdf_path'])) ?>" class="action-btn"><i class="fas fa-download"></i> Download PDF</a>
    <?php endif; ?>
    <form method="POST" style="display:inline">
      <input type="hidden" name="quotation_id" value="<?= $q['id'] ?>">
      <button type="submit" name="resend" class="action-btn"><i class="fas fa-paper-plane"></i> Resend Email</button>
    </form>
    <a href="final_quotations.php" class="action-btn"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>
<?php if ($sent === 1): ?><div class="alert alert-success">Email sent to client successfully.</div><?php endif; ?>
<?php if ($sent === 0): ?><div class="alert alert-error">Email failed — check SMTP config.</div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 320px;gap:24px">
  <div>
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-header"><h3>Client Details</h3>
        <?php if ($q['is_premium']): ?><span class="badge" style="background:#f5c842;color:#000"><i class="fas fa-star"></i> Premium</span><?php endif; ?>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:4px 0">
        <div><div style="font-size:0.72rem;color:var(--gray-400);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Client Name</div><div style="font-weight:600"><?= htmlspecialchars($q['customer_name']) ?></div></div>
        <div><div style="font-size:0.72rem;color:var(--gray-400);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Company</div><div><?= htmlspecialchars($q['company_name'] ?: '—') ?></div></div>
        <div><div style="font-size:0.72rem;color:var(--gray-400);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Email</div><div><?= htmlspecialchars($q['email']) ?></div></div>
        <div><div style="font-size:0.72rem;color:var(--gray-400);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Phone</div><div><?= htmlspecialchars($q['contact_number'] ?: '—') ?></div></div>
        <?php if ($q['is_premium']): ?>
        <div><div style="font-size:0.72rem;color:var(--gray-400);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Address</div><div><?= htmlspecialchars($q['prem_address'] ?: '—') ?></div></div>
        <div><div style="font-size:0.72rem;color:var(--gray-400);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px">Branch</div><div><?= htmlspecialchars($q['prem_branch'] ?: '—') ?></div></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="admin-card">
      <div class="admin-card-header"><h3>Line Items</h3></div>
      <table class="data-table">
        <thead><tr><th>Description</th><th style="text-align:center">Qty</th><th style="text-align:right">Unit Price</th><th style="text-align:right">Subtotal</th></tr></thead>
        <tbody>
          <?php foreach ($items as $it): ?>
          <tr>
            <td><?= htmlspecialchars($it['description']) ?></td>
            <td style="text-align:center"><?= $it['quantity'] ?></td>
            <td style="text-align:right">₱<?= number_format($it['unit_price'], 2) ?></td>
            <td style="text-align:right;font-weight:700">₱<?= number_format($it['subtotal'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div>
    <div class="admin-card" style="margin-bottom:16px">
      <div class="admin-card-header"><h3>Summary</h3></div>
      <div style="padding:4px 0">
        <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:0.88rem"><span style="color:var(--gray-600)">Subtotal</span><span>₱<?= number_format($q['subtotal'], 2) ?></span></div>
        <?php if ($q['discount_percent'] > 0): ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:0.88rem"><span style="color:var(--gray-600)">Discount (<?= $q['discount_percent'] ?>%)</span><span style="color:#c00">-₱<?= number_format($q['discount_amount'], 2) ?></span></div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;padding:12px 0;border-top:2px solid var(--black);margin-top:8px"><span style="font-weight:700;font-size:0.85rem;text-transform:uppercase;letter-spacing:1px">Total</span><span style="font-size:1.3rem;font-weight:800">₱<?= number_format($q['total_amount'], 2) ?></span></div>
      </div>
    </div>
    <div class="admin-card">
      <div class="admin-card-header"><h3>Info</h3></div>
      <div style="font-size:0.85rem;padding:4px 0">
        <div style="padding:6px 0;border-bottom:1px solid var(--gray-200)"><span style="color:var(--gray-400)">Created:</span> <?= date('M d, Y g:i A', strtotime($q['created_at'])) ?></div>
        <?php if ($q['request_id']): ?>
        <div style="padding:6px 0;border-bottom:1px solid var(--gray-200)"><span style="color:var(--gray-400)">From Request:</span> <a href="quotations.php?view=<?= $q['request_id'] ?>" style="text-decoration:underline">View Request</a></div>
        <?php endif; ?>
        <?php if ($q['is_premium'] && $q['prem_prepared_by']): ?>
        <div style="padding:6px 0;border-bottom:1px solid var(--gray-200)"><span style="color:var(--gray-400)">Prepared By:</span> <?= htmlspecialchars($q['prem_prepared_by']) ?></div>
        <div style="padding:6px 0"><span style="color:var(--gray-400)">Checked By:</span> <?= htmlspecialchars($q['prem_checked_by']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</main>
</body>
</html>
<?php
    exit;
}

// List view
$quotations = $db->query("SELECT fq.*, qr.request_number FROM final_quotations fq LEFT JOIN quotation_requests qr ON fq.request_id=qr.id ORDER BY fq.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Final Quotations — <?= SITE_NAME ?> Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php adminSidebar('final_quotations'); ?>
<main class="admin-main">
<div class="admin-topbar">
  <h1>Final Quotations</h1>
</div>
<div class="admin-card">
  <table class="data-table">
    <thead>
      <tr><th>Quotation #</th><th>Client</th><th>Email</th><th>Total</th><th>Type</th><th>Date</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php if (empty($quotations)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-400)">No final quotations yet.</td></tr>
      <?php else: foreach ($quotations as $q): ?>
      <tr>
        <td><strong><?= htmlspecialchars($q['quotation_number']) ?></strong></td>
        <td><?= htmlspecialchars($q['customer_name']) ?></td>
        <td><?= htmlspecialchars($q['email']) ?></td>
        <td style="font-weight:700">₱<?= number_format($q['total_amount'], 2) ?></td>
        <td><?= $q['is_premium'] ? '<span class="badge" style="background:#f5c842;color:#000"><i class="fas fa-star"></i> Premium</span>' : '<span class="badge badge-responded">Standard</span>' ?></td>
        <td><?= date('M d, Y', strtotime($q['created_at'])) ?></td>
        <td style="display:flex;gap:6px">
          <a href="final_quotations.php?view=<?= $q['id'] ?>" class="action-btn">View</a>
          <?php if (!empty($q['pdf_path'])): ?>
          <a href="../download.php?file=<?= urlencode(basename($q['pdf_path'])) ?>" class="action-btn"><i class="fas fa-download"></i></a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
</main>
</body>
</html>
