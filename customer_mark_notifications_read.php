<?php
/**
 * Mark all customer notifications as read
 */
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: customer_login.php');
    exit;
}

include('db_connect.php');
include('includes/notifications.php');

$customer_id = $_SESSION['customer_id'];

// Mark all notifications as read
mark_all_customer_notifications_read($conn, $customer_id);

// Redirect back to previous page or dashboard
$redirect = $_SERVER['HTTP_REFERER'] ?? 'customer_dashboard.php';
header('Location: ' . $redirect);
exit;
