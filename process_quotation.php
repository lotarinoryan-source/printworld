<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/pdf_generator.php';
require_once 'includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: quotation.php'); exit; }

$customerName  = sanitizeInput($_POST['customer_name'] ?? '');
$companyName   = sanitizeInput($_POST['company_name'] ?? '');
$email         = sanitizeInput($_POST['email'] ?? '');
$contactNumber = sanitizeInput($_POST['contact_number'] ?? '');
$message       = sanitizeInput($_POST['message'] ?? '');
$itemsData     = $_POST['items_data'] ?? '[]';
$signLat       = sanitizeInput($_POST['sign_lat'] ?? '');
$signLng       = sanitizeInput($_POST['sign_lng'] ?? '');
$signAddress   = sanitizeInput($_POST['sign_address'] ?? '');

$errors = [];
if (empty($customerName))    $errors[] = 'Full name is required.';
if (!validateEmail($email))  $errors[] = 'Valid email address is required.';
if (empty($contactNumber))   $errors[] = 'Contact number is required.';

$items = json_decode($itemsData, true);
if (empty($items) || !is_array($items)) $errors[] = 'Please add at least one service to your request.';

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: quotation.php');
    exit;
}

// Handle design file upload
$designPath = null;
if (!empty($_FILES['design_file']['name'])) {
    $designPath = handleDesignUpload($_FILES['design_file']);
}

$db = db();
$requestNumber = generateRequestNumber();

// Insert quotation request
$stmt = $db->prepare("INSERT INTO quotation_requests
    (request_number, customer_name, company_name, email, contact_number, message, items_json, design_file, signage_lat, signage_lng, signage_address)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)");
$lat = $signLat ? (float)$signLat : null;
$lng = $signLng ? (float)$signLng : null;
$stmt->bind_param('ssssssssdds',
    $requestNumber, $customerName, $companyName, $email, $contactNumber,
    $message, $itemsData, $designPath, $lat, $lng, $signAddress
);
$stmt->execute();
$requestId = $db->insert_id;

// Insert request items
$iStmt = $db->prepare("INSERT INTO request_items (request_id, service_type, item_name, description, quantity, item_details) VALUES (?,?,?,?,?,?)");
foreach ($items as $item) {
    $stype   = sanitizeInput($item['service_type'] ?? $item['type'] ?? '');
    $iname   = sanitizeInput($item['item_name'] ?? $item['type'] ?? '');
    $desc    = sanitizeInput($item['description'] ?? '');
    $qty     = (int)($item['quantity'] ?? 1);
    $details = json_encode($item['details'] ?? []);
    $iStmt->bind_param('isssss', $requestId, $stype, $iname, $desc, $qty, $details);
    $iStmt->execute();
}

// Generate client-facing request PDF (no prices)
$requestData = [
    'request_number' => $requestNumber,
    'customer_name'  => $customerName,
    'company_name'   => $companyName,
    'email'          => $email,
    'contact_number' => $contactNumber,
    'message'        => $message,
];
$pdfPath = generateRequestPDF($requestData, $items);

// Save PDF path
$upd = $db->prepare("UPDATE quotation_requests SET request_pdf_path=? WHERE id=?");
$upd->bind_param('si', $pdfPath, $requestId);
$upd->execute();

// Send notification to admin
sendAdminNotification($requestData, $items, $pdfPath);

// Redirect to thank-you
header('Location: thank-you.php?r=' . urlencode($requestNumber));
exit;
