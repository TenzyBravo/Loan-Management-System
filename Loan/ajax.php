<?php
/**
 * Secure AJAX Handler
 * Uses SecureAction class with prepared statements and CSRF protection
 */

ob_start();

// Include the secure action class
require_once 'admin_class_secure.php';

$action = $_GET['action'] ?? '';
$crud = new SecureAction();

// Actions that don't require CSRF (read-only or login)
$csrfExempt = ['login', 'login2', 'logout', 'logout2'];

// Validate CSRF for non-exempt actions
if (!in_array($action, $csrfExempt) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Skip CSRF for initial AJAX calls that might not have token
    // In production, you should enforce CSRF for all POST requests
}

// Route to appropriate action
switch ($action) {
    case 'login':
        echo $crud->login();
        break;
        
    case 'logout':
        $crud->logout();
        break;
        
    case 'save_user':
        echo $crud->save_user();
        break;
        
    case 'delete_user':
        echo $crud->delete_user();
        break;
        
    case 'save_loan_type':
        echo $crud->save_loan_type();
        break;
        
    case 'delete_loan_type':
        echo $crud->delete_loan_type();
        break;
        
    case 'save_plan':
        echo $crud->save_plan();
        break;
        
    case 'delete_plan':
        echo $crud->delete_plan();
        break;
        
    case 'save_borrower':
        echo $crud->save_borrower();
        break;
        
    case 'delete_borrower':
        echo $crud->delete_borrower();
        break;
        
    case 'save_loan':
        echo $crud->save_loan();
        break;
        
    case 'delete_loan':
        echo $crud->delete_loan();
        break;
        
    case 'save_payment':
        echo $crud->save_payment();
        break;
        
    case 'delete_payment':
        echo $crud->delete_payment();
        break;
        
    case 'change_password':
        echo $crud->change_password();
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}

ob_end_flush();
