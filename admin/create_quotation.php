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
$requestId = (int)($_GET['request_id'] ?? 0);
$isPremium = isset($_GET['premium']) || isset($_POST['is_premium']);
$msg = $err = '';

// Load request
$req = null; $reqItems = [];
if ($requestId) {
    $s = $db->prepare("SELECT * FROM quotation_requests WHERE id=?");
    $s->bind_param('i', $requestId);
    $s->execute();
    $req = $s->get_result()->fetch_assoc();
    if ($req) {
        $si = $db->prepare("SELECT * FROM request_items WHERE request_id=?");
        $si->bind_param('i', $requestId);
        $si->execute();
        $reqItems = $si->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$premiumClients = getPremiumClients();
$prices = getAllPrices();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $isPremium     = (int)($_POST['is_premium'] ?? 0);
    $custName      = sanitizeInput($_POST['customer_name'] ?? '');
    $companyName   = sanitizeInput($_POST['company_name'] ?? '');
    $email         = sanitizeInput($_POST['email'] ?? '');
    $phone         = sanitizeInput($_POST['contact_number'] ?? '');
    $premAddress   = sanitizeInput($_POST['prem_address'] ?? '');
    $premBranch    = sanitizeInput($_POST['prem_branch'] ?? '');
    $premDear      = sanitizeInput($_POST['prem_dear'] ?? '');
    $preparedBy    = sanitizeInput($_POST['prem_prepared_by'] ?? 'Niño S. Del Rosario');
    $checkedBy     = sanitizeInput($_POST['prem_checked_by'] ?? 'Ryan Mark R. Lotarino');
    $location      = sanitizeInput($_POST['location'] ?? '');
    $discountAmt   = (float)($_POST['discount_amount'] ?? 0);
    $notes         = sanitizeInput($_POST['notes'] ?? '');
    $premClientId  = (int)($_POST['premium_client_id'] ?? 0);

    // Collect line items
    $lineItems = [];
    $descriptions = $_POST['item_desc'] ?? [];
    $quantities   = $_POST['item_qty']  ?? [];
    $unitPrices   = $_POST['item_price'] ?? [];

    foreach ($descriptions as $i => $desc) {
        $desc = sanitizeInput($desc);
        $qty  = (int)($quantities[$i] ?? 1);
        $up   = (float)($unitPrices[$i] ?? 0);
        if (empty($desc)) continue;
        $sub  = $qty * $up;
        $lineItems[] = ['description' => $desc, 'quantity' => $qty, 'unit_price' => $up, 'subtotal' => $sub, 'service_type' => 'Service'];
    }

    if (empty($lineItems)) { $err = 'Please add at least one line item.'; goto render; }
    if (empty($email))     { $err = 'Client email is required.'; goto render; }

    $subtotal    = array_sum(array_column($lineItems, 'subtotal'));
    $discountPct = 0; // fixed amount, no percentage
    $total       = $subtotal - $discountAmt;
    $qnum        = generateQuotationNumber();

    // Insert final quotation
    $ins = $db->prepare("INSERT INTO final_quotations
        (quotation_number, request_id, customer_name, company_name, email, contact_number,
         is_premium, premium_client_id, prem_address, prem_branch, prem_dear, prem_prepared_by, prem_checked_by,
         discount_percent, subtotal, discount_amount, total_amount, notes, location)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $ins->bind_param('siisssiisssssddddss',
        $qnum, $requestId, $custName, $companyName, $email, $phone,
        $isPremium, $premClientId, $premAddress, $premBranch, $premDear, $preparedBy, $checkedBy,
        $discountPct, $subtotal, $discountAmt, $total, $notes, $location
    );
    $ins->execute();
    $quotationId = $db->insert_id;

    // Insert line items
    $iins = $db->prepare("INSERT INTO final_quotation_items (quotation_id, service_type, description, quantity, unit_price, subtotal) VALUES (?,?,?,?,?,?)");
    foreach ($lineItems as $li) {
        $iins->bind_param('issidd', $quotationId, $li['service_type'], $li['description'], $li['quantity'], $li['unit_price'], $li['subtotal']);
        $iins->execute();
    }

    // Generate PDF
    $qData = [
        'quotation_number' => $qnum,
        'customer_name'    => $custName,
        'company_name'     => $companyName,
        'email'            => $email,
        'contact_number'   => $phone,
        'location'         => $location,
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

    // Save PDF path
    $upd = $db->prepare("UPDATE final_quotations SET pdf_path=? WHERE id=?");
    $upd->bind_param('si', $pdfPath, $quotationId);
    $upd->execute();

    // Update request status
    if ($requestId) {
        $db->query("UPDATE quotation_requests SET status='quoted' WHERE id=$requestId");
    }

    // Send to client
    $sent = sendFinalQuotationToClient($qData, $pdfPath);
    $sentMsg = $sent ? ' Email sent to client.' : ' (Email failed — check SMTP config.)';

    header('Location: final_quotations.php?view=' . $quotationId . '&sent=' . ($sent?1:0));
    exit;
}

render:
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
<?php adminSidebar('quotations'); ?>
<main class="admin-main">
<div class="admin-topbar">
  <h1><?= $isPremium ? '<i class="fas fa-star" style="color:#f5c842;margin-right:8px"></i>Premium Quotation' : 'Create Final Quotation' ?></h1>
  <a href="quotations.php<?= $requestId ? '?view='.$requestId : '' ?>" class="action-btn"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<form method="POST">
  <input type="hidden" name="generate" value="1">
  <input type="hidden" name="is_premium" value="<?= $isPremium ? 1 : 0 ?>">

  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">
    <div>
      <!-- Client Info -->
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header">
          <h3>Client Information</h3>
          <?php if ($isPremium): ?>
          <div style="display:flex;align-items:center;gap:8px">
            <label style="font-size:0.8rem;color:var(--gray-400)">Premium Client:</label>
            <select name="premium_client_id" class="form-control" style="width:180px" onchange="fillPremiumClient(this)">
              <option value="">— Select —</option>
              <?php foreach ($premiumClients as $pc): ?>
              <option value="<?= $pc['id'] ?>" data-name="<?= htmlspecialchars($pc['company_name']) ?>" data-addr="<?= htmlspecialchars($pc['address']) ?>"><?= htmlspecialchars($pc['company_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
        </div>
        <div class="form-section-body" style="padding:20px">
          <div class="form-row">
            <div class="form-group">
              <label>Client Name *</label>
              <input type="text" name="customer_name" class="form-control" value="<?= htmlspecialchars($req['customer_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label>Company Name</label>
              <input type="text" name="company_name" id="field-company" class="form-control" value="<?= htmlspecialchars($req['company_name'] ?? '') ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email *</label>
              <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($req['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label>Contact Number</label>
              <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($req['contact_number'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Location / Address</label>
            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($req['location'] ?? '') ?>" placeholder="Client's location">
          </div>
          <?php if ($isPremium): ?>
          <div class="form-row">
            <div class="form-group">
              <label>Address</label>
              <input type="text" name="prem_address" id="field-address" class="form-control" value="">
            </div>
            <div class="form-group">
              <label>Branch</label>
              <input type="text" name="prem_branch" class="form-control" placeholder="e.g. MR. DIY - Calinan, Davao City">
            </div>
          </div>
          <?php endif; ?>
          <div class="form-group">
            <label>Dear (Recipient Name)</label>
            <input type="text" name="prem_dear" class="form-control" placeholder="e.g. Ms. Eva" value="<?= htmlspecialchars($req['customer_name'] ?? '') ?>">
          </div>
        </div>
      </div>

      <!-- Line Items -->
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
                <th style="width:20%;text-align:right">Unit Price (₱)</th>
                <th style="width:18%;text-align:right">Subtotal</th>
                <th style="width:5%"></th>
              </tr>
            </thead>
            <tbody id="items-body">
              <?php if (!empty($reqItems)): ?>
                <?php foreach ($reqItems as $ri): ?>
                <tr class="item-row">
                  <td><input type="text" name="item_desc[]" class="form-control" value="<?= htmlspecialchars($ri['description']) ?>" required></td>
                  <td><input type="number" name="item_qty[]" class="form-control item-qty" value="<?= $ri['quantity'] ?>" min="1" style="text-align:center" oninput="calcRow(this)"></td>
                  <td><input type="number" name="item_price[]" class="form-control item-price" value="0" min="0" step="0.01" style="text-align:right" oninput="calcRow(this)"></td>
                  <td class="item-sub" style="text-align:right;padding:12px 14px;font-weight:700">0.00</td>
                  <td><button type="button" class="action-btn danger" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr class="item-row">
                  <td><input type="text" name="item_desc[]" class="form-control" placeholder="Description" required></td>
                  <td><input type="number" name="item_qty[]" class="form-control item-qty" value="1" min="1" style="text-align:center" oninput="calcRow(this)"></td>
                  <td><input type="number" name="item_price[]" class="form-control item-price" value="0" min="0" step="0.01" style="text-align:right" oninput="calcRow(this)"></td>
                  <td class="item-sub" style="text-align:right;padding:12px 14px;font-weight:700">0.00</td>
                  <td><button type="button" class="action-btn danger" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Notes -->
      <div class="admin-card">
        <div class="admin-card-header"><h3>Notes</h3></div>
        <div style="padding:20px">
          <textarea name="notes" class="form-control" rows="3" placeholder="Internal notes (not shown on PDF)..."></textarea>
        </div>
      </div>
    </div>

    <!-- RIGHT: TOTALS + SIGNATURE -->
    <div>
      <div class="admin-card" style="margin-bottom:16px">
        <div class="admin-card-header"><h3>Pricing Summary</h3></div>
        <div style="padding:20px">
          <div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:0.9rem">
            <span style="color:var(--gray-600)">Original Price</span>
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
            <span style="font-size:0.85rem;font-weight:700;letter-spacing:1px;text-transform:uppercase">Total Price</span>
            <span id="display-total" style="font-size:1.4rem;font-weight:800">₱0.00</span>
          </div>
        </div>
      </div>

      <div class="admin-card" style="margin-bottom:16px">
        <div class="admin-card-header"><h3>Signature Fields</h3></div>
        <div style="padding:20px">
          <div class="form-group">
            <label>Prepared By</label>
            <input type="text" name="prem_prepared_by" class="form-control" value="Niño S. Del Rosario">
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
            <i class="fas fa-file-pdf"></i> Generate & Send Quotation
          </button>
          <p style="font-size:0.75rem;text-align:center;color:var(--gray-400)">PDF will be generated and emailed to client.</p>
        </div>
      </div>
    </div>
  </div>
</form>
</main>

<script>
function addRow() {
  const tbody = document.getElementById('items-body');
  const tr = document.createElement('tr');
  tr.className = 'item-row';
  tr.innerHTML = `
    <td><input type="text" name="item_desc[]" class="form-control" placeholder="Description" required></td>
    <td><input type="number" name="item_qty[]" class="form-control item-qty" value="1" min="1" style="text-align:center" oninput="calcRow(this)"></td>
    <td><input type="number" name="item_price[]" class="form-control item-price" value="0" min="0" step="0.01" style="text-align:right" oninput="calcRow(this)"></td>
    <td class="item-sub" style="text-align:right;padding:12px 14px;font-weight:700">0.00</td>
    <td><button type="button" class="action-btn danger" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>`;
  tbody.appendChild(tr);
}

function removeRow(btn) {
  const rows = document.querySelectorAll('.item-row');
  if (rows.length <= 1) return;
  btn.closest('tr').remove();
  calcTotals();
}

function calcRow(input) {
  const row = input.closest('tr');
  const qty   = parseFloat(row.querySelector('.item-qty')?.value) || 0;
  const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
  const sub   = qty * price;
  row.querySelector('.item-sub').textContent = sub.toFixed(2);
  calcTotals();
}

function calcTotals() {
  let subtotal = 0;
  document.querySelectorAll('.item-row').forEach(row => {
    const qty   = parseFloat(row.querySelector('.item-qty')?.value) || 0;
    const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
    subtotal += qty * price;
  });
  const discAmt = parseFloat(document.getElementById('discount-amt')?.value) || 0;
  const total   = Math.max(0, subtotal - discAmt);
  document.getElementById('display-subtotal').textContent = '₱' + subtotal.toLocaleString('en-PH', {minimumFractionDigits:2});
  document.getElementById('display-discount').textContent = '-₱' + discAmt.toLocaleString('en-PH', {minimumFractionDigits:2});
  document.getElementById('display-total').textContent    = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits:2});
}

function fillPremiumClient(sel) {
  const opt = sel.options[sel.selectedIndex];
  const nameEl = document.getElementById('field-company');
  const addrEl = document.getElementById('field-address');
  if (nameEl) nameEl.value = opt.dataset.name || '';
  if (addrEl) addrEl.value = opt.dataset.addr || '';
}

calcTotals();
</script>
</body>
</html>
