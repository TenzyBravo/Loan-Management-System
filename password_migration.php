<?php
/**
 * Password Migration Script
 * Upgrades existing plain-text passwords to bcrypt hashes
 * 
 * IMPORTANT: Run this script ONCE after deploying the security updates
 * 
 * Usage: Access this file via browser or run from command line
 *        php password_migration.php
 */

// Prevent accidental execution in production
// Comment out this line to run the migration
// die("Remove this line to run the migration");

require_once __DIR__ . '/includes/security.php';

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'loan_db';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "===========================================\n";
echo "Password Migration Script\n";
echo "===========================================\n\n";

// Check if running from CLI or browser
$isCLI = php_sapi_name() === 'cli';
$br = $isCLI ? "\n" : "<br>";

// Get all users
$result = $conn->query("SELECT id, username, password FROM users");

if ($result->num_rows === 0) {
    echo "No users found in the database.{$br}";
    exit;
}

echo "Found {$result->num_rows} users.{$br}{$br}";

$migrated = 0;
$skipped = 0;
$errors = 0;

while ($user = $result->fetch_assoc()) {
    $userId = $user['id'];
    $username = $user['username'];
    $currentPassword = $user['password'];
    
    // Check if already hashed (bcrypt hashes start with $2)
    if (strpos($currentPassword, '$2') === 0) {
        echo "User '{$username}' (ID: {$userId}): Already using bcrypt - SKIPPED{$br}";
        $skipped++;
        continue;
    }
    
    // Check if using MD5 (32 character hex string)
    if (preg_match('/^[a-f0-9]{32}$/i', $currentPassword)) {
        echo "User '{$username}' (ID: {$userId}): Using MD5 - Cannot migrate automatically{$br}";
        echo "  -> User will need to reset password{$br}";
        $skipped++;
        continue;
    }
    
    // Hash the plain text password
    $hashedPassword = Security::hashPassword($currentPassword);
    
    // Update the password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    
    if ($stmt->execute()) {
        echo "User '{$username}' (ID: {$userId}): Password migrated successfully{$br}";
        $migrated++;
    } else {
        echo "User '{$username}' (ID: {$userId}): ERROR - {$stmt->error}{$br}";
        $errors++;
    }
    
    $stmt->close();
}

echo "{$br}===========================================\n";
echo "Migration Summary{$br}";
echo "==========================================={$br}";
echo "Migrated: {$migrated}{$br}";
echo "Skipped: {$skipped}{$br}";
echo "Errors: {$errors}{$br}";
echo "==========================================={$br}{$br}";

if ($migrated > 0) {
    echo "SUCCESS: {$migrated} passwords have been upgraded to bcrypt.{$br}";
    echo "Users can continue using their existing passwords.{$br}";
}

if ($errors > 0) {
    echo "WARNING: {$errors} errors occurred. Please check the log above.{$br}";
}

// Log the migration
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'event' => 'password_migration',
    'migrated' => $migrated,
    'skipped' => $skipped,
    'errors' => $errors
];

$logFile = __DIR__ . '/logs/migration.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);

$conn->close();

echo "{$br}Migration complete. Log saved to logs/migration.log{$br}";
?>
