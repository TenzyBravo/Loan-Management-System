<?php
/**
 * Customer Document Update Handler
 * Handles document uploads for existing customers
 */

session_start();
require_once 'db_connect.php';

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: customer_login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $document_type = $_POST['document_type'] ?? '';
    $allowed_types = ['id', 'employment_proof', 'payslip'];

    if (!in_array($document_type, $allowed_types)) {
        $_SESSION['error_msg'] = 'Invalid document type';
        header('Location: customer_my_documents.php');
        exit;
    }

    // Check if file was uploaded
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File is too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File is too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
        ];
        $error_code = $_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE;
        $_SESSION['error_msg'] = $error_messages[$error_code] ?? 'File upload failed';
        header('Location: customer_my_documents.php');
        exit;
    }

    $file = $_FILES['document'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Validate file extension
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($file_ext, $allowed_extensions)) {
        $_SESSION['error_msg'] = 'Invalid file type. Allowed: PDF, JPG, PNG';
        header('Location: customer_my_documents.php');
        exit;
    }

    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024;
    if ($file_size > $max_size) {
        $_SESSION['error_msg'] = 'File is too large. Maximum size is 5MB';
        header('Location: customer_my_documents.php');
        exit;
    }

    // Create upload directory if needed
    $upload_dir = 'assets/uploads/customer_documents/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $new_filename = $document_type . '_' . $customer_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
    $file_path = $upload_dir . $new_filename;

    // Move uploaded file
    if (move_uploaded_file($file_tmp, $file_path)) {

        // Check if document of this type already exists
        $stmt = $conn->prepare("SELECT id, file_path FROM borrower_documents WHERE borrower_id = ? AND document_type = ?");
        $stmt->bind_param("is", $customer_id, $document_type);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            // Update existing document
            // Optionally delete old file
            if (file_exists($existing['file_path'])) {
                @unlink($existing['file_path']);
            }

            $stmt = $conn->prepare("UPDATE borrower_documents SET file_name = ?, file_path = ?, file_size = ?, upload_date = NOW(), status = 0 WHERE id = ?");
            $stmt->bind_param("ssii", $new_filename, $file_path, $file_size, $existing['id']);
        } else {
            // Insert new document
            $stmt = $conn->prepare("INSERT INTO borrower_documents (borrower_id, document_type, file_name, file_path, file_size, upload_date, status) VALUES (?, ?, ?, ?, ?, NOW(), 0)");
            $stmt->bind_param("isssi", $customer_id, $document_type, $new_filename, $file_path, $file_size);
        }

        if ($stmt->execute()) {
            $_SESSION['success_msg'] = 'Document uploaded successfully! It will be reviewed by our team.';
        } else {
            $_SESSION['error_msg'] = 'Database error: ' . $conn->error;
        }
        $stmt->close();

    } else {
        $_SESSION['error_msg'] = 'Failed to save file. Please try again.';
    }

} else {
    $_SESSION['error_msg'] = 'Invalid request method';
}

header('Location: customer_my_documents.php');
exit;
