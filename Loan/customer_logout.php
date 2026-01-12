<?php
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Delete remember me cookies
if(isset($_COOKIE['customer_id'])) {
    setcookie('customer_id', '', time() - 3600, '/');
}
if(isset($_COOKIE['customer_remember'])) {
    setcookie('customer_remember', '', time() - 3600, '/');
}

// Redirect to login page
header('Location: customer_login.php');
exit;
?>
