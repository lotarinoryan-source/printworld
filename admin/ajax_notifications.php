<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$db = db();
$row = $db->query("SELECT COUNT(*) AS cnt FROM quotation_requests WHERE status='pending'")->fetch_assoc();
header('Content-Type: application/json');
echo json_encode(['pending' => (int)$row['cnt']]);
