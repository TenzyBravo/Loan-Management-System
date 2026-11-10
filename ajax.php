<?php
/**
 * Secure AJAX Handler
 * Uses SecureAction class with prepared statements and CSRF protection
 */

ob_start();

// Include the secure action class
require_once 'admin_class_secure.php';
require_once __DIR__ . '/includes/auth_guard.php';

$action = $_GET['action'] ?? '';

// Actions reachable without a logged-in back-office user.
// check_session / extend_session handle the logged-out case themselves.
$publicActions = ['login', 'logout', 'check_session', 'extend_session'];

// Actions restricted to administrators (login_type == 1)
$adminOnlyActions = ['save_user', 'delete_user'];

if (!in_array($action, $publicActions, true)) {
    require_staff(in_array($action, $adminOnlyActions, true));
}

// Actions that don't require CSRF: login has no session yet; logout is a plain link
$csrfExempt = ['login', 'logout'];

// Every other POST must carry the session's CSRF token (form field or X-CSRF-Token header)
if (!in_array($action, $csrfExempt, true) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = Security::getRequestCSRFToken();
    if (!Security::validateCSRFToken($token)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token. Please refresh the page and try again.']);
        ob_end_flush();
        exit;
    }
}

$crud = new SecureAction();

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

    case 'check_session':
        // Check session timeout status
        if (isset($_SESSION['login_id'])) {
            $info = Security::getSessionTimeoutInfo();
            echo json_encode([
                'status' => 'ok',
                'valid' => $info['valid'],
                'remaining' => $info['remaining'],
                'warning' => $info['remaining'] <= $info['warning']
            ]);
        } else {
            echo json_encode(['status' => 'expired', 'valid' => false]);
        }
        break;

    case 'extend_session':
        // Extend the session by updating last activity
        if (isset($_SESSION['login_id'])) {
            Security::updateActivity();
            $info = Security::getSessionTimeoutInfo();
            echo json_encode([
                'status' => 'ok',
                'remaining' => $info['remaining'],
                'message' => 'Session extended successfully'
            ]);
        } else {
            echo json_encode(['status' => 'expired', 'message' => 'Session has expired']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}

ob_end_flush();
