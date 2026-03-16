<?php
require_once '../config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if (isAdminLoggedIn()) {
    echo json_encode(['success' => true]);
    exit;
}

$username = htmlspecialchars(strip_tags(trim($_POST['username'] ?? '')));
$password = $_POST['password'] ?? '';

if (adminLogin($username, $password)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
}
