<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '_layout.php';
requireAdmin();

$db = db();
$totalRequests  = $db->query("SELECT COUNT(*) as c FROM quotation_requests")->fetch_assoc()['c'];
$pendingCount   = $db->query("SELECT COUNT(*) as c FROM quotation_requests WHERE status='pending'")->fetch_assoc()['c'];
$todayCount     = $db->query("SELECT COUNT(*) as c FROM quotation_requests WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'];
$totalQuoted    = $db->query("SELECT COUNT(*) as c FROM final_quotations")->fetch_assoc()['c'];

$recent = $db->query("SELECT * FROM quotation_requests ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/pw.png">
  <title>Printworld - Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php adminSidebar('dashboard'); ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1>Dashboard</h1>
    <div class="topbar-right">
      <span style="font-size:0.85rem;color:var(--gray-400)">Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card dark">
      <div class="stat-num"><?= $totalRequests ?></div>
      <div class="stat-label">Total Requests</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $pendingCount ?></div>
      <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $todayCount ?></div>
      <div class="stat-label">Today</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $totalQuoted ?></div>
      <div class="stat-label">Quotations Sent</div>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-header">
      <h3>Recent Quotation Requests</h3>
      <a href="quotations.php" class="action-btn">View All</a>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th>Request #</th><th>Client</th><th>Email</th><th>Status</th><th>Date</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $recent->fetch_assoc()): ?>
        <tr>
          <td><strong><?= htmlspecialchars($row['request_number']) ?></strong></td>
          <td><?= htmlspecialchars($row['customer_name']) ?></td>
          <td><?= htmlspecialchars($row['email']) ?></td>
          <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
          <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
          <td>
            <a href="quotations.php?view=<?= $row['id'] ?>" class="action-btn">View</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
