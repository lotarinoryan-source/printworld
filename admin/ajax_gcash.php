<?php
if (session_status() === PHP_SESSION_NONE) session_start();
ini_set('display_errors', 0);
ob_start();

require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

// Must be unlocked
if (empty($_SESSION['gcash_unlocked'])) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized.']);
    exit;
}

header('Content-Type: application/json');
ob_clean();

$db     = db();
$action = $_POST['action'] ?? '';
$type   = $_POST['type']   ?? '';

if (!in_array($type, ['in', 'out'])) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid type.']);
    exit;
}

$table = $type === 'in' ? 'gcash_cash_in' : 'gcash_cash_out';

// ── Charge calculation ─────────────────────────────────────────────────
function calcCharge(float $amount): float
{
    if ($amount <= 199) return 5.00;
    if ($amount <= 599) return 10.00;
    return round($amount * 0.02, 2);
}

// ── Summary helper ─────────────────────────────────────────────────────
function getSummary($db, string $table): array
{
    return $db->query("SELECT
        COALESCE(SUM(amount),0) AS total_amount,
        COALESCE(SUM(charge),0) AS total_charge,
        COALESCE(SUM(total),0)  AS total_total
        FROM `$table`")->fetch_assoc();
}

function getOverall($db): array
{
    $in  = getSummary($db, 'gcash_cash_in');
    $out = getSummary($db, 'gcash_cash_out');
    $cIn  = (int)$db->query("SELECT COUNT(*) AS c FROM gcash_cash_in")->fetch_assoc()['c'];
    $cOut = (int)$db->query("SELECT COUNT(*) AS c FROM gcash_cash_out")->fetch_assoc()['c'];
    return [
        'total_amount' => $in['total_amount'] + $out['total_amount'],
        'total_charge' => $in['total_charge'] + $out['total_charge'],
        'total_total'  => $in['total_total']  + $out['total_total'],
        'tx_count'     => $cIn + $cOut,
    ];
}

// ── ADD ────────────────────────────────────────────────────────────────
if ($action === 'add') {
    $amount = (float)($_POST['amount'] ?? 0);
    if ($amount <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'Amount must be greater than 0.']);
        exit;
    }
    $charge = calcCharge($amount);
    $total  = $amount + $charge;

    $stmt = $db->prepare("INSERT INTO `$table` (amount, charge, total) VALUES (?, ?, ?)");
    $stmt->bind_param('ddd', $amount, $charge, $total);
    $stmt->execute();
    $newId = $db->insert_id;

    $row = $db->query("SELECT * FROM `$table` WHERE id=$newId")->fetch_assoc();
    $row['created_at'] = date('M d, Y h:i A', strtotime($row['created_at']));

    echo json_encode([
        'ok'      => true,
        'row'     => $row,
        'summary' => getSummary($db, $table),
        'overall' => getOverall($db),
    ]);
    exit;
}

// ── DELETE ─────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid ID.']);
        exit;
    }
    $stmt = $db->prepare("DELETE FROM `$table` WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    echo json_encode([
        'ok'      => true,
        'summary' => getSummary($db, $table),
        'overall' => getOverall($db),
    ]);
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Unknown action.']);
