<?php
session_start();
include('db_connect.php');

if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Handle file upload
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document'])) {
    
    $document_type = $conn->real_escape_string($_POST['document_type']);
    $reason = isset($_POST['reason']) ? $conn->real_escape_string($_POST['reason']) : 'Document upload';
    
    $file = $_FILES['document'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Check for upload errors
    if($file_error !== UPLOAD_ERR_OK) {
        $_SESSION['error_msg'] = 'File upload failed. Please try again.';
        header('Location: customer_my_documents.php');
        exit;
    }
    
    // Validate file type
    $allowed_ext = array('jpg', 'jpeg', 'png', 'pdf');
    if(!in_array($file_ext, $allowed_ext)) {
        $_SESSION['error_msg'] = 'Invalid file type. Only JPG, PNG, and PDF allowed.';
        header('Location: customer_my_documents.php');
        exit;
    }
    
    // Validate file size (5MB max)
    if($file_size > 5242880) {
        $_SESSION['error_msg'] = 'File size exceeds 5MB limit.';
        header('Location: customer_my_documents.php');
        exit;
    }
    
    // Upload directory
    $upload_dir = 'assets/uploads/customer_documents/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $new_filename = $document_type . '_' . $customer_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
    $file_path = $upload_dir . $new_filename;
    
    // Check if document of this type already exists
    $existing_doc = $conn->query("SELECT * FROM borrower_documents 
                                  WHERE borrower_id = $customer_id 
                                  AND document_type = '$document_type'")->fetch_assoc();
    
    try {
        // Move uploaded file
        if(!move_uploaded_file($file_tmp, $file_path)) {
            throw new Exception('Failed to upload file. Please try again.');
        }
        
        if($existing_doc) {
            // Update existing document
            $old_path = $existing_doc['file_path'];
            
            $sql = "UPDATE borrower_documents SET 
                    file_name = '$new_filename',
                    file_path = '$file_path',
                    file_size = $file_size,
                    upload_date = NOW(),
                    status = 0,
                    verified_by = NULL,
                    verification_date = NULL,
                    verification_notes = NULL
                    WHERE borrower_id = $customer_id AND document_type = '$document_type'";
            
            if(!$conn->query($sql)) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            // Delete old file if it exists
            if(file_exists($old_path) && $old_path != $file_path) {
                @unlink($old_path);
            }
            
            $_SESSION['success_msg'] = 'Document updated successfully! It will be reviewed shortly.';
            
        } else {
            // Insert new document
            $sql = "INSERT INTO borrower_documents (borrower_id, document_type, file_name, file_path, file_size, status, upload_date) 
                    VALUES ($customer_id, '$document_type', '$new_filename', '$file_path', $file_size, 0, NOW())";
            
            if(!$conn->query($sql)) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            $_SESSION['success_msg'] = 'Document uploaded successfully! It will be reviewed shortly.';
        }
        
    } catch(Exception $e) {
        // Delete uploaded file on error
        if(file_exists($file_path)) {
            @unlink($file_path);
        }
        
        $_SESSION['error_msg'] = 'Error: ' . $e->getMessage();
    }
    
    header('Location: customer_my_documents.php');
    exit;
    
} else {
    // No file uploaded
    header('Location: customer_my_documents.php');
    exit;
}
?>
