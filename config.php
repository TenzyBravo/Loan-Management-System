<?php
/**
 * Free Hosting Configuration - Brian Investments Loan System
 * Host: yzz.me (InfinityFree)
 */

// ============================================================
// DATABASE CONFIGURATION
// ============================================================
$db_host = 'sql203.yzz.me';
$db_user = 'yzzme_41042304';
$db_pass = 'YOUR_VPANEL_PASSWORD_HERE';  // <-- PUT YOUR VPANEL PASSWORD HERE
$db_name = 'yzzme_41042304_loan_db';

// ============================================================
// DO NOT EDIT BELOW THIS LINE
// ============================================================

// Production settings - hide errors from users
error_reporting(0);
ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('Africa/Lusaka');

// Database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die('Database connection failed. Please check your config.php settings.');
}

$conn->set_charset('utf8mb4');
