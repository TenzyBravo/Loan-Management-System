<?php
session_start();
include('db_connect.php');

if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $customer_id = $_SESSION['customer_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords match
    if($new_password !== $confirm_password) {
        $_SESSION['error_msg'] = 'New passwords do not match!';
        header('Location: customer_profile.php');
        exit;
    }
    
    // Get current password from database
    $customer = $conn->query("SELECT password FROM borrowers WHERE id = $customer_id")->fetch_assoc();
    
    // Verify current password (in production, use password_verify)
    if($current_password !== $customer['password']) {
        $_SESSION['error_msg'] = 'Current password is incorrect!';
        header('Location: customer_profile.php');
        exit;
    }
    
    // Update password (in production, use password_hash)
    $sql = "UPDATE borrowers SET password = '$new_password', last_updated = NOW() WHERE id = $customer_id";
    
    if($conn->query($sql)) {
        // Log activity
        $ip = $_SERVER['REMOTE_ADDR'];
        $conn->query("INSERT INTO activity_log (user_id, user_type, action, description, target_id, target_type, ip_address) 
                     VALUES ($customer_id, 'customer', 'password_change', 'Changed password', $customer_id, 'borrower', '$ip')");
        
        $_SESSION['success_msg'] = 'Password changed successfully!';
    } else {
        $_SESSION['error_msg'] = 'Error changing password: ' . $conn->error;
    }
    
    header('Location: customer_profile.php');
    exit;
    
} else {
    header('Location: customer_profile.php');
    exit;
}
?>
