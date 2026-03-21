<?php
if (session_status() === PHP_SESSION_NONE) session_start();
ini_set('display_errors', 0);
ob_start();

require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

header('Content-Type: application/json');
ob_clean();

$db     = db();
$action = $_POST['action'] ?? '';

function jsonOut(bool $ok, string $msg = ''): void
{
    echo json_encode(['ok' => $ok, 'msg' => $msg]);
    exit;
}

if ($action === 'add') {
    $client = trim($_POST['client_name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $price  = (float)($_POST['price'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['Paid', 'Unpaid']) ? $_POST['status'] : 'Unpaid';

    if (!$client || !$desc) {
        jsonOut(false, 'Client name and description are required.');
    }

    $stmt = $db->prepare(
        "INSERT INTO client_notes (client_name, description, price, status) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param('ssds', $client, $desc, $price, $status);
    $stmt->execute();
    $newId = $db->insert_id;
    $date  = date('Y-m-d');
    echo json_encode(['ok' => true, 'msg' => 'Item added.', 'id' => $newId, 'date' => $date]);
    exit;
}

if ($action === 'edit') {
    $id     = (int)($_POST['id'] ?? 0);
    $client = trim($_POST['client_name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $price  = (float)($_POST['price'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['Paid', 'Unpaid']) ? $_POST['status'] : 'Unpaid';

    if (!$id || !$client || !$desc) {
        jsonOut(false, 'Missing required fields.');
    }

    $stmt = $db->prepare(
        "UPDATE client_notes SET client_name=?, description=?, price=?, status=? WHERE id=?"
    );
    $stmt->bind_param('ssdsi', $client, $desc, $price, $status, $id);
    $stmt->execute();
    jsonOut(true, 'Item updated.');
}

if ($action === 'status') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['Paid', 'Unpaid']) ? $_POST['status'] : 'Unpaid';

    if (!$id) {
        jsonOut(false, 'Invalid ID.');
    }

    $stmt = $db->prepare("UPDATE client_notes SET status=? WHERE id=?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    jsonOut(true, 'Status updated.');
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    if (!$id) {
        jsonOut(false, 'Invalid ID.');
    }

    $stmt = $db->prepare("DELETE FROM client_notes WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    jsonOut(true, 'Item deleted.');
}

if ($action === 'delete_client') {
    $client = trim($_POST['client_name'] ?? '');

    if (!$client) {
        jsonOut(false, 'Client name is required.');
    }

    $stmt = $db->prepare("DELETE FROM client_notes WHERE client_name=?");
    $stmt->bind_param('s', $client);
    $stmt->execute();
    jsonOut(true, 'Client deleted.');
}

jsonOut(false, 'Unknown action.');
