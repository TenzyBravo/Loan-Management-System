<?php
// Simple test script to debug login
session_start();

// Test if Security class loads properly
require_once 'includes/security.php';
require_once 'includes/database.php';

echo "Security class loaded: " . (class_exists('Security') ? 'YES' : 'NO') . "\n";
echo "Database class loaded: " . (class_exists('Database') ? 'YES' : 'NO') . "\n";
echo "Session started: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "\n";

// Test database connection
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "Database connected: YES\n";

    // Test if users table exists
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Users in database: " . $row['count'] . "\n";
    }

    // Test a sample user query (replace with your actual admin username)
    $testUsername = 'admin'; // Change this to your admin username
    $stmt = $conn->prepare("SELECT id, username, type FROM users WHERE username = ?");
    $stmt->bind_param("s", $testUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "\nTest user '$testUsername' found in database\n";
        $user = $result->fetch_assoc();
        echo "User ID: " . $user['id'] . "\n";
        echo "User Type: " . $user['type'] . "\n";
    } else {
        echo "\nTest user '$testUsername' NOT found in database\n";
    }

} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
