<?php
/**
 * HTTPS Enforcement
 * Redirects HTTP requests to HTTPS for secure connections
 *
 * Include this file at the top of sensitive pages (login, admin, customer pages)
 */

function enforceHTTPS() {
    // Skip HTTPS enforcement in local development
    $isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']);

    if ($isLocalhost) {
        return; // Skip HTTPS enforcement on localhost
    }

    // Check if the request is not using HTTPS
    $isHTTPS = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        $_SERVER['SERVER_PORT'] == 443 ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
    );

    if (!$isHTTPS) {
        // Build the HTTPS URL
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // Redirect to HTTPS
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect);
        exit();
    }

    // Set security headers
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Auto-execute if this file is included
enforceHTTPS();
?>
