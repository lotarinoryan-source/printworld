<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function adminLogin(string $username, string $password): bool {
    $db = db();
    $stmt = $db->prepare("SELECT id, password FROM admins WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_username'] = $username;
            return true;
        }
    }
    return false;
}

function adminLogout(): void {
    session_destroy();
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit;
}
