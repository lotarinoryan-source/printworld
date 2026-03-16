<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'printing_shop');

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('SMTP_FROM_NAME', 'Printworld');

// Admin notification email
define('ADMIN_EMAIL', 'your_email@gmail.com');

// Google Maps API Key
define('GOOGLE_MAPS_API_KEY', 'YOUR_GOOGLE_MAPS_API_KEY');

// Site Config
define('SITE_NAME', 'Printworld');
define('SITE_URL', 'http://localhost/printcraft');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Contact Info
define('CONTACT_PHONE', '09XXXXXXXXX');
define('CONTACT_EMAIL', 'your_email@gmail.com');
define('CONTACT_FACEBOOK', 'https://www.facebook.com/YourPage');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
