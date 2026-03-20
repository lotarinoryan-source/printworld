<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/pdf_generator.php';
require_once '../includes/mailer.php';
require_once '_layout.php';
requireAdmin();

$db = db();
$premiumClients = getPremiumClients();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $isPremium    = (int)($_POST['is_premium'] ?? 0);
    $custName     = sanitizeInput($_POST['customer_name'] ?? '');
    $companyName  = sanitizeInput($_POST['company_name'] ?? '');
    $email        = sanitizeInput($_POST['email'] ?? '');
    $phone        = sanitizeInput($_POST['contact_number'] ?? '');
    $premAddress  = sanitizeInput($_POST['prem_address'] ?? '');
    $premBranch   = sanitizeInput($_POST['prem_branch'] ?? '');
    $premDear     = sanitizeInput($_POST['prem_dear'] ?? '');
    $preparedBy   = sanitizeInput($_POST['prem_prepared_by'] ?? 'Nino S. Del Rosario');
    $checkedBy    = sanitizeInput($_POST['prem_checked_by'] ?? 'Ryan Mark R. Lotarino');
    $discountAmt  = (float)($_POST['discount_amount'] ?? 0);
    $notes        = sanitizeInput($_POST['notes'] ?? '');
    $premClientId = (int)($_POST['premium_client_id'] ?? 0);

    $lineItems    = [];
    $descriptions = $_POST['item_desc']  ?? [];
    $quantities   = $_POST['item_qty']   ?? [];
    $unitPrices   = $_POST['item_price'] ?? [];

    foreach ($descriptions as $i => $desc) {
        $desc = sanitizeInput($desc);
        $qty  = (int)($quantities[$i] ?? 1);
        $up   = (float)($unitPrices[$i] ?? 0);
        if (empty($desc)) continue;
        $sub  = $qty * $up;
        $lineItems[] = [
            'description'  => $desc,
            'quantity'     => $qty,
            'unit_price'   => $up,
            'subtotal'     => $sub,
            'service_type' => 'Service',
        ];
    }

    if (empty($lineItems)) { $err = 'Please add at least one line item.'; goto render; }
    if (empty($email))     { $err = 'Client email is required.'; goto render; }

    $subtotal    = array_sum(array_column($lineItems, 'subtotal'));
    $total       = $subtotal - $discountAmt;
    $qnum = generateQuotationNumber();

    $ins = $db->prepare('INSERT INTO final_quotations
        (quotation_number, customer_name, company_name, email, contact_number,
         is_premium, premium_client_id, prem_address, prem_branch, prem_dear,
         prem_prepared_by, prem_checked_by, discount_percent, subtotal,
         discount_amount, total_amount, notes)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $ins->bind_param('sssssiisssssdddds',
        $qnum, $custName, $companyName, $email, $phone,
        $isPremium, $premClientId, $premAddress, $premBranch, $premDear,
        $preparedBy, $checkedBy, $discountPct, $subtotal, $discountAmt, $total, $notes
    );
    $ins->execute();
    $quotationId = $db->insert_id;

    $iins = $db->prepare('INSERT INTO final_quotation_items
        (quotation_id, service_type, description, quantity, unit_price, subtotal)
        VALUES (?,?,?,?,?,?)');
    foreach ($lineItems as $li) {
        $iins->bind_param('issidd',
            $quotationId, $li['service_type'], $li['description'],
            $li['quantity'], $li['unit_price'], $li['subtotal']
        );
        $iins->execute();
    }

    $qData = [
        'quotation_number' => $qnum,
        'customer_name'    => $custName,
        'company_name'     => $companyName,
        'email'            => $email,
        'contact_number'   => $phone,
        'is_premium'       => $isPremium,
        'prem_address'     => $premAddress,
        'prem_branch'      => $premBranch,
        'prem_dear'        => $premDear,
        'prem_prepared_by' => $preparedBy,
        'prem_checked_by'  => $checkedBy,
        'discount_percent' => 0,
        'discount_amount'  => $discountAmt,
        'subtotal'         => $subtotal,
        'total_amount'     => $total,
    ];
    $pdfPath = generateQuotationPDF($qData, $lineItems);

    $upd = $db->prepare('UPDATE final_quotations SET pdf_path=? WHERE id=?');
    $upd->bind_param('si', $pdfPath, $quotationId);
    $upd->execute();

    $sent = sendFinalQuotationToClient($qData, $pdfPath);
    header('Location: final_quotations.php?view=' . $quotationId . '&sent=' . ($sent ? 1 : 0));
    exit;
}
render:?>
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
<?php adminSidebar('manual_quotation'); ?>
<main class="admin-main">
<div class="admin-topbar">
  <h1><i class="fas fa-pen-to-square" style="margin-right:8px"></i>Manual Quotation</h1>
  <a href="final_quotations.php" class="action-btn"><i class="fas fa-arrow-left"></i> Back</a>
</div>
<?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="admin-card" style="margin-bottom:20px;padding:16px 20px">
  <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
    <span style="font-weight:700;font-size:0.9rem">Quotation Type:</span>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
      <input type="radio" name="qtype" value="standard" checked onchange="setType(0)"> Standard
    </label>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
      <input type="radio" name="qtype" value="premium" onchange="setType(1)">
      <i class="fas fa-star" style="color:#f5c842"></i> Premium Client
    </label>
    <div id="premium-selector" style="display:none;align-items:center;gap:8px">
      <label style="font-size:0.8rem;color:var(--gray-400)">Select Client:</label>
      <select id="premium-client-select" class="form-control" style="width:200px" onchange="fillPremiumClient(this)">
        <option value="">— Select —</option>
        <?php foreach ($premiumClients as $pc): ?>
        <option value="<?= $pc['id'] ?>"
          data-name="<?= htmlspecialchars($pc['company_name']) ?>"
          data-addr="<?= htmlspecialchars($pc['address']) ?>">
          <?= htmlspecialchars($pc['company_name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<form method="POST" id="quotation-form">
  <input type="hidden" name="generate" value="1">
  <input type="hidden" name="is_premium" id="is-premium-val" value="0">
  <input type="hidden" name="premium_client_id" id="premium-client-id-val" value="0">

  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">
    <div>
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header"><h3>Client Information</h3></div>
        <div style="padding:20px">
          <div class="form-row">
            <div class="form-group">
              <label>Client Name *</label>
              <input type="text" name="customer_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Company Name</label>
              <input type="text" name="company_name" id="field-company" class="form-control">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email *</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Contact Number</label>
              <input type="text" name="contact_number" class="form-control">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Address</label>
              <input type="text" name="prem_address" id="field-address" class="form-control">
            </div>
            <div class="form-group">
              <label>Branch</label>
              <input type="text" name="prem_branch" class="form-control" placeholder="e.g. Branch name / location">
            </div>
          </div>
          <div class="form-group">
            <label>Dear (Recipient Name)</label>
            <input type="text" name="prem_dear" class="form-control" placeholder="e.g. Ms. Eva">
          </div>
        </div>
      </div>

      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header">
          <h3>Quotation Items</h3>
          <button type="button" class="action-btn" onclick="addRow()"><i class="fas fa-plus"></i> Add Row</button>
        </div>
        <div style="padding:0 0 16px">
          <table class="data-table" id="items-table">
            <thead>
              <tr>
                <th style="width:45%">Description</th>
                <th style="width:12%;text-align:center">Qty</th>
                <th style="width:20%;text-align:right">Unit Price (P)</th>
                <th style="width:18%;text-align:right">Subtotal</th>
                <th style="width:5%"></th>
              </tr>
            </thead>
            <tbody id="items-body">
              <tr class="item-row">
                <td><input type="text" name="item_desc[]" class="form-control" placeholder="Description" required></td>
                <td><input type="number" name="item_qty[]" class="form-control item-qty" value="1" min="1" style="text-align:center" oninput="calcRow(this)"></td>
                <td><input type="number" name="item_price[]" class="form-control item-price" value="0" min="0" step="0.01" style="text-align:right" oninput="calcRow(this)"></td>
                <td class="item-sub" style="text-align:right;padding:12px 14px;font-weight:700">0.00</td>
                <td><button type="button" class="action-btn danger" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-header"><h3>Notes</h3></div>
        <div style="padding:20px">
          <textarea name="notes" class="form-control" rows="3" placeholder="Internal notes (not shown on PDF)..."></textarea>
        </div>
      </div>
    </div>

    <div>
      <div class="admin-card" style="margin-bottom:16px">
        <div class="admin-card-header"><h3>Pricing Summary</h3></div>
        <div style="padding:20px">
          <div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:0.9rem">
            <span style="color:var(--gray-600)">Subtotal</span>
            <span id="display-subtotal" style="font-weight:700">₱0.00</span>
          </div>
          <div class="form-group">
            <label>Corporate Discount (₱)</label>
            <input type="number" name="discount_amount" id="discount-amt" class="form-control" value="0" min="0" step="0.01" oninput="calcTotals()">
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.85rem;color:var(--gray-600)">
            <span>Corporate Discount</span>
            <span id="display-discount" style="color:#c00">-₱0.00</span>
          </div>
          <div style="border-top:2px solid var(--black);padding-top:12px;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:0.85rem;font-weight:700;letter-spacing:1px;text-transform:uppercase">Total</span>
            <span id="display-total" style="font-size:1.4rem;font-weight:800">₱0.00</span>
          </div>
        </div>
      </div>

      <div class="admin-card" style="margin-bottom:16px">
        <div class="admin-card-header"><h3>Signature Fields</h3></div>
        <div style="padding:20px">
          <div class="form-group">
            <label>Prepared By</label>
            <input type="text" name="prem_prepared_by" class="form-control" value="Nino S. Del Rosario">
          </div>
          <div class="form-group">
            <label>Checked By</label>
            <input type="text" name="prem_checked_by" class="form-control" value="Ryan Mark R. Lotarino">
          </div>
        </div>
      </div>

      <div class="admin-card">
        <div style="padding:20px">
          <button type="submit" class="btn btn-dark" style="width:100%;justify-content:center;margin-bottom:10px">
            <i class="fas fa-file-pdf"></i> Generate &amp; Send Quotation
          </button>
          <p style="font-size:0.75rem;text-align:center;color:var(--gray-400)">PDF will be generated and emailed to client.</p>
        </div>
      </div>
    </div>
  </div>
</form>
</main>
<script>
function setType(isPremium) {
  document.getElementById('is-premium-val').value = isPremium;
  var sel = document.getElementById('premium-selector');
  sel.style.display = isPremium ? 'flex' : 'none';
  if (!isPremium) document.getElementById('premium-client-id-val').value = 0;
}
function fillPremiumClient(sel) {
  var opt = sel.options[sel.selectedIndex];
  document.getElementById('field-company').value = opt.dataset.name || '';
  document.getElementById('field-address').value = opt.dataset.addr || '';
  document.getElementById('premium-client-id-val').value = sel.value || 0;
}
function addRow() {
  var tbody = document.getElementById('items-body');
  var tr = document.createElement('tr');
  tr.className = 'item-row';
  tr.innerHTML = '<td><input type="text" name="item_desc[]" class="form-control" placeholder="Description" required></td>'
    + '<td><input type="number" name="item_qty[]" class="form-control item-qty" value="1" min="1" style="text-align:center" oninput="calcRow(this)"></td>'
    + '<td><input type="number" name="item_price[]" class="form-control item-price" value="0" min="0" step="0.01" style="text-align:right" oninput="calcRow(this)"></td>'
    + '<td class="item-sub" style="text-align:right;padding:12px 14px;font-weight:700">0.00</td>'
    + '<td><button type="button" class="action-btn danger" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>';
  tbody.appendChild(tr);
}
function removeRow(btn) {
  if (document.querySelectorAll('.item-row').length <= 1) return;
  btn.closest('tr').remove();
  calcTotals();
}
function calcRow(input) {
  var row = input.closest('tr');
  var qty   = parseFloat(row.querySelector('.item-qty').value) || 0;
  var price = parseFloat(row.querySelector('.item-price').value) || 0;
  row.querySelector('.item-sub').textContent = (qty * price).toFixed(2);
  calcTotals();
}
function calcTotals() {
  var subtotal = 0;
  document.querySelectorAll('.item-row').forEach(function(row) {
    var qty   = parseFloat(row.querySelector('.item-qty').value) || 0;
    var price = parseFloat(row.querySelector('.item-price').value) || 0;
    subtotal += qty * price;
  });
  var discAmt = parseFloat(document.getElementById('discount-amt').value) || 0;
  var total   = subtotal - discAmt;
  document.getElementById('display-subtotal').textContent = '₱' + subtotal.toLocaleString('en-PH', {minimumFractionDigits:2});
  document.getElementById('display-discount').textContent = '-₱' + discAmt.toLocaleString('en-PH', {minimumFractionDigits:2});
  document.getElementById('display-total').textContent    = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits:2});
}
calcTotals();
</script>
</body>
</html>