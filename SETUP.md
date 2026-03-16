# PrintCraft — Setup Guide

## Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB
- Apache with mod_rewrite
- Composer

## Installation

### 1. Install Dependencies
```bash
cd printing-shop
composer install
```

### 2. Database Setup
Import the schema:
```bash
mysql -u root -p < database.sql
```

### 3. Configure
Edit `config.php`:
- Set DB credentials
- Set SMTP credentials (use Gmail App Password)
- Set your Google Maps API Key
- Set SITE_URL to your domain

### 4. Permissions
```bash
chmod -R 755 uploads/
```

### 5. Admin Login
- URL: `/admin/login.php`
- Username: `admin`
- Password: `password` (change immediately via phpMyAdmin)

To generate a new password hash:
```php
echo password_hash('your_new_password', PASSWORD_DEFAULT);
```

## Google Maps API
1. Go to https://console.cloud.google.com
2. Enable: Maps JavaScript API + Geocoding API
3. Copy API key to config.php

## Gmail SMTP
1. Enable 2FA on your Google account
2. Generate an App Password (Google Account > Security > App Passwords)
3. Use that as SMTP_PASS in config.php

## Folder Structure
```
printing-shop/
├── index.php           # Landing page
├── quotation.php       # Quotation form
├── process_quotation.php
├── thank-you.php
├── download.php        # PDF download handler
├── config.php          # Configuration
├── database.sql        # DB schema
├── composer.json
├── .htaccess
├── admin/              # Admin panel
│   ├── login.php
│   ├── dashboard.php
│   ├── quotations.php
│   ├── prices.php
│   ├── services.php
│   ├── gallery.php
│   └── content.php
├── includes/
│   ├── db.php
│   ├── auth.php
│   ├── functions.php
│   ├── pdf_generator.php
│   └── mailer.php
├── assets/
│   ├── css/style.css
│   └── js/
│       ├── main.js
│       ├── quotation.js
│       └── maps.js
└── uploads/            # Auto-created
    ├── gallery/
    ├── designs/
    └── quotations/
```
