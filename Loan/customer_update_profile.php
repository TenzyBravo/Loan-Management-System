<?php
session_start();
include('db_connect.php');

if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $customer_id = $_SESSION['customer_id'];
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $middlename = $conn->real_escape_string($_POST['middlename']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $email = $conn->real_escape_string($_POST['email']);
    $contact_no = $conn->real_escape_string($_POST['contact_no']);
    $address = $conn->real_escape_string($_POST['address']);
    $tax_id = $conn->real_escape_string($_POST['tax_id']);
    
    // Check if email is already used by another customer
    $check_email = $conn->query("SELECT id FROM borrowers WHERE email = '$email' AND id != $customer_id");
    if($check_email->num_rows > 0) {
        $_SESSION['error_msg'] = 'Email address is already in use by another account.';
        header('Location: customer_profile.php');
        exit;
    }
    
    // Update profile
    $sql = "UPDATE borrowers SET 
            firstname = '$firstname',
            middlename = '$middlename',
            lastname = '$lastname',
            email = '$email',
            contact_no = '$contact_no',
            address = '$address',
            tax_id = '$tax_id',
            last_updated = NOW(),
            updated_by = $customer_id,
            profile_complete = 1
            WHERE id = $customer_id";
    
    if($conn->query($sql)) {
        // Update session name
        $_SESSION['customer_name'] = $firstname . ' ' . $lastname;
        $_SESSION['customer_email'] = $email;
        
        // Log activity
        $ip = $_SERVER['REMOTE_ADDR'];
        $conn->query("INSERT INTO activity_log (user_id, user_type, action, description, target_id, target_type, ip_address) 
                     VALUES ($customer_id, 'customer', 'profile_update', 'Updated profile information', $customer_id, 'borrower', '$ip')");
        
        $_SESSION['success_msg'] = 'Profile updated successfully!';
    } else {
        $_SESSION['error_msg'] = 'Error updating profile: ' . $conn->error;
    }
    
    header('Location: customer_profile.php');
    exit;
    
} else {
    header('Location: customer_profile.php');
    exit;
}
?>
