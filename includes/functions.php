<?php
require_once __DIR__ . '/db.php';

function getSiteContent(string $key, string $default = ''): string {
    $db = db();
    $stmt = $db->prepare("SELECT content_value FROM site_content WHERE content_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? htmlspecialchars_decode($row['content_value']) : $default;
}

function getAllPrices(): array {
    $db = db();
    $result = $db->query("SELECT * FROM prices ORDER BY id");
    $prices = [];
    while ($row = $result->fetch_assoc()) $prices[$row['item_key']] = $row;
    return $prices;
}

function getPremiumClients(): array {
    $db = db();
    $result = $db->query("SELECT * FROM premium_clients WHERE is_active=1 ORDER BY company_name");
    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    return $list;
}

function generateRequestNumber(): string {
    return 'RQ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

function generateQuotationNumber(): string {
    return 'QT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

function sanitizeInput(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)));
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function handleFileUpload(array $file, string $subdir = 'gallery'): string|false {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'], $allowed) || $file['size'] > MAX_FILE_SIZE) return false;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $dir = UPLOAD_DIR . $subdir . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $dest = $dir . uniqid('img_') . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $dest) ? 'uploads/' . $subdir . '/' . basename($dest) : false;
}

function handleDesignUpload(array $file): string|false {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
    if (!in_array($file['type'], $allowed) || $file['size'] > MAX_FILE_SIZE) return false;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $dir = UPLOAD_DIR . 'designs/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $dest = $dir . uniqid('design_') . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $dest) ? 'uploads/designs/' . basename($dest) : false;
}

function getGalleryImages(int $limit = 0): array {
    $db = db();
    $sql = "SELECT * FROM gallery ORDER BY sort_order ASC, created_at DESC" . ($limit > 0 ? " LIMIT $limit" : "");
    $result = $db->query($sql);
    $images = [];
    while ($row = $result->fetch_assoc()) $images[] = $row;
    return $images;
}

function getActiveServices(): array {
    $db = db();
    $result = $db->query("SELECT * FROM service_categories WHERE is_active=1 ORDER BY sort_order");
    $services = [];
    while ($row = $result->fetch_assoc()) $services[] = $row;
    return $services;
}

function formatCurrency(float $amount): string {
    return '₱' . number_format($amount, 2);
}
