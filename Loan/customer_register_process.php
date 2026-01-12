<?php
session_start();
include('db_connect.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $middlename = $conn->real_escape_string($_POST['middlename']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $email = $conn->real_escape_string($_POST['email']);
    $contact_no = $conn->real_escape_string($_POST['contact_no']);
    $address = $conn->real_escape_string($_POST['address']);
    $tax_id = $conn->real_escape_string($_POST['tax_id']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password']; // In production, use password_hash()
    
    // Validate passwords match
    if($password !== $_POST['confirm_password']) {
        $_SESSION['error_msg'] = 'Passwords do not match!';
        header('Location: customer_register.php');
        exit;
    }
    
    // Check if username already exists
    $check_username = $conn->query("SELECT id FROM borrowers WHERE username = '$username'");
    if($check_username->num_rows > 0) {
        $_SESSION['error_msg'] = 'Username already exists. Please choose another.';
        header('Location: customer_register.php');
        exit;
    }
    
    // Check if email already exists
    $check_email = $conn->query("SELECT id FROM borrowers WHERE email = '$email'");
    if($check_email->num_rows > 0) {
        $_SESSION['error_msg'] = 'Email already registered. Please use another email or login.';
        header('Location: customer_register.php');
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = 'assets/uploads/customer_documents/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // File upload handling
    $uploaded_files = array();
    $file_fields = array(
        'id_document' => 'id',
        'employment_proof' => 'employment_proof',
        'payslip' => 'payslip'
    );
    
    $upload_errors = array();
    
    foreach($file_fields as $field => $doc_type) {
        if(isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
            $file = $_FILES[$field];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validate file extension
            $allowed_ext = array('jpg', 'jpeg', 'png', 'pdf');
            if(!in_array($file_ext, $allowed_ext)) {
                $upload_errors[] = "Invalid file type for $field. Only JPG, PNG, and PDF allowed.";
                continue;
            }
            
            // Validate file size (5MB max)
            if($file_size > 5242880) {
                $upload_errors[] = "File size for $field exceeds 5MB limit.";
                continue;
            }
            
            // Generate unique filename
            $new_filename = $doc_type . '_' . time() . '_' . uniqid() . '.' . $file_ext;
            $file_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if(move_uploaded_file($file_tmp, $file_path)) {
                $uploaded_files[$doc_type] = array(
                    'name' => $new_filename,
                    'path' => $file_path,
                    'size' => $file_size
                );
            } else {
                $upload_errors[] = "Failed to upload $field.";
            }
        } else {
            $upload_errors[] = "Required document missing: $field";
        }
    }
    
    // Check if there were any upload errors
    if(!empty($upload_errors)) {
        $_SESSION['error_msg'] = 'Upload errors: ' . implode(', ', $upload_errors);
        header('Location: customer_register.php');
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert borrower record
        $sql = "INSERT INTO borrowers (firstname, middlename, lastname, contact_no, address, email, tax_id, username, password, status, date_created) 
                VALUES ('$firstname', '$middlename', '$lastname', '$contact_no', '$address', '$email', '$tax_id', '$username', '$password', 1, " . time() . ")";
        
        if(!$conn->query($sql)) {
            throw new Exception('Error creating account: ' . $conn->error);
        }
        
        $borrower_id = $conn->insert_id;
        
        // Insert document records
        foreach($uploaded_files as $doc_type => $file_info) {
            $doc_sql = "INSERT INTO borrower_documents (borrower_id, document_type, file_name, file_path, file_size, status) 
                        VALUES ($borrower_id, '$doc_type', '{$file_info['name']}', '{$file_info['path']}', {$file_info['size']}, 0)";
            
            if(!$conn->query($doc_sql)) {
                throw new Exception('Error saving documents: ' . $conn->error);
            }
        }
        
        // Create welcome notification
        $notif_sql = "INSERT INTO customer_notifications (borrower_id, title, message, type) 
                      VALUES ($borrower_id, 'Welcome!', 'Your account has been created successfully. Your documents are being reviewed by our team.', 'success')";
        $conn->query($notif_sql);
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['success_msg'] = 'Application submitted successfully! You can now login with your credentials.';
        header('Location: customer_login.php');
        exit;
        
    } catch(Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Delete uploaded files
        foreach($uploaded_files as $file_info) {
            if(file_exists($file_info['path'])) {
                unlink($file_info['path']);
            }
        }
        
        $_SESSION['error_msg'] = 'Registration failed: ' . $e->getMessage();
        header('Location: customer_register.php');
        exit;
    }
    
} else {
    header('Location: customer_register.php');
    exit;
}
?>
