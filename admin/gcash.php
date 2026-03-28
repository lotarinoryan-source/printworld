<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '_layout.php';
requireAdmin();

$db = db();

$db->query("CREATE TABLE IF NOT EXISTS gcash_cash_in (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(12,2) NOT NULL,
    charge DECIMAL(12,2) NOT NULL,
    total  DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$db->query("CREATE TABLE IF NOT EXISTS gcash_cash_out (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(12,2) NOT NULL,
    charge DECIMAL(12,2) NOT NULL,
    total  DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$gcashUnlocked = !empty($_SESSION['gcash_unlocked']);

// Load autolock setting
$autolockRow = $db->query("SELECT `value` FROM site_settings WHERE `key`='gcash_autolock_enabled' LIMIT 1");
$gcashAutolockEnabled = ($autolockRow && ($ar = $autolockRow->fetch_assoc())) ? $ar['value'] : '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gcash_pw'])) {
    // Verify against the GCash page password (from DB or config)
    $gcashLockPw = GCASH_PASSWORD;
    $pwRow = $db->query("SELECT `value` FROM site_settings WHERE `key`='gcash_lock_password' LIMIT 1");
    if ($pwRow && $r = $pwRow->fetch_assoc()) {
        $gcashLockPw = $r['value'] ?: GCASH_PASSWORD;
    }
    if ($_POST['gcash_pw'] === $gcashLockPw) {
        $_SESSION['gcash_unlocked'] = true;
        $gcashUnlocked = true;
    } else {
        $pwError = 'Incorrect password. Please try again.';
    }
}
if (isset($_GET['lock'])) {
    unset($_SESSION['gcash_unlocked']);
    header('Location: gcash.php');
    exit;
}

