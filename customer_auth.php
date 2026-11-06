<?php
session_start();
include('db_connect.php');
include('includes/security.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check rate limiting before processing login
    $rateLimit = Security::checkLoginRateLimit($conn, 5, 15);
    if (!$rateLimit['allowed']) {
        // Try to get user email for notification (only if username was provided)
        if (!empty($username)) {
            $emailStmt = $conn->prepare("SELECT email, firstname, lastname FROM borrowers WHERE (username = ? OR email = ?) LIMIT 1");
            $emailStmt->bind_param("ss", $username, $username);
            $emailStmt->execute();
            $userResult = $emailStmt->get_result();
            if ($userResult->num_rows > 0) {
                $userData = $userResult->fetch_assoc();
                Security::sendLockoutNotification(
                    $conn,
                    $userData['email'],
                    $userData['firstname'] . ' ' . $userData['lastname'],
                    15,
                    false
                );
            }
            $emailStmt->close();
        }

        $lockedUntil = $rateLimit['locked_until'] ? date('g:i A', strtotime($rateLimit['locked_until'])) : '';
        $_SESSION['login_error'] = "Too many failed login attempts. Please try again after $lockedUntil.";
        header('Location: customer_login.php');
        exit;
    }

    // Prepare statement to check if user exists (can login with username or email)
    $stmt = $conn->prepare("SELECT * FROM borrowers WHERE (username = ? OR email = ?) AND status = 1");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if password is hashed (bcrypt starts with $2y or $2a)
        $isHashed = strpos($user['password'], '$2') === 0;

        $passwordValid = false;

        if ($isHashed) {
            // Verify hashed password
            $passwordValid = password_verify($password, $user['password']);
        } else {
            // Legacy plain text password check (for migration)
            $passwordValid = ($password === $user['password']);

            // If valid, upgrade to hashed password
            if ($passwordValid) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE borrowers SET password = ? WHERE id = ?");
                $updateStmt->bind_param("si", $hashedPassword, $user['id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }

        if($passwordValid) {
            // Record successful login attempt (clears failed attempts)
            Security::recordLoginAttempt($conn, $username, true);

            // Update last login timestamp and IP
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $updateLogin = $conn->prepare("UPDATE borrowers SET last_login = NOW(), last_login_ip = ? WHERE id = ?");
            if ($updateLogin) {
                $updateLogin->bind_param("si", $ip, $user['id']);
                @$updateLogin->execute();
                @$updateLogin->close();
            }

            // Login successful
            $_SESSION['customer_id'] = $user['id'];
            $_SESSION['customer_name'] = $user['firstname'] . ' ' . $user['lastname'];
            $_SESSION['customer_email'] = $user['email'];
            $_SESSION['customer_username'] = $user['username'];
            $_SESSION['customer_last_login'] = $user['last_login'] ?? null;

            // Remember me functionality - secure cookies
            if(isset($_POST['remember'])) {
                // Generate secure token instead of storing user ID directly
                $rememberToken = bin2hex(random_bytes(32));

                // Store token in database (you should create a remember_tokens table)
                // For now, we'll use a more secure cookie approach

                $cookieOptions = [
                    'expires' => time() + (86400 * 30),
                    'path' => '/',
                    'httponly' => true,  // Prevents JavaScript access
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',  // Only send over HTTPS
                    'samesite' => 'Strict'  // Prevents CSRF attacks
                ];

                setcookie('customer_remember_token', $rememberToken, $cookieOptions);
                setcookie('customer_remember', 'yes', $cookieOptions);
            }

            // Redirect to dashboard
            header('Location: customer_dashboard.php');
            exit;
        } else {
            // Record failed login attempt
            Security::recordLoginAttempt($conn, $username, false);

            // Check remaining attempts
            $rateLimit = Security::checkLoginRateLimit($conn, 5, 15);
            $remaining = $rateLimit['remaining'];

            $errorMsg = 'Incorrect password. Please try again.';
            if ($remaining <= 3 && $remaining > 0) {
                $errorMsg .= " ($remaining attempts remaining)";
            }

            $_SESSION['login_error'] = $errorMsg;
            header('Location: customer_login.php');
            exit;
        }
    } else {
        // Record failed login attempt for non-existent user
        Security::recordLoginAttempt($conn, $username, false);

        $_SESSION['login_error'] = 'Account not found or inactive. Please check your credentials.';
        header('Location: customer_login.php');
        exit;
    }
    $stmt->close();
} else {
    header('Location: customer_login.php');
    exit;
}
?>
