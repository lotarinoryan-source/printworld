<?php
require_once 'config.php';
require_once 'includes/functions.php';
$rnum = sanitizeInput($_GET['r'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="assets/pw.png">
<title>Printworld - Thank You</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="thankyou-page">
  <div class="thankyou-card">
    <div class="thankyou-icon"><i class="fas fa-check"></i></div>
    <h1>Request Submitted!</h1>
    <p>Thank you for reaching out to <strong><?= SITE_NAME ?></strong>.</p>
    <p style="color:var(--gray-400);font-size:0.9rem">Our team will review your request and send you a detailed quotation shortly.</p>
    <?php if ($rnum): ?>
    <div class="quotation-ref"><?= htmlspecialchars($rnum) ?></div>
    <?php endif; ?>
    <p style="font-size:0.82rem;color:var(--gray-400);margin-top:8px">Keep this reference number for your records.</p>
    <div class="thankyou-actions">
      <a href="quotation.php" class="btn btn-dark"><i class="fas fa-plus"></i> New Request</a>
      <a href="index.php" class="btn btn-outline" style="color:var(--black);border-color:var(--gray-200)"><i class="fas fa-home"></i> Home</a>
    </div>
    <div style="margin-top:32px;padding-top:24px;border-top:1px solid var(--gray-200)">
      <p style="font-size:0.82rem;color:var(--gray-400);margin-bottom:12px">For urgent inquiries:</p>
      <p style="font-size:0.9rem;margin-bottom:6px"><i class="fas fa-phone" style="width:16px;margin-right:6px"></i><a href="tel:<?= CONTACT_PHONE ?>"><?= CONTACT_PHONE ?></a></p>
      <p style="font-size:0.9rem;margin-bottom:6px"><i class="fas fa-envelope" style="width:16px;margin-right:6px"></i><a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a></p>
      <p style="font-size:0.9rem"><i class="fab fa-facebook-f" style="width:16px;margin-right:6px"></i><a href="<?= CONTACT_FACEBOOK ?>" target="_blank" rel="noopener">Facebook Page</a></p>
    </div>
  </div>
</div>
</body>
</html>