function gcashSummary($db, string $table): array {
    return $db->query("SELECT
        COALESCE(SUM(amount),0) AS total_amount,
        COALESCE(SUM(charge),0) AS total_charge,
        COALESCE(SUM(total),0)  AS total_total
        FROM `$table`")->fetch_assoc();
}

// Daily summary: merge both tables grouped by date
function gcashDailySummary($db): array {
    $sql = "SELECT
        DATE(created_at) AS day,
        SUM(CASE WHEN type='in'  THEN amount ELSE 0 END) AS cash_in_amount,
        SUM(CASE WHEN type='out' THEN amount ELSE 0 END) AS cash_out_amount,
        SUM(charge) AS total_charge,
        SUM(amount) AS total_amount,
        COUNT(*)    AS tx_count
    FROM (
        SELECT amount, charge, total, created_at, 'in'  AS type FROM gcash_cash_in
        UNION ALL
        SELECT amount, charge, total, created_at, 'out' AS type FROM gcash_cash_out
    ) AS combined
    GROUP BY DATE(created_at)
    ORDER BY day DESC";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Chart data: last 30 days
function gcashChartData($db): array {
    $sql = "SELECT
        DATE(created_at) AS day,
        SUM(amount) AS total_amount,
        SUM(charge) AS total_charge,
        COUNT(*)    AS tx_count
    FROM (
        SELECT amount, charge, created_at FROM gcash_cash_in
        UNION ALL
        SELECT amount, charge, created_at FROM gcash_cash_out
    ) AS combined
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

$inSum    = gcashSummary($db, 'gcash_cash_in');
$outSum   = gcashSummary($db, 'gcash_cash_out');
$inRows   = $db->query("SELECT * FROM gcash_cash_in  ORDER BY created_at DESC LIMIT 200")->fetch_all(MYSQLI_ASSOC);
$outRows  = $db->query("SELECT * FROM gcash_cash_out ORDER BY created_at DESC LIMIT 200")->fetch_all(MYSQLI_ASSOC);
$dailyRows  = $gcashUnlocked ? gcashDailySummary($db)  : [];
$chartData  = $gcashUnlocked ? gcashChartData($db)     : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/pw.png">
  <title>Printworld - GCash Transactions</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    .gc-lock-page{display:flex;align-items:center;justify-content:center;min-height:70vh}
    .gc-lock-card{background:#fff;border:1px solid #eee;border-radius:8px;padding:40px 36px;width:100%;max-width:380px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.07)}
    .gc-lock-icon{width:64px;height:64px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px}
    .gc-lock-icon i{font-size:1.6rem;color:#16a34a}
    .gc-tabs{display:flex;border-bottom:2px solid #e5e5e5;margin-bottom:24px;gap:0;overflow-x:auto}
    .gc-tab{flex:1;padding:12px 10px;text-align:center;font-size:.82rem;font-weight:700;letter-spacing:.5px;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;color:#888;transition:color .15s;white-space:nowrap}
    .gc-tab.active{color:#111;border-bottom-color:#111}
    .gc-pane{display:none}.gc-pane.active{display:block}
    .gc-summary-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px}
    .gc-sum-card{background:#fff;border:1px solid #eee;border-radius:6px;padding:18px 16px;text-align:center}
    .gc-sum-card .val{font-size:1.3rem;font-weight:800;color:#111}
    .gc-sum-card .lbl{font-size:.72rem;color:#888;text-transform:uppercase;letter-spacing:1px;margin-top:4px}
    .gc-sum-card.green .val{color:#16a34a}
    .gc-sum-card.blue  .val{color:#2563eb}
    .gc-overall{background:#111;border-radius:8px;padding:24px 28px;margin-bottom:28px;display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
    .gc-overall .ov-val{font-size:1.2rem;font-weight:800;color:#fff}
    .gc-overall .ov-lbl{font-size:.68rem;color:#aaa;text-transform:uppercase;letter-spacing:1px;margin-top:4px}
    .gc-add-form{background:#fff;border:1px solid #eee;border-radius:6px;padding:20px 24px;margin-bottom:24px;display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap}
    .gc-add-form .form-group{margin:0;flex:1;min-width:160px}
    .gc-preview{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:5px;padding:10px 16px;font-size:.85rem;color:#166534;display:none;margin-top:8px}
    .pw-toast-el{position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 22px;border-radius:4px;font-size:.85rem;color:#fff;opacity:0;transition:opacity .25s}
    .pw-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:3000;align-items:center;justify-content:center;padding:20px}
    .pw-overlay.open{display:flex}
    .pw-box{background:#fff;padding:32px;width:100%;max-width:420px;border-radius:8px;text-align:center}
    .day-best{background:#fefce8;border-left:3px solid #f5c842}
    .chart-wrap{background:#fff;border:1px solid #eee;border-radius:6px;padding:20px 24px;margin-bottom:28px}
    .chart-wrap canvas{max-height:320px}
    .chart-legend{display:flex;gap:16px;flex-wrap:wrap;margin-top:12px;font-size:.78rem;color:#666}
    .chart-legend span{display:flex;align-items:center;gap:6px}
    .chart-legend .dot{width:12px;height:12px;border-radius:2px;flex-shrink:0}
    @media print{
      .admin-sidebar,.admin-mobile-toggle,.admin-sidebar-overlay,.gc-tabs,.gc-add-form,.admin-topbar,.action-btn,.pw-overlay{display:none!important}
      .admin-main{margin:0!important;padding:0!important}
      .gc-pane{display:block!important}
      body{background:#fff}
    }
    @media(max-width:768px){
      .gc-summary-grid{grid-template-columns:1fr 1fr}
      .gc-overall{grid-template-columns:1fr 1fr}
      .gc-add-form{flex-direction:column}
      .gc-add-form .form-group{min-width:100%}
    }
    @media(max-width:480px){
      .gc-summary-grid{grid-template-columns:1fr}
      .gc-overall{grid-template-columns:1fr 1fr;padding:16px}
    }
  </style>
</head>
<body class="admin-body">
<?php adminSidebar('gcash'); ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1><i class="fas fa-mobile-screen-button" style="margin-right:8px;color:#16a34a"></i>GCash Transactions</h1>
    <?php if ($gcashUnlocked): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <button class="btn btn-dark" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
      <button class="action-btn" onclick="exportPDF()"><i class="fas fa-file-pdf"></i> Export PDF</button>
      <button class="action-btn" onclick="switchGcTab('gcnotes', document.querySelector('.gc-tab:last-child'))"><i class="fas fa-note-sticky" style="color:#d97706"></i> Notes</button>
      <a href="gcash.php?lock=1" class="action-btn"><i class="fas fa-lock"></i> Lock</a>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.8rem;color:#555;margin-left:4px" title="Auto-lock on page exit">
        <span style="white-space:nowrap"><i class="fas fa-shield-halved" style="color:#7c3aed;margin-right:3px"></i>Auto-Lock</span>
        <span style="position:relative;display:inline-block;width:40px;height:22px;flex-shrink:0">
          <input type="checkbox" id="autolock-toggle" <?= $gcashAutolockEnabled === '1' ? 'checked' : '' ?>
            onchange="saveAutolock(this.checked)"
            style="opacity:0;width:0;height:0;position:absolute">
          <span id="autolock-slider" style="position:absolute;inset:0;background:<?= $gcashAutolockEnabled === '1' ? '#7c3aed' : '#ccc' ?>;border-radius:22px;transition:.3s;cursor:pointer">
            <span style="position:absolute;width:16px;height:16px;background:#fff;border-radius:50%;top:3px;transition:.3s;left:<?= $gcashAutolockEnabled === '1' ? '21px' : '3px' ?>"></span>
          </span>
        </span>
      </label>
    </div>
    <?php endif; ?>
  </div>

<?php if (!$gcashUnlocked): ?>
<div class="gc-lock-page">
  <div class="gc-lock-card">
    <div class="gc-lock-icon"><i class="fas fa-lock"></i></div>
    <h2 style="margin-bottom:6px;font-size:1.2rem">GCash Access</h2>
    <p style="color:#888;font-size:.88rem;margin-bottom:24px">Enter the GCash password to continue.</p>
    <?php if (!empty($pwError)): ?>
    <div class="alert alert-error" style="margin-bottom:16px"><?= htmlspecialchars($pwError) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group" style="text-align:left">
        <label>Password</label>
        <input type="password" name="gcash_pw" class="form-control" placeholder="••••••••" autofocus required>
      </div>
      <button type="submit" class="btn btn-dark" style="width:100%;justify-content:center;margin-top:8px">
        <i class="fas fa-unlock"></i> Unlock
      </button>
      <a href="dashboard.php" style="display:block;text-align:center;margin-top:12px;font-size:.82rem;color:#aaa;text-decoration:none">
        <i class="fas fa-arrow-left" style="margin-right:4px"></i> Back to Dashboard
      </a>
    </form>
  </div>
</div>

<?php else:
  $combAmount = $inSum['total_amount'] + $outSum['total_amount'];
  $combCharge = $inSum['total_charge'] + $outSum['total_charge'];
  $combTotal  = $inSum['total_total']  + $outSum['total_total'];
  $txCount    = count($inRows) + count($outRows);

  // Find busiest day for highlight
  $busiestDay = '';
  $busiestCount = 0;
  foreach ($dailyRows as $dr) {
      if ($dr['tx_count'] > $busiestCount) { $busiestCount = $dr['tx_count']; $busiestDay = $dr['day']; }
  }
?>

<!-- GRAPH — shown when Graph tab is active -->

<!-- OVERALL SUMMARY -->
<div class="gc-overall">
  <div class="ov-item">
    <div class="ov-val">₱<?= number_format($combAmount, 2) ?></div>
    <div class="ov-lbl">Total Capital</div>
  </div>
  <div class="ov-item">
    <div class="ov-val" style="color:#f5c842">₱<?= number_format($combCharge, 2) ?></div>
    <div class="ov-lbl">Total Profit</div>
  </div>
  <div class="ov-item">
    <div class="ov-val">₱<?= number_format($combTotal, 2) ?></div>
    <div class="ov-lbl">Combined Total</div>
  </div>
  <div class="ov-item">
    <div class="ov-val" style="color:#86efac"><?= $txCount ?></div>
    <div class="ov-lbl">Transactions</div>
  </div>
</div>

<!-- TABS: Cash In | Cash Out | Daily Summary | Graph -->
<div class="gc-tabs">
  <div class="gc-tab active"  onclick="switchGcTab('in',this)"><i class="fas fa-arrow-down" style="color:#16a34a;margin-right:5px"></i>Cash In</div>
  <div class="gc-tab" onclick="switchGcTab('out',this)"><i class="fas fa-arrow-up" style="color:#dc2626;margin-right:5px"></i>Cash Out</div>
  <div class="gc-tab" onclick="switchGcTab('daily',this)"><i class="fas fa-calendar-days" style="color:#2563eb;margin-right:5px"></i>Daily Summary</div>
  <div class="gc-tab" onclick="switchGcTab('graph',this)"><i class="fas fa-chart-line" style="color:#7c3aed;margin-right:5px"></i>Graph</div>
</div>

<!-- CASH IN PANE -->
<div class="gc-pane active" id="pane-in">
  <div class="gc-summary-grid">
    <div class="gc-sum-card"><div class="val">₱<?= number_format($inSum['total_amount'],2) ?></div><div class="lbl">Total Amount</div></div>
    <div class="gc-sum-card green"><div class="val">₱<?= number_format($inSum['total_charge'],2) ?></div><div class="lbl">Total Charges (Profit)</div></div>
    <div class="gc-sum-card blue"><div class="val">₱<?= number_format($inSum['total_total'],2) ?></div><div class="lbl">Overall Total</div></div>
  </div>
  <div class="gc-add-form">
    <div class="form-group">
      <label>Amount (₱) *</label>
      <input type="number" id="in-amount" class="form-control" min="1" step="0.01" placeholder="e.g. 500" oninput="previewCharge('in')" onkeydown="if(event.key==='Enter'){event.preventDefault();submitTx('in')}">
    </div>
    <div id="in-preview" class="gc-preview" style="flex-basis:100%"></div>
    <button class="btn btn-dark" onclick="submitTx('in')"><i class="fas fa-plus"></i> Add Cash In</button>
  </div>
  <div class="admin-card" style="padding:0">
    <div class="admin-card-header" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between">
      <h3><i class="fas fa-arrow-down" style="color:#16a34a;margin-right:6px"></i>Cash In Records</h3>
      <button class="action-btn" onclick="exportTypePDF('in')"><i class="fas fa-file-pdf"></i> Export PDF</button>
    </div>
    <div style="overflow-x:auto">
      <table class="data-table" id="tbl-in" style="min-width:520px">
        <thead><tr><th>#</th><th>Date & Time</th><th>Amount</th><th>Charge</th><th>Total</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($inRows as $i => $r): ?>
          <tr id="gc-in-<?= $r['id'] ?>">
            <td><?= $i+1 ?></td>
            <td style="white-space:nowrap"><?= date('M d, Y h:i A', strtotime($r['created_at'])) ?></td>
            <td>₱<?= number_format($r['amount'],2) ?></td>
            <td style="color:#16a34a;font-weight:600">₱<?= number_format($r['charge'],2) ?></td>
            <td style="font-weight:700">₱<?= number_format($r['total'],2) ?></td>
            <td><button class="action-btn danger" onclick="deleteTx('in',<?= $r['id'] ?>,'₱<?= number_format($r['amount'],2) ?>')"><i class="fas fa-trash"></i></button></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($inRows)): ?>
          <tr id="gc-in-empty"><td colspan="6" style="text-align:center;color:#aaa;padding:32px">No cash in records yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- CASH OUT PANE -->
<div class="gc-pane" id="pane-out">
  <div class="gc-summary-grid">
    <div class="gc-sum-card"><div class="val">₱<?= number_format($outSum['total_amount'],2) ?></div><div class="lbl">Total Amount</div></div>
    <div class="gc-sum-card green"><div class="val">₱<?= number_format($outSum['total_charge'],2) ?></div><div class="lbl">Total Charges (Profit)</div></div>
    <div class="gc-sum-card blue"><div class="val">₱<?= number_format($outSum['total_total'],2) ?></div><div class="lbl">Overall Total</div></div>
  </div>
  <div class="gc-add-form">
    <div class="form-group">
      <label>Amount (₱) *</label>
      <input type="number" id="out-amount" class="form-control" min="1" step="0.01" placeholder="e.g. 1000" oninput="previewCharge('out')" onkeydown="if(event.key==='Enter'){event.preventDefault();submitTx('out')}">
    </div>
    <div id="out-preview" class="gc-preview" style="flex-basis:100%"></div>
    <button class="btn btn-dark" onclick="submitTx('out')"><i class="fas fa-plus"></i> Add Cash Out</button>
  </div>
  <div class="admin-card" style="padding:0">
    <div class="admin-card-header" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between">
      <h3><i class="fas fa-arrow-up" style="color:#dc2626;margin-right:6px"></i>Cash Out Records</h3>
      <button class="action-btn" onclick="exportTypePDF('out')"><i class="fas fa-file-pdf"></i> Export PDF</button>
    </div>
    <div style="overflow-x:auto">
      <table class="data-table" id="tbl-out" style="min-width:520px">
        <thead><tr><th>#</th><th>Date & Time</th><th>Amount</th><th>Charge</th><th>Total</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($outRows as $i => $r): ?>
          <tr id="gc-out-<?= $r['id'] ?>">
            <td><?= $i+1 ?></td>
            <td style="white-space:nowrap"><?= date('M d, Y h:i A', strtotime($r['created_at'])) ?></td>
            <td>₱<?= number_format($r['amount'],2) ?></td>
            <td style="color:#16a34a;font-weight:600">₱<?= number_format($r['charge'],2) ?></td>
            <td style="font-weight:700">₱<?= number_format($r['total'],2) ?></td>
            <td><button class="action-btn danger" onclick="deleteTx('out',<?= $r['id'] ?>,'₱<?= number_format($r['amount'],2) ?>')"><i class="fas fa-trash"></i></button></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($outRows)): ?>
          <tr id="gc-out-empty"><td colspan="6" style="text-align:center;color:#aaa;padding:32px">No cash out records yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- DAILY SUMMARY PANE -->
<div class="gc-pane" id="pane-daily">
  <div class="admin-card" style="padding:0">
    <div class="admin-card-header" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between">
      <h3><i class="fas fa-calendar-days" style="color:#2563eb;margin-right:6px"></i>Daily Transaction Summary</h3>
      <button class="action-btn" onclick="exportDailyPDF()"><i class="fas fa-file-pdf"></i> Export PDF</button>
    </div>
    <?php if (!empty($busiestDay)): ?>
    <div style="padding:10px 20px;background:#fefce8;border-bottom:1px solid #fde68a;font-size:.83rem;color:#92400e">
      <i class="fas fa-fire" style="color:#f59e0b;margin-right:6px"></i>
      Busiest day: <strong><?= date('F d, Y', strtotime($busiestDay)) ?></strong> with <strong><?= $busiestCount ?></strong> transaction(s)
    </div>
    <?php endif; ?>
    <div style="overflow-x:auto">
      <table class="data-table" style="min-width:640px">
        <thead>
          <tr>
            <th>Date</th><th>Cash In</th><th>Cash Out</th>
            <th>Total Charges</th><th>Total Capital</th><th>Transactions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($dailyRows)): ?>
          <tr><td colspan="6" style="text-align:center;color:#aaa;padding:32px">No transactions yet.</td></tr>
          <?php else: foreach ($dailyRows as $dr):
            $isBusiest = $dr['day'] === $busiestDay;
          ?>
          <tr <?= $isBusiest ? 'class="day-best"' : '' ?>>
            <td style="font-weight:600;white-space:nowrap;cursor:pointer;color:#2563eb;text-decoration:underline" onclick="openDayDetail('<?= $dr['day'] ?>')">
              <?= date('M d, Y', strtotime($dr['day'])) ?>
              <?php if ($isBusiest): ?><span style="font-size:.7rem;background:#f5c842;color:#000;border-radius:3px;padding:1px 6px;margin-left:6px">🔥 Busiest</span><?php endif; ?>
            </td>
            <td style="color:#16a34a;font-weight:600">₱<?= number_format($dr['cash_in_amount'],2) ?></td>
            <td style="color:#dc2626;font-weight:600">₱<?= number_format($dr['cash_out_amount'],2) ?></td>
            <td style="color:#16a34a">₱<?= number_format($dr['total_charge'],2) ?></td>
            <td>₱<?= number_format($dr['total_amount'],2) ?></td>
            <td style="text-align:center"><span style="background:#e0e7ff;color:#3730a3;border-radius:4px;padding:2px 10px;font-weight:700;font-size:.82rem"><?= $dr['tx_count'] ?></span></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- GRAPH PANE -->
<div class="gc-pane" id="pane-graph">
  <div class="chart-wrap">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
      <h3 style="font-size:.9rem;letter-spacing:1px;text-transform:uppercase;margin:0">
        <i class="fas fa-chart-line" style="color:#7c3aed;margin-right:6px"></i>Daily Transactions — Last 30 Days
      </h3>
      <div style="display:flex;gap:8px">
        <button class="action-btn gc-chart-toggle active" data-mode="profit" onclick="toggleChart('profit',this)">By Profit</button>
        <button class="action-btn gc-chart-toggle" data-mode="count" onclick="toggleChart('count',this)">By Count</button>
      </div>
    </div>
    <canvas id="gcash-chart"></canvas>
    <div class="chart-legend">
      <span><span class="dot" style="background:#16a34a"></span>Cash In</span>
      <span><span class="dot" style="background:#dc2626"></span>Cash Out</span>
    </div>
  </div>
  <?php if (!empty($busiestDay)): ?>
  <div class="admin-card" style="padding:14px 20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-top:16px">
    <div style="width:40px;height:40px;background:#fef9c3;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas fa-trophy" style="color:#f59e0b;font-size:1.1rem"></i>
    </div>
    <div>
      <div style="font-size:.7rem;color:#888;text-transform:uppercase;letter-spacing:1px">Busiest Day</div>
      <div style="font-size:1rem;font-weight:800"><?= date('F d, Y', strtotime($busiestDay)) ?></div>
      <div style="font-size:.82rem;color:#666"><?= $busiestCount ?> transaction(s) recorded</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php
// Create gcash_notes table
$db->query("CREATE TABLE IF NOT EXISTS gcash_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$gcashNotes = $db->query("SELECT * FROM gcash_notes ORDER BY created_at DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
?>

<!-- NOTES PANE -->
<div class="gc-pane" id="pane-gcnotes">
  <div class="gc-add-form" style="align-items:flex-start;flex-direction:column;gap:12px">
    <label style="font-weight:700;font-size:.85rem;text-transform:uppercase;letter-spacing:1px"><i class="fas fa-note-sticky" style="color:#d97706;margin-right:6px"></i>Add a Note</label>
    <textarea id="gcnote-input" class="form-control" rows="3" placeholder="Write a note or remark for today's transactions..." style="width:100%;resize:vertical"></textarea>
    <button class="btn btn-dark" onclick="saveGcNote()"><i class="fas fa-plus"></i> Add Note</button>
  </div>
  <div class="admin-card" style="padding:0">
    <div class="admin-card-header" style="padding:16px 20px">
      <h3><i class="fas fa-note-sticky" style="color:#d97706;margin-right:6px"></i>Notes</h3>
    </div>
    <div id="gcnotes-list" style="padding:0 20px 20px">
      <?php if (empty($gcashNotes)): ?>
      <p id="gcnotes-empty" style="color:#aaa;text-align:center;padding:32px 0;font-size:.9rem">No notes yet. Add one above.</p>
      <?php else: ?>
      <?php foreach ($gcashNotes as $n): ?>
      <div class="gcnote-item" id="gcnote-<?= $n['id'] ?>" style="border-bottom:1px solid #f0f0f0;padding:14px 0;display:flex;gap:12px;align-items:flex-start">
        <div style="width:36px;height:36px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="fas fa-note-sticky" style="color:#d97706;font-size:.85rem"></i>
        </div>
        <div style="flex:1">
          <div style="font-size:.9rem;line-height:1.6;white-space:pre-wrap"><?= htmlspecialchars($n['note']) ?></div>
          <div style="font-size:.75rem;color:#aaa;margin-top:4px"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></div>
        </div>
        <button class="action-btn danger" onclick="deleteGcNote(<?= $n['id'] ?>)" style="flex-shrink:0"><i class="fas fa-trash"></i></button>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php endif; ?>
</main>

<!-- Day Drill-Down Modal -->
<div id="day-modal" class="pw-overlay">
  <div style="background:#fff;width:100%;max-width:720px;border-radius:8px;max-height:90vh;display:flex;flex-direction:column">
    <div style="padding:20px 24px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
      <h3 id="day-modal-title" style="margin:0;font-size:1rem"><i class="fas fa-calendar-day" style="color:#2563eb;margin-right:8px"></i>Day Detail</h3>
      <div style="display:flex;gap:8px;align-items:center">
        <button id="day-pdf-btn" class="action-btn" onclick="exportDayPDF()"><i class="fas fa-file-pdf"></i> Export PDF</button>
        <button onclick="document.getElementById('day-modal').classList.remove('open')" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#aaa;line-height:1">&times;</button>
      </div>
    </div>
    <div id="day-modal-body" style="overflow-y:auto;padding:20px 24px;flex:1">
      <div style="text-align:center;padding:32px;color:#aaa"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div id="del-modal" class="pw-overlay">
  <div class="pw-box">
    <div style="width:54px;height:54px;background:#fff0f0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
      <i class="fas fa-trash" style="color:#c00;font-size:1.3rem"></i>
    </div>
    <h3 style="margin-bottom:8px;font-size:1.05rem">Delete Confirmation</h3>
    <p id="del-msg" style="color:#666;font-size:.88rem;margin-bottom:24px">Are you sure you want to delete this transaction permanently?</p>
    <div style="display:flex;gap:12px;justify-content:center">
      <button id="del-cancel" class="action-btn" style="min-width:100px">Cancel</button>
      <button id="del-confirm" style="min-width:100px;background:#c00;color:#fff;border:none;padding:10px 20px;border-radius:4px;cursor:pointer;font-weight:600;font-size:.88rem">Delete</button>
    </div>
  </div>
</div>

<script>
(function () {
  var AJAX = 'ajax_gcash.php';

  // ── Chart data from PHP ────────────────────────────────────────────────
  var chartRaw = <?= json_encode($chartData) ?>;

  // ── Charge calculation ─────────────────────────────────────────────────
  function calcCharge(amount) {
    if (amount <= 199) return 5;
    if (amount <= 599) return 10;
    return Math.round(amount * 0.02 * 100) / 100;
  }

  function fmt(n) {
    return parseFloat(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });
  }

  // ── Live preview ───────────────────────────────────────────────────────
  window.previewCharge = function (type) {
    var amt = parseFloat(document.getElementById(type + '-amount').value) || 0;
    var el  = document.getElementById(type + '-preview');
    if (amt <= 0) { el.style.display = 'none'; return; }
    var charge = calcCharge(amt);
    el.style.display = 'block';
    el.innerHTML = '<i class="fas fa-calculator" style="margin-right:6px"></i>'
      + 'Amount: <strong>₱' + fmt(amt) + '</strong> &nbsp;+&nbsp; '
      + 'Charge: <strong>₱' + fmt(charge) + '</strong> &nbsp;=&nbsp; '
      + 'Total: <strong>₱' + fmt(amt + charge) + '</strong>';
  };

  // ── Tab switch ─────────────────────────────────────────────────────────
  window.switchGcTab = function (type, btn) {
    document.querySelectorAll('.gc-tab').forEach(function (t) { t.classList.remove('active'); });
    document.querySelectorAll('.gc-pane').forEach(function (p) { p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('pane-' + type).classList.add('active');
    if (type === 'graph' && !window._chartBuilt) buildChart('profit');
  };

  // ── Toast ──────────────────────────────────────────────────────────────
  function toast(msg, type) {
    var t = document.createElement('div');
    t.className = 'pw-toast-el';
    t.textContent = msg;
    t.style.background = type === 'error' ? '#c00' : '#111';
    document.body.appendChild(t);
    setTimeout(function () { t.style.opacity = '1'; }, 10);
    setTimeout(function () { t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 300); }, 3000);
  }

  // ── Submit transaction ─────────────────────────────────────────────────
  window.submitTx = function (type) {
    var amtEl = document.getElementById(type + '-amount');
    var amt   = parseFloat(amtEl.value);
    if (!amt || amt <= 0) { toast('Please enter a valid amount.', 'error'); amtEl.focus(); return; }
    var fd = new FormData();
    fd.append('action', 'add'); fd.append('type', type); fd.append('amount', amt);
    fetch(AJAX, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.ok) { toast(res.msg || 'Error.', 'error'); return; }
        amtEl.value = '';
        document.getElementById(type + '-preview').style.display = 'none';
        injectRow(type, res.row);
        updateSummary(type, res.summary);
        updateOverall(res.overall);
        toast((type === 'in' ? 'Cash In' : 'Cash Out') + ' added — ₱' + fmt(res.row.amount));
      })
      .catch(function () { toast('Network error.', 'error'); });
  };

  function injectRow(type, row) {
    var tbody = document.querySelector('#tbl-' + type + ' tbody');
    var empty = document.getElementById('gc-' + type + '-empty');
    if (empty) empty.remove();
    tbody.querySelectorAll('tr').forEach(function (tr, i) {
      var f = tr.querySelector('td'); if (f) f.textContent = i + 2;
    });
    var tr = document.createElement('tr');
    tr.id = 'gc-' + type + '-' + row.id;
    tr.innerHTML = '<td>1</td>'
      + '<td style="white-space:nowrap">' + row.created_at + '</td>'
      + '<td>₱' + fmt(row.amount) + '</td>'
      + '<td style="color:#16a34a;font-weight:600">₱' + fmt(row.charge) + '</td>'
      + '<td style="font-weight:700">₱' + fmt(row.total) + '</td>'
      + '<td><button class="action-btn danger" onclick="deleteTx(\'' + type + '\',' + row.id + ',\'₱' + fmt(row.amount) + '\')">'
      + '<i class="fas fa-trash"></i></button></td>';
    tbody.insertBefore(tr, tbody.firstChild);
  }

  function updateSummary(type, s) {
    var cards = document.querySelectorAll('#pane-' + type + ' .gc-sum-card .val');
    if (cards[0]) cards[0].textContent = '₱' + fmt(s.total_amount);
    if (cards[1]) cards[1].textContent = '₱' + fmt(s.total_charge);
    if (cards[2]) cards[2].textContent = '₱' + fmt(s.total_total);
  }

  function updateOverall(o) {
    var items = document.querySelectorAll('.gc-overall .ov-val');
    if (items[0]) items[0].textContent = '₱' + fmt(o.total_amount);
    if (items[1]) items[1].textContent = '₱' + fmt(o.total_charge);
    if (items[2]) items[2].textContent = '₱' + fmt(o.total_total);
    if (items[3]) items[3].textContent = o.tx_count;
  }

  // ── Delete ─────────────────────────────────────────────────────────────
  var _delFn = null;
  document.getElementById('del-cancel').addEventListener('click', function () {
    document.getElementById('del-modal').classList.remove('open'); _delFn = null;
  });
  document.getElementById('del-modal').addEventListener('click', function (e) {
    if (e.target === this) { this.classList.remove('open'); _delFn = null; }
  });
  document.getElementById('del-confirm').addEventListener('click', function () {
    document.getElementById('del-modal').classList.remove('open');
    if (_delFn) { _delFn(); _delFn = null; }
  });

  window.deleteTx = function (type, id, label) {
    document.getElementById('del-msg').textContent = 'Delete transaction ' + label + ' permanently?';
    _delFn = function () {
      var fd = new FormData();
      fd.append('action', 'delete'); fd.append('type', type); fd.append('id', id);
      fetch(AJAX, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.ok) {
            var row = document.getElementById('gc-' + type + '-' + id);
            if (row) {
              row.style.transition = 'opacity .3s'; row.style.opacity = '0';
              setTimeout(function () {
                row.remove();
                var tbody = document.querySelector('#tbl-' + type + ' tbody');
                tbody.querySelectorAll('tr').forEach(function (tr, i) {
                  var f = tr.querySelector('td'); if (f) f.textContent = i + 1;
                });
                if (!tbody.querySelector('tr')) {
                  var e = document.createElement('tr');
                  e.id = 'gc-' + type + '-empty';
                  e.innerHTML = '<td colspan="6" style="text-align:center;color:#aaa;padding:32px">No records yet.</td>';
                  tbody.appendChild(e);
                }
              }, 320);
            }
            updateSummary(type, res.summary);
            updateOverall(res.overall);
            toast('Transaction deleted.');
          } else { toast(res.msg || 'Delete failed.', 'error'); }
        })
        .catch(function () { toast('Network error.', 'error'); });
    };
    document.getElementById('del-modal').classList.add('open');
  };

  // ── Chart ──────────────────────────────────────────────────────────────
  var _chart = null;
  window._chartBuilt = false;

  function buildChart(mode) {
    var labels = chartRaw.map(function (r) { return r.day; });
    var data   = chartRaw.map(function (r) {
      return mode === 'profit' ? parseFloat(r.total_charge) : parseInt(r.tx_count);
    });
    var maxVal = Math.max.apply(null, data);
    var colors = data.map(function (v) {
      return v === maxVal && v > 0 ? '#f59e0b' : '#16a34a';
    });
    var ctx = document.getElementById('gcash-chart').getContext('2d');
    if (_chart) _chart.destroy();
    _chart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: mode === 'profit' ? 'Daily Profit (₱)' : 'Transactions',
          data: data,
          borderColor: '#16a34a',
          backgroundColor: 'rgba(22,163,74,0.08)',
          pointBackgroundColor: colors,
          pointBorderColor: colors,
          pointRadius: 5,
          pointHoverRadius: 7,
          borderWidth: 2.5,
          fill: true,
          tension: 0.35,
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                return mode === 'profit'
                  ? '₱' + parseFloat(ctx.raw).toLocaleString('en-PH', { minimumFractionDigits: 2 })
                  : ctx.raw + ' transaction(s)';
              }
            }
          }
        },
        scales: {
          y: { beginAtZero: true, ticks: { font: { size: 11 } } },
          x: { ticks: { font: { size: 10 }, maxRotation: 45 } }
        }
      }
    });
    window._chartBuilt = true;
  }

  window.toggleChart = function (mode, btn) {
    document.querySelectorAll('.gc-chart-toggle').forEach(function (b) { b.classList.remove('active'); });
    btn.classList.add('active');
    buildChart(mode);
  };

  // ── PDF Export (browser print of a generated HTML page) ───────────────
  function buildPdfHtml(title, headers, rows, summaryLines) {
    var printDate = new Date().toLocaleString('en-PH', { dateStyle: 'long', timeStyle: 'short' });
    var thead = '<tr>' + headers.map(function (h) { return '<th>' + h + '</th>'; }).join('') + '</tr>';
    var tbody = rows.map(function (r) {
      return '<tr>' + r.map(function (c) { return '<td>' + c + '</td>'; }).join('') + '</tr>';
    }).join('');
    var summary = summaryLines.map(function (l) {
      return '<tr><td style="font-weight:700;padding:6px 12px">' + l[0] + '</td><td style="padding:6px 12px">' + l[1] + '</td></tr>';
    }).join('');
    return '<!DOCTYPE html><html><head><meta charset="UTF-8">'
      + '<title>' + title + '</title>'
      + '<style>body{font-family:Arial,sans-serif;padding:32px;color:#111}'
      + 'h1{font-size:20px;margin-bottom:4px}p.sub{color:#888;font-size:12px;margin-bottom:20px}'
      + 'table{width:100%;border-collapse:collapse;margin-bottom:20px}'
      + 'th{background:#111;color:#fff;padding:8px 12px;font-size:11px;text-align:left}'
      + 'td{padding:7px 12px;border-bottom:1px solid #eee;font-size:12px}'
      + 'tr:nth-child(even) td{background:#f9f9f9}'
      + '.sum-table td:first-child{color:#888;font-size:11px;text-transform:uppercase;letter-spacing:1px}'
      + '.sum-table td:last-child{font-weight:700;font-size:14px}'
      + '</style></head><body>'
      + '<h1>Printworld — ' + title + '</h1>'
      + '<p class="sub">Print Date: ' + printDate + '</p>'
      + '<table><thead>' + thead + '</thead><tbody>' + tbody + '</tbody></table>'
      + '<table class="sum-table">' + summary + '</table>'
      + '<script>window.onload=function(){window.print();}<\/script>'
      + '</body></html>';
  }

  window.exportTypePDF = function (type) {
    var isIn   = type === 'in';
    var title  = isIn ? 'Cash In Transactions' : 'Cash Out Transactions';
    var rows   = [];
    document.querySelectorAll('#tbl-' + type + ' tbody tr').forEach(function (tr) {
      var cells = tr.querySelectorAll('td');
      if (cells.length < 5) return;
      rows.push([cells[0].textContent, cells[1].textContent, cells[2].textContent, cells[3].textContent, cells[4].textContent]);
    });
    var sumCards = document.querySelectorAll('#pane-' + type + ' .gc-sum-card');
    var summary  = [
      ['Total Amount',          sumCards[0] ? sumCards[0].querySelector('.val').textContent : '—'],
      ['Total Charges (Profit)',sumCards[1] ? sumCards[1].querySelector('.val').textContent : '—'],
      ['Overall Total',         sumCards[2] ? sumCards[2].querySelector('.val').textContent : '—'],
    ];
    var html = buildPdfHtml(title, ['#','Date & Time','Amount','Charge','Total'], rows, summary);
    var w = window.open('', '_blank');
    w.document.write(html);
    w.document.close();
  };

  window.exportDailyPDF = function () {
    var rows = [];
    document.querySelectorAll('#pane-daily .data-table tbody tr').forEach(function (tr) {
      var cells = tr.querySelectorAll('td');
      if (cells.length < 6) return;
      rows.push([
        cells[0].textContent.replace('🔥 Busiest','').trim(),
        cells[1].textContent, cells[2].textContent,
        cells[3].textContent, cells[4].textContent, cells[5].textContent
      ]);
    });
    var html = buildPdfHtml('Daily Summary',
      ['Date','Cash In','Cash Out','Total Charges','Total Capital','Transactions'],
      rows, []);
    var w = window.open('', '_blank');
    w.document.write(html);
    w.document.close();
  };

  window.exportPDF = function () {
    // Full report — both types + daily
    var inRows = [], outRows = [], dailyRows = [];
    document.querySelectorAll('#tbl-in tbody tr').forEach(function (tr) {
      var c = tr.querySelectorAll('td');
      if (c.length >= 5) inRows.push([c[0].textContent,c[1].textContent,c[2].textContent,c[3].textContent,c[4].textContent]);
    });
    document.querySelectorAll('#tbl-out tbody tr').forEach(function (tr) {
      var c = tr.querySelectorAll('td');
      if (c.length >= 5) outRows.push([c[0].textContent,c[1].textContent,c[2].textContent,c[3].textContent,c[4].textContent]);
    });
    document.querySelectorAll('#pane-daily .data-table tbody tr').forEach(function (tr) {
      var c = tr.querySelectorAll('td');
      if (c.length >= 6) dailyRows.push([c[0].textContent.replace('🔥 Busiest','').trim(),c[1].textContent,c[2].textContent,c[3].textContent,c[4].textContent,c[5].textContent]);
    });
    var overall = document.querySelectorAll('.gc-overall .ov-val');
    var printDate = new Date().toLocaleString('en-PH', { dateStyle: 'long', timeStyle: 'short' });
    function tbl(headers, rows) {
      return '<table><thead><tr>' + headers.map(function(h){return '<th>'+h+'</th>';}).join('') + '</tr></thead><tbody>'
        + (rows.length ? rows.map(function(r){return '<tr>'+r.map(function(c){return '<td>'+c+'</td>';}).join('')+'</tr>';}).join('') : '<tr><td colspan="'+headers.length+'" style="color:#aaa;text-align:center">No records</td></tr>')
        + '</tbody></table>';
    }
    var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>GCash Full Report</title>'
      + '<style>body{font-family:Arial,sans-serif;padding:32px;color:#111}h1{font-size:20px}h2{font-size:14px;margin:24px 0 8px;text-transform:uppercase;letter-spacing:1px;color:#555}'
      + 'p.sub{color:#888;font-size:12px;margin-bottom:20px}'
      + 'table{width:100%;border-collapse:collapse;margin-bottom:16px}'
      + 'th{background:#111;color:#fff;padding:8px 12px;font-size:11px;text-align:left}'
      + 'td{padding:7px 12px;border-bottom:1px solid #eee;font-size:12px}'
      + 'tr:nth-child(even) td{background:#f9f9f9}'
      + '.overall{background:#111;color:#fff;padding:16px 20px;border-radius:6px;display:flex;gap:32px;flex-wrap:wrap;margin-bottom:24px}'
      + '.overall .item .val{font-size:18px;font-weight:800}.overall .item .lbl{font-size:10px;color:#aaa;text-transform:uppercase;letter-spacing:1px}'
      + '</style></head><body>'
      + '<h1>Printworld — GCash Full Report</h1>'
      + '<p class="sub">Print Date: ' + printDate + '</p>'
      + '<div class="overall">'
      + (overall[0] ? '<div class="item"><div class="val">'+overall[0].textContent+'</div><div class="lbl">Total Capital</div></div>' : '')
      + (overall[1] ? '<div class="item"><div class="val" style="color:#f5c842">'+overall[1].textContent+'</div><div class="lbl">Total Profit</div></div>' : '')
      + (overall[2] ? '<div class="item"><div class="val">'+overall[2].textContent+'</div><div class="lbl">Combined Total</div></div>' : '')
      + (overall[3] ? '<div class="item"><div class="val" style="color:#86efac">'+overall[3].textContent+'</div><div class="lbl">Transactions</div></div>' : '')
      + '</div>'
      + '<h2>Cash In</h2>' + tbl(['#','Date & Time','Amount','Charge','Total'], inRows)
      + '<h2>Cash Out</h2>' + tbl(['#','Date & Time','Amount','Charge','Total'], outRows)
      + '<h2>Daily Summary</h2>' + tbl(['Date','Cash In','Cash Out','Total Charges','Total Capital','Transactions'], dailyRows)
      + '<script>window.onload=function(){window.print();}<\/script>'
      + '</body></html>';
    var w = window.open('', '_blank');
    w.document.write(html);
    w.document.close();
  };

  // ── Day drill-down ─────────────────────────────────────────────────────
  var _dayData = null;
  var _dayDate = '';

  window.openDayDetail = function (date) {
    _dayDate = date;
    var modal = document.getElementById('day-modal');
    var body  = document.getElementById('day-modal-body');
    document.getElementById('day-modal-title').innerHTML =
      '<i class="fas fa-calendar-day" style="color:#2563eb;margin-right:8px"></i>' +
      'Transactions for ' + new Date(date + 'T00:00:00').toLocaleDateString('en-PH', { year:'numeric', month:'long', day:'numeric' });
    body.innerHTML = '<div style="text-align:center;padding:32px;color:#aaa"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    modal.classList.add('open');

    var fd = new FormData();
    fd.append('action', 'day_detail');
    fd.append('date', date);
    fetch(AJAX, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.ok) { body.innerHTML = '<p style="color:#c00;padding:20px">' + (res.msg || 'Error loading.') + '</p>'; return; }
        _dayData = res;
        body.innerHTML = buildDayHtmlBody(res, false);
      })
      .catch(function () { body.innerHTML = '<p style="color:#c00;padding:20px">Network error.</p>'; });
  };

  document.getElementById('day-modal').addEventListener('click', function (e) {
    if (e.target === this) this.classList.remove('open');
  });

  function buildDayHtmlBody(res, forPdf) {
    var inRows  = res.cash_in  || [];
    var outRows = res.cash_out || [];
    var s       = res.summary  || {};

    function calcSub(rows) {
      var amt = 0, chg = 0, tot = 0;
      rows.forEach(function (r) { amt += parseFloat(r.amount)||0; chg += parseFloat(r.charge)||0; tot += parseFloat(r.total)||0; });
      return { amt: amt, chg: chg, tot: tot };
    }

    function tblRows(rows, type) {
      if (!rows.length) return '<tr><td colspan="5" style="text-align:center;color:#aaa;padding:16px">No ' + type + ' transactions.</td></tr>';
      var sub = calcSub(rows);
      var html = rows.map(function (r, i) {
        return '<tr>'
          + '<td>' + (i+1) + '</td>'
          + '<td style="white-space:nowrap">' + r.time + '</td>'
          + '<td>₱' + fmt(r.amount) + '</td>'
          + '<td style="color:#16a34a;font-weight:600">₱' + fmt(r.charge) + '</td>'
          + '<td style="font-weight:700">₱' + fmt(r.total) + '</td>'
          + '</tr>';
      }).join('');
      // subtotal row
      html += '<tr style="background:#f0fdf4;border-top:2px solid #16a34a">'
        + '<td colspan="2" style="font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:1px;padding:8px 12px;color:#166534">Subtotal</td>'
        + '<td style="font-weight:800;color:#111">₱' + fmt(sub.amt) + '</td>'
        + '<td style="font-weight:800;color:#16a34a">₱' + fmt(sub.chg) + '</td>'
        + '<td style="font-weight:800;color:#111">₱' + fmt(sub.tot) + '</td>'
        + '</tr>';
      return html;
    }

    var tblStyle = 'width:100%;border-collapse:collapse;margin-bottom:20px;font-size:.88rem';
    var thStyle  = 'background:#111;color:#fff;padding:8px 12px;text-align:left;font-size:.75rem';

    return '<h4 style="margin:0 0 10px;font-size:.82rem;text-transform:uppercase;letter-spacing:1px;color:#16a34a"><i class="fas fa-arrow-down" style="margin-right:6px"></i>Cash In</h4>'
      + '<table style="' + tblStyle + '"><thead><tr>'
      + '<th style="' + thStyle + '">#</th><th style="' + thStyle + '">Time</th><th style="' + thStyle + '">Amount</th><th style="' + thStyle + '">Charge</th><th style="' + thStyle + '">Total</th>'
      + '</tr></thead><tbody>' + tblRows(inRows, 'Cash In') + '</tbody></table>'
      + '<h4 style="margin:0 0 10px;font-size:.82rem;text-transform:uppercase;letter-spacing:1px;color:#dc2626"><i class="fas fa-arrow-up" style="margin-right:6px"></i>Cash Out</h4>'
      + '<table style="' + tblStyle + '"><thead><tr>'
      + '<th style="' + thStyle + '">#</th><th style="' + thStyle + '">Time</th><th style="' + thStyle + '">Amount</th><th style="' + thStyle + '">Charge</th><th style="' + thStyle + '">Total</th>'
      + '</tr></thead><tbody>' + tblRows(outRows, 'Cash Out') + '</tbody></table>'
      + '<table style="' + tblStyle + 'border-top:2px solid #111;margin-top:8px">'
      + '<tr style="background:#111"><td colspan="2" style="padding:8px 12px;color:#fff;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:1px">Overall Total</td>'
      + '<td style="padding:8px 12px;color:#fff;font-weight:800">₱' + fmt(s.total_amount || 0) + '</td>'
      + '<td style="padding:8px 12px;color:#f5c842;font-weight:800">₱' + fmt(s.total_charge || 0) + '</td>'
      + '<td style="padding:8px 12px;color:#fff;font-weight:800">₱' + fmt(s.total_total || 0) + '</td>'
      + '</tr></table>';
  }

  window.exportDayPDF = function () {
    if (!_dayData || !_dayDate) return;
    var printDate = new Date().toLocaleString('en-PH', { dateStyle: 'long', timeStyle: 'short' });
    var reportDate = new Date(_dayDate + 'T00:00:00').toLocaleDateString('en-PH', { year:'numeric', month:'long', day:'numeric' });
    var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>GCash Daily Report — ' + reportDate + '</title>'
      + '<style>body{font-family:Arial,sans-serif;padding:32px;color:#111}'
      + 'h1{font-size:18px;margin-bottom:2px}p.sub{color:#888;font-size:11px;margin-bottom:20px}'
      + 'h4{font-size:12px;text-transform:uppercase;letter-spacing:1px;margin:16px 0 6px}'
      + 'table{width:100%;border-collapse:collapse;margin-bottom:16px}'
      + 'th{background:#111;color:#fff;padding:7px 10px;font-size:10px;text-align:left}'
      + 'td{padding:6px 10px;border-bottom:1px solid #eee;font-size:11px}'
      + 'tr:nth-child(even) td{background:#f9f9f9}'
      + '.sum td:first-child{color:#888;font-size:10px;text-transform:uppercase;letter-spacing:1px}'
      + '.sum td:last-child{font-weight:700;font-size:13px}'
      + '</style></head><body>'
      + '<h1>Printworld — GCash Daily Report</h1>'
      + '<p class="sub">Report Date: ' + reportDate + ' &nbsp;|&nbsp; Print Date: ' + printDate + '</p>'
      + buildDayHtmlBody(_dayData, true)
      + '<script>window.onload=function(){window.print();}<\/script>'
      + '</body></html>';
    var w = window.open('', '_blank');
    w.document.write(html);
    w.document.close();
  };

  // ── Midnight auto-refresh ──────────────────────────────────────────────
  (function scheduleMidnightRefresh() {
    var now  = new Date();
    var next = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 5); // 12:00:05 AM next day
    var ms   = next - now;
    setTimeout(function () {
      location.reload();
    }, ms);
  })();



  // ── GCash Notes ────────────────────────────────────────────────────────
  window.saveGcNote = function () {
    var txt = document.getElementById('gcnote-input').value.trim();
    if (!txt) { toast('Please write a note first.', 'error'); return; }
    var fd = new FormData();
    fd.append('action', 'add_note');
    fd.append('note', txt);
    fetch(AJAX, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.ok) { toast(res.msg || 'Error.', 'error'); return; }
        document.getElementById('gcnote-input').value = '';
        var empty = document.getElementById('gcnotes-empty');
        if (empty) empty.remove();
        var list = document.getElementById('gcnotes-list');
        var div  = document.createElement('div');
        div.className = 'gcnote-item';
        div.id = 'gcnote-' + res.id;
        div.style.cssText = 'border-bottom:1px solid #f0f0f0;padding:14px 0;display:flex;gap:12px;align-items:flex-start';
        div.innerHTML =
          '<div style="width:36px;height:36px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">'
          + '<i class="fas fa-note-sticky" style="color:#d97706;font-size:.85rem"></i></div>'
          + '<div style="flex:1"><div style="font-size:.9rem;line-height:1.6;white-space:pre-wrap">' + escHtml(txt) + '</div>'
          + '<div style="font-size:.75rem;color:#aaa;margin-top:4px">' + res.created_at + '</div></div>'
          + '<button class="action-btn danger" onclick="deleteGcNote(' + res.id + ')" style="flex-shrink:0"><i class="fas fa-trash"></i></button>';
        list.insertBefore(div, list.firstChild);
        toast('Note added.');
      })
      .catch(function () { toast('Network error.', 'error'); });
  };

  window.deleteGcNote = function (id) {
    if (!confirm('Delete this note permanently?')) return;
    var fd = new FormData();
    fd.append('action', 'delete_note');
    fd.append('id', id);
    fetch(AJAX, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.ok) {
          var el = document.getElementById('gcnote-' + id);
          if (el) { el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(function(){el.remove();},320); }
          toast('Note deleted.');
        } else { toast(res.msg || 'Error.', 'error'); }
      })
      .catch(function () { toast('Network error.', 'error'); });
  };

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Auto-lock toggle ───────────────────────────────────────────────────
  window.saveAutolock = function (enabled) {
    var slider = document.getElementById('autolock-slider');
    var dot    = slider ? slider.querySelector('span') : null;
    if (slider) slider.style.background = enabled ? '#7c3aed' : '#ccc';
    if (dot)    dot.style.left          = enabled ? '21px'   : '3px';
    var fd = new FormData();
    fd.append('action', 'toggle_autolock');
    fd.append('enabled', enabled ? '1' : '0');
    fetch(AJAX, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        toast(enabled ? 'Auto-Lock enabled.' : 'Auto-Lock disabled.');
      })
      .catch(function () { toast('Failed to save setting.', 'error'); });
  };

})();
</script>
</body>
</html>
