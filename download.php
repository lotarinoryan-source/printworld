<?php
require_once 'config.php';

$file = basename($_GET['file'] ?? '');
if (empty($file)) exit('Invalid request.');

// Only allow PDF files
if (pathinfo($file, PATHINFO_EXTENSION) !== 'pdf') {
    http_response_code(403);
    exit('Forbidden.');
}

$path = UPLOAD_DIR . 'quotations/' . $file;

if (!file_exists($path) || !is_file($path)) {
    http_response_code(404);
    exit('File not found.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
