<?php
/**
 * Database Connection
 * Brian Investments Loan System
 *
 * For production: Copy config.production.php to config.php and update credentials
 */

// Check if production config exists
if (file_exists(__DIR__ . '/config.php')) {
    // Production mode - use config.php
    require_once __DIR__ . '/config.php';
} else {
    // Development mode - use hardcoded values
    // WARNING: Change these for production!

    $environment = 'development';

    // Database settings
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'loan_db';

    // Error handling based on environment
    if ($environment === 'production') {
        error_reporting(0);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', __DIR__ . '/logs/php_errors.log');
    } else {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    // Create connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        if ($environment === 'production') {
            error_log('Database connection failed: ' . $conn->connect_error);
            die('Service temporarily unavailable.');
        } else {
            die('Could not connect to mysql: ' . $conn->connect_error);
        }
    }

    $conn->set_charset('utf8mb4');
}
