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
// Only exempt read-only or login/logout endpoints from CSRF.
// All state-changing actions must require a valid CSRF token.
$csrfExempt = [
    'login', 'login2', 'logout', 'logout2',
    // read-only detail fetchers
    'get_loan_review_details'
];

// Validate CSRF for non-exempt actions
if (!in_array($action, $csrfExempt) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token. Please refresh the page and try again.']);
        exit;
    }
}

// Which actions return HTML (preserve their output as-is)
$htmlResponses = [
    'get_loan_review_details'
];

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

    // Grab buffered output and normalize
    $output = ob_get_clean();

    // If this was a POST (state-changing) request and not an HTML response, return JSON
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($action, $htmlResponses)) {
        header('Content-Type: application/json; charset=utf-8');
        $trim = trim($output);

        // If handler already returned valid JSON, pass it through
        $decoded = json_decode($trim, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo $trim;
            exit;
        }

        // Common legacy short responses: '1', 'ok', or empty
        if ($trim === '1' || strtolower($trim) === 'ok' || $trim === '') {
            echo json_encode(['status' => 'ok']);
            exit;
        }

        // Otherwise wrap as data
        echo json_encode(['status' => 'ok', 'data' => $trim]);
        exit;
    }

    // For GET or HTML responses, output as-is
    echo $output;
