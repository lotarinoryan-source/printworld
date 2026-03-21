<?php
if (session_status() === PHP_SESSION_NONE) session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../config.php';
require_once '../includes/db.php';

if (empty($_SESSION['admin_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'POST only']);
    exit;
}

$db     = db();
$action = trim($_POST['action'] ?? '');

if ($action === 'delete_one') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['ok' => false, 'msg' => 'Invalid ID']); exit; }
    $s = $db->prepare('DELETE FROM client_color_codes WHERE id = ?');
    $s->bind_param('i', $id);
    $s->execute();
    echo json_encode(['ok' => true, 'affected' => $s->affected_rows]);

} elseif ($action === 'delete_client') {
    $client = trim($_POST['client_name'] ?? '');
    if (!$client) { echo json_encode(['ok' => false, 'msg' => 'Missing client name']); exit; }
    $s = $db->prepare('DELETE FROM client_color_codes WHERE client_name = ?');
    $s->bind_param('s', $client);
    $s->execute();
    echo json_encode(['ok' => true, 'affected' => $s->affected_rows]);

} else {
    echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
}
