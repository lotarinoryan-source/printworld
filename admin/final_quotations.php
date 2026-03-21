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

// Handle PDF regeneration (pulls latest client T&C + details from DB)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regen_pdf'])) {
    require_once '../includes/pdf_generator.php';
    $qid = (int)$_POST['quotation_id'];
    $q   = $db->query("SELECT * FROM final_quotations WHERE id=$qid")->fetch_assoc();
    if ($q) {
        $items = $db->query("SELECT * FROM final_quotation_items WHERE quotation_id=$qid")->fetch_all(MYSQLI_ASSOC);
        // Pull latest T&C from premium client if linked
        if ($q['is_premium'] && $q['premium_client_id']) {
            $pc = $db->query("SELECT * FROM premium_clients WHERE id=" . (int)$q['premium_client_id'])->fetch_assoc();
            if ($pc) {
                $q['terms_conditions'] = $pc['terms_conditions'] ?? '';
                $q['company_name']     = $pc['company_name'];
                $q['prem_address']     = $q['prem_address'] ?: $pc['address'];
            }
        }
        // Pull latest branch data if linked
        if (!empty($q['branch_id'])) {
            $br = $db->query("SELECT * FROM client_branches WHERE id=" . (int)$q['branch_id'])->fetch_assoc();
            if ($br) {
                $q['prem_branch'] = $br['branch_name'];
                if ($br['address']) $q['prem_address'] = $br['address'];
                if ($br['dear'])    $q['prem_dear']    = $br['dear'];
            }
        }
        $pdfPath = generateQuotationPDF($q, $items);
        $upd = $db->prepare('UPDATE final_quotations SET pdf_path=? WHERE id=?');
        $upd->bind_param('si', $pdfPath, $qid);
        $upd->execute();
        header('Location: final_quotations.php?view='.$qid.'&regen=1');
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
  <link rel="icon" type="image/png" href="../assets/pw.png">
  <title>Printworld - Final Quotations</title>
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
    <?php if ($q['is_premium'] && $q['premium_client_id']): ?>
    <form method="POST" style="display:inline">
      <input type="hidden" name="quotation_id" value="<?= $q['id'] ?>">
      <button type="submit" name="regen_pdf" class="action-btn" style="background:#1a1a1a;color:#fff" onclick="return confirm('Regenerate PDF with latest client details?')">
        <i class="fas fa-rotate"></i> Regenerate PDF
      </button>
    </form>
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
<?php if (isset($_GET['regen'])): ?><div class="alert alert-success">PDF regenerated with latest client details.</div><?php endif; ?>
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
        <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:0.88rem"><span style="color:var(--gray-600)">Original Price</span><span>₱<?= number_format($q['subtotal'], 2) ?></span></div>
        <?php if ($q['discount_amount'] > 0): ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:0.88rem"><span style="color:var(--gray-600)">Corporate Discount</span><span style="color:#c00">-₱<?= number_format($q['discount_amount'], 2) ?></span></div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;padding:12px 0;border-top:2px solid var(--black);margin-top:8px"><span style="font-weight:700;font-size:0.85rem;text-transform:uppercase;letter-spacing:1px">Total Price</span><span style="font-size:1.3rem;font-weight:800">₱<?= number_format($q['total_amount'], 2) ?></span></div>
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
  <link rel="icon" type="image/png" href="../assets/pw.png">
  <title>Printworld - Final Quotations</title>
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
