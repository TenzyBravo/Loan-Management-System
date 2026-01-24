<?php
/**
 * Password Migration Script
 * Upgrades all legacy passwords to bcrypt hashing
 *
 * RUN THIS ONCE before going to production!
 *
 * Usage: php upgrade_passwords.php
 * Or access via browser (then DELETE this file after running)
 */

// Security check - remove this file after running!
$allowed_ips = ['127.0.0.1', '::1']; // localhost only
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_ips) && php_sapi_name() !== 'cli') {
    die('Access denied. Run from localhost or CLI only.');
}

require_once __DIR__ . '/../db_connect.php';

echo "<pre>\n";
echo "===========================================\n";
echo "Password Migration Script\n";
echo "Brian Investments Loan System\n";
echo "===========================================\n\n";

// Migrate admin/staff users
echo "Migrating USERS table...\n";
$result = $conn->query("SELECT id, username, password FROM users");
$migrated = 0;
$already_hashed = 0;
$errors = 0;

while ($user = $result->fetch_assoc()) {
    // Check if already hashed (bcrypt starts with $2)
    if (strpos($user['password'], '$2') === 0) {
        $already_hashed++;
        echo "  [SKIP] User #{$user['id']} ({$user['username']}) - already hashed\n";
        continue;
    }

    // Hash the current password (assuming it's plain text or MD5)
    // For MD5 passwords, users will need to reset
    // For plain text, we can hash directly
    if (strlen($user['password']) === 32 && ctype_xdigit($user['password'])) {
        // This is likely an MD5 hash - cannot migrate, user needs to reset
        echo "  [WARN] User #{$user['id']} ({$user['username']}) - MD5 detected, needs password reset\n";
        $errors++;
        continue;
    }

    // Plain text password - hash it
    $hashed = password_hash($user['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $user['id']);

    if ($stmt->execute()) {
        $migrated++;
        echo "  [OK] User #{$user['id']} ({$user['username']}) - password hashed\n";
    } else {
        $errors++;
        echo "  [ERROR] User #{$user['id']} ({$user['username']}) - failed to update\n";
    }
    $stmt->close();
}

echo "\nUsers Summary: {$migrated} migrated, {$already_hashed} already hashed, {$errors} errors\n\n";

// Migrate customer/borrowers if they have passwords
echo "Migrating BORROWERS table...\n";
$result = $conn->query("SELECT id, username, password FROM borrowers WHERE password IS NOT NULL AND password != ''");
$migrated_b = 0;
$already_hashed_b = 0;
$errors_b = 0;

if ($result && $result->num_rows > 0) {
    while ($borrower = $result->fetch_assoc()) {
        if (strpos($borrower['password'], '$2') === 0) {
            $already_hashed_b++;
            echo "  [SKIP] Borrower #{$borrower['id']} ({$borrower['username']}) - already hashed\n";
            continue;
        }

        if (strlen($borrower['password']) === 32 && ctype_xdigit($borrower['password'])) {
            echo "  [WARN] Borrower #{$borrower['id']} ({$borrower['username']}) - MD5 detected, needs password reset\n";
            $errors_b++;
            continue;
        }

        $hashed = password_hash($borrower['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $conn->prepare("UPDATE borrowers SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $borrower['id']);

        if ($stmt->execute()) {
            $migrated_b++;
            echo "  [OK] Borrower #{$borrower['id']} ({$borrower['username']}) - password hashed\n";
        } else {
            $errors_b++;
            echo "  [ERROR] Borrower #{$borrower['id']} ({$borrower['username']}) - failed to update\n";
        }
        $stmt->close();
    }
} else {
    echo "  No borrower passwords to migrate.\n";
}

echo "\nBorrowers Summary: {$migrated_b} migrated, {$already_hashed_b} already hashed, {$errors_b} errors\n\n";

echo "===========================================\n";
echo "Migration Complete!\n";
echo "===========================================\n\n";

echo "IMPORTANT: Delete this file after running!\n";
echo "File location: " . __FILE__ . "\n";
echo "</pre>\n";
?>
