<?php
/**
 * Free Hosting Configuration - Brian Investments Loan System
 *
 * FOR FREEHOSTING.ORG / INFINITYFREE / 000WEBHOST / SIMILAR
 *
 * STEPS:
 * 1. Upload all files to htdocs or public_html
 * 2. Create database in cPanel → MySQL Databases
 * 3. Import database/loan_db.sql via phpMyAdmin
 * 4. Edit the values below with YOUR database details
 * 5. Rename this file to: config.php
 */

// ============================================================
// DATABASE - GET THESE FROM YOUR CPANEL
// ============================================================
$db_host = 'sql123.freehosting.org';  // CHECK YOUR CPANEL - often NOT localhost!
$db_user = 'your_username';            // Your cPanel/database username
$db_pass = 'your_password';            // Your database password
$db_name = 'your_database';            // Your database name

// ============================================================
// SITE URL - CHANGE TO YOUR FREE HOSTING URL
// ============================================================
$base_url = 'http://yoursite.freehosting.org/';

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
