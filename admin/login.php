<?php
require_once '../config.php';
require_once '../includes/auth.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (adminLogin($username, $password)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid username or password.';
}

function sanitize_string(string $s): string {
    return htmlspecialchars(strip_tags(trim($s)));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../assets/pw.png">
  <title>Printworld - Admin Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-login-page">
  <div class="login-card">
    <div class="login-logo">
      <h1><?= SITE_NAME ?></h1>
      <p>ADMIN PANEL</p>
    </div>
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="admin" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-dark" style="width:100%;justify-content:center;margin-top:8px">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>
    <p style="text-align:center;margin-top:20px;font-size:0.8rem"><a href="../index.php" style="color:var(--gray-400)">← Back to website</a></p>
  </div>
</div>
</body>
</html>
