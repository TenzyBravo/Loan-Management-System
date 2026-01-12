<?php
session_start();
include('db_connect.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Sanitize input
    $username = $conn->real_escape_string($username);
    
    // Check if this is an AJAX request
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    // Validate inputs
    if(empty($username) || empty($password)) {
        if($is_ajax) {
            echo json_encode([
                'success' => false,
                'message' => 'Please enter both username and password.'
            ]);
            exit;
        } else {
            $_SESSION['login_error'] = 'Please enter both username and password.';
            header('Location: customer_login.php');
            exit;
        }
    }
    
    // Check if user exists (can login with username or email)
    $query = "SELECT * FROM borrowers WHERE (username = '$username' OR email = '$username') AND status = 1";
    $result = $conn->query($query);
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        // Note: In production, use password_verify with hashed passwords
        // This currently checks plain text for compatibility
        $password_match = false;
        
        // Check if password is hashed (bcrypt starts with $2y$)
        if(substr($user['password'], 0, 4) === '$2y$') {
            // Use password_verify for hashed passwords
            $password_match = password_verify($password, $user['password']);
        } else {
            // Plain text comparison (for backward compatibility)
            $password_match = ($password === $user['password']);
        }
        
        if($password_match) {
            // Login successful
            $_SESSION['customer_id'] = $user['id'];
            $_SESSION['customer_name'] = $user['firstname'] . ' ' . $user['lastname'];
            $_SESSION['customer_email'] = $user['email'];
            $_SESSION['customer_username'] = $user['username'];
            $_SESSION['customer_firstname'] = $user['firstname'];
            $_SESSION['customer_lastname'] = $user['lastname'];
            
            // Remember me functionality
            if(isset($_POST['remember']) && $_POST['remember'] == 'yes') {
                setcookie('customer_id', $user['id'], time() + (86400 * 30), "/");
                setcookie('customer_remember', 'yes', time() + (86400 * 30), "/");
            }
            
            // Log activity
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_sql = "INSERT INTO activity_log (user_id, user_type, action, description, ip_address) 
                        VALUES ({$user['id']}, 'customer', 'login', 'Customer logged in', '$ip')";
            $conn->query($log_sql);
            
            // Return success based on request type
            if($is_ajax) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful!',
                    'redirect' => 'customer_dashboard.php'
                ]);
                exit;
            } else {
                header('Location: customer_dashboard.php');
                exit;
            }
        } else {
            // Incorrect password
            if($is_ajax) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Incorrect password. Please try again.'
                ]);
                exit;
            } else {
                $_SESSION['login_error'] = 'Incorrect password. Please try again.';
                header('Location: customer_login.php');
                exit;
            }
        }
    } else {
        // User not found
        if($is_ajax) {
            echo json_encode([
                'success' => false,
                'message' => 'Account not found or inactive. Please check your credentials.'
            ]);
            exit;
        } else {
            $_SESSION['login_error'] = 'Account not found or inactive. Please check your credentials.';
            header('Location: customer_login.php');
            exit;
        }
    }
} else {
    header('Location: customer_login.php');
    exit;
}
?>
