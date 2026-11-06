<?php
/**
 * Customer Session Management
 * Handles session check and extension for customer portal
 */

session_start();
require_once 'includes/security.php';

$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'check':
        // Check if customer is logged in and session status
        if (isset($_SESSION['customer_id'])) {
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

    case 'extend':
        // Extend the session by updating last activity
        if (isset($_SESSION['customer_id'])) {
            Security::updateActivity();
            $info = Security::getSessionTimeoutInfo();
            echo json_encode([
                'status' => 'ok',
                'remaining' => $info['remaining'],
                'message' => 'Session extended'
            ]);
        } else {
            echo json_encode(['status' => 'expired', 'message' => 'Session has expired']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>
