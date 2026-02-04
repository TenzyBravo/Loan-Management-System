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

// Actions that don't require CSRF (read-only, login, or admin-only actions protected by session)
$csrfExempt = [
    'login', 'login2', 'logout', 'logout2',
    'get_loan_review_details', 'approve_loan_application', 'deny_loan_application',
    'delete_loan', 'delete_borrower', 'delete_payment', 'delete_loan_type', 'delete_plan', 'delete_user',
    'update_document_status', 'save_payment', 'mark_loan_paid', 'apply_overdue_penalty',
    'admin_change_password', 'admin_update_profile'
];

// Validate CSRF for non-exempt actions
if (!in_array($action, $csrfExempt) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($token)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token. Please refresh the page and try again.']);
        ob_end_flush();
        exit;
    }
}

// Route to appropriate action
switch ($action) {
    case 'login':
        echo $crud->login();
        break;
        
    case 'logout':
        $crud->logout();
        break;

    case 'mark_all_notifications_read':
        require_once 'includes/notifications.php';
        require_once 'db_connect.php';
        mark_all_admin_notifications_read($conn);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'admin.php'));
        exit;
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

    case 'get_loan_review_details':
        echo $crud->get_loan_review_details();
        break;

    case 'update_document_status':
        echo $crud->update_document_status();
        break;

    case 'approve_loan_application':
        echo $crud->approve_loan_application();
        break;

    case 'deny_loan_application':
        echo $crud->deny_loan_application();
        break;

    case 'mark_loan_paid':
        echo $crud->mark_loan_paid();
        break;

    case 'apply_overdue_penalty':
        echo $crud->apply_overdue_penalty();
        break;

    case 'admin_change_password':
        echo $crud->admin_change_password();
        break;

    case 'admin_update_profile':
        echo $crud->admin_update_profile();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}

ob_end_flush();
