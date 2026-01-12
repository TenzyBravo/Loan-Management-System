<?php
session_start();
include('db_connect.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Sanitize input
    $username = $conn->real_escape_string($username);
    
    // Check if user exists (can login with username or email)
    $query = "SELECT * FROM borrowers WHERE (username = '$username' OR email = '$username') AND status = 1";
    $result = $conn->query($query);
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password (in production, use password_verify with hashed passwords)
        // For now, plain text comparison
        if($password === $user['password']) {
            // Login successful
            $_SESSION['customer_id'] = $user['id'];
            $_SESSION['customer_name'] = $user['firstname'] . ' ' . $user['lastname'];
            $_SESSION['customer_email'] = $user['email'];
            $_SESSION['customer_username'] = $user['username'];
            
            // Remember me functionality
            if(isset($_POST['remember'])) {
                setcookie('customer_id', $user['id'], time() + (86400 * 30), "/");
                setcookie('customer_remember', 'yes', time() + (86400 * 30), "/");
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
} else {
    header('Location: customer_login.php');
    exit;
}
?>
