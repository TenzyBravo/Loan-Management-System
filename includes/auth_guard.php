<?php
/**
 * Staff/admin access guard.
 *
 * Include at the very top of every back-office page or endpoint, then call
 * require_staff() (any logged-in user) or require_staff(true) (admins only).
 * Safe to include more than once and from pages already included by admin.php.
 */

require_once __DIR__ . '/security.php';

Security::secureSession();

if (!function_exists('require_staff')) {
    /**
     * Stop the request unless a back-office user is logged in.
     *
     * @param bool $adminOnly Require login_type == 1 (Admin) rather than any staff user.
     */
    function require_staff(bool $adminOnly = false): void {
        // isset, not empty(): a user whose id is 0 is still logged in
        $loggedIn = Security::isLoggedIn();
        $isAdmin  = $loggedIn && (int)($_SESSION['login_type'] ?? 0) === 1;

        if ($loggedIn && (!$adminOnly || $isAdmin)) {
            // Sliding timeout: staff who keep working stay logged in
            Security::updateActivity();
            return;
        }

        $code = $loggedIn ? 403 : 401;
        $message = $loggedIn ? 'Administrator privileges required.' : 'Your session has expired. Please log in again.';

        $isAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
            || basename($_SERVER['SCRIPT_NAME'] ?? '') === 'ajax.php';

        if ($isAjax) {
            // Drop anything buffered so the response is clean JSON
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        if (!headers_sent()) {
            http_response_code($code);
        }

        if ($isAjax) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode(['status' => 'error', 'message' => $message]);
        } elseif (!$loggedIn && !headers_sent()) {
            header('Location: login.php');
        } else {
            echo '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
        }
        exit;
    }
}
