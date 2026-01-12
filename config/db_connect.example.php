<?php
/**
 * Example Database Connection Configuration — replace values in local copy
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_database_name');
define('DB_CHARSET', 'utf8mb4');

// System Configuration
define('BASE_URL', 'http://localhost/your_app/');
define('SITE_NAME', 'Your Loan System');
define('TIMEZONE', 'Africa/Lusaka');

// Currency Configuration
define('CURRENCY_SYMBOL', 'K');
define('CURRENCY_CODE', 'ZMW');
define('CURRENCY_NAME', 'Zambian Kwacha');

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'LOAN_SYS_SESSION');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
