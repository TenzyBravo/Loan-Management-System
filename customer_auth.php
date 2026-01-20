<?php
session_start();
include('db_connect.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

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
            // Login successful
            $_SESSION['customer_id'] = $user['id'];
            $_SESSION['customer_name'] = $user['firstname'] . ' ' . $user['lastname'];
            $_SESSION['customer_email'] = $user['email'];
            $_SESSION['customer_username'] = $user['username'];

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
            $_SESSION['login_error'] = 'Incorrect password. Please try again.';
            header('Location: customer_login.php');
            exit;
        }
    } else {
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
