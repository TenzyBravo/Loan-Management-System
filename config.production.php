<?php
/**
 * Production Configuration for Brian Investments Loan System
 *
 * INSTRUCTIONS:
 * 1. Copy this file to 'config.php' on your production server
 * 2. Update all values marked with [CHANGE THIS]
 * 3. Ensure this file is NOT accessible from the web (store outside public_html if possible)
 * 4. Set proper file permissions (640 or 600)
 */

// ============================================================
// ENVIRONMENT DETECTION
// ============================================================
define('ENVIRONMENT', 'production'); // 'development' or 'production'

// ============================================================
// DATABASE CONFIGURATION [CHANGE THIS]
// ============================================================
define('DB_HOST', 'localhost');           // Database host
define('DB_USER', 'your_db_user');        // [CHANGE THIS] Database username
define('DB_PASS', 'your_secure_password'); // [CHANGE THIS] Database password
define('DB_NAME', 'loan_db');             // Database name
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// SITE CONFIGURATION [CHANGE THIS]
// ============================================================
define('BASE_URL', 'https://yourdomain.com/'); // [CHANGE THIS] Your domain with HTTPS
define('SITE_NAME', 'Brian Investments');
define('TIMEZONE', 'Africa/Lusaka');

// ============================================================
// CURRENCY CONFIGURATION
// ============================================================
define('CURRENCY_SYMBOL', 'K');
define('CURRENCY_CODE', 'ZMW');
define('CURRENCY_NAME', 'Zambian Kwacha');

// ============================================================
// SECURITY CONFIGURATION
// ============================================================
define('FORCE_HTTPS', true);              // Redirect HTTP to HTTPS
define('SESSION_LIFETIME', 1800);         // 30 minutes (shorter for security)
define('SESSION_NAME', 'BRIAN_INV_SESS');
define('CSRF_ENABLED', true);
define('PASSWORD_COST', 12);              // bcrypt cost factor

// ============================================================
// FILE UPLOAD CONFIGURATION
// ============================================================
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5242880);         // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// ============================================================
// ERROR HANDLING - PRODUCTION SETTINGS
// ============================================================
if (ENVIRONMENT === 'production') {
    // Disable error display to users
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);

    // Log errors to file instead
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php_errors.log');
} else {
    // Development - show errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ============================================================
// SET TIMEZONE
// ============================================================
date_default_timezone_set(TIMEZONE);

// ============================================================
// HTTPS ENFORCEMENT
// ============================================================
if (FORCE_HTTPS && ENVIRONMENT === 'production') {
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect);
        exit();
    }
}

// ============================================================
// SECURITY HEADERS
// ============================================================
if (ENVIRONMENT === 'production') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Uncomment when you have HTTPS configured:
    // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ============================================================
// DATABASE CONNECTION
// ============================================================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    if (ENVIRONMENT === 'production') {
        // Log error but don't show details to user
        error_log('Database connection failed: ' . $conn->connect_error);
        die('Service temporarily unavailable. Please try again later.');
    } else {
        die('Database connection failed: ' . $conn->connect_error);
    }
}

$conn->set_charset(DB_CHARSET);

// ============================================================
// CREATE REQUIRED DIRECTORIES
// ============================================================
$required_dirs = [
    __DIR__ . '/logs',
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/documents',
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Protect logs directory
$htaccess_logs = __DIR__ . '/logs/.htaccess';
if (!file_exists($htaccess_logs)) {
    file_put_contents($htaccess_logs, "Deny from all\n");
}
