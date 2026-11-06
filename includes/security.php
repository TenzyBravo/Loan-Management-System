<?php
/**
 * Security Helper Functions
 * Provides password hashing, CSRF protection, input sanitization
 */

class Security {
    
    /**
     * Hash a password using bcrypt
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify a password against a hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing (cost changed)
     */
    public static function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Validate password strength
     * @param string $password The password to validate
     * @param bool $strict Use strict requirements (default: true)
     * @return array ['valid' => bool, 'errors' => array, 'strength' => string]
     */
    public static function validatePasswordStrength($password, $strict = true) {
        $errors = [];
        $strength = 'weak';

        // Minimum length
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if ($strict) {
            // Check for uppercase
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Password must contain at least one uppercase letter';
            }

            // Check for lowercase
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = 'Password must contain at least one lowercase letter';
            }

            // Check for number
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = 'Password must contain at least one number';
            }

            // Check for special character
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = 'Password must contain at least one special character (!@#$%^&*)';
            }
        }

        // Calculate strength
        $score = 0;
        if (strlen($password) >= 8) $score++;
        if (strlen($password) >= 12) $score++;
        if (preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/[a-z]/', $password)) $score++;
        if (preg_match('/[0-9]/', $password)) $score++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score++;

        if ($score <= 2) $strength = 'weak';
        else if ($score <= 4) $strength = 'medium';
        else $strength = 'strong';

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $strength,
            'score' => $score
        ];
    }

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token input field
     */
    public static function csrfField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Sanitize string input
     */
    public static function sanitizeString($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeString'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize email
     */
    public static function sanitizeEmail($email) {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize integer
     */
    public static function sanitizeInt($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize float/decimal
     */
    public static function sanitizeFloat($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Secure session configuration
     */
    public static function secureSession() {
        // Prevent session fixation
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);

            // Use secure cookies if HTTPS
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }

            session_start();
        }

        // Initialize last activity if not set
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
        }

        // Check session timeout (only if logged in)
        if (isset($_SESSION['login_id'])) {
            $timeout = self::checkSessionTimeout();
            if (!$timeout['valid']) {
                // Session expired - destroy it
                self::destroySession();
                // Don't redirect here, let the AJAX or page handle it
            }
        }

        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            self::regenerateSession();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            self::regenerateSession();
        }
    }

    /**
     * Destroy session completely
     */
    public static function destroySession() {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"] ?? '/',
                $params["domain"] ?? '',
                $params["secure"] ?? false,
                $params["httponly"] ?? true
            );
        }

        session_destroy();
    }
    
    /**
     * Regenerate session ID
     */
    public static function regenerateSession() {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['login_id']) && !empty($_SESSION['login_id']);
    }
    
    /**
     * Require login - redirect if not logged in
     */
    public static function requireLogin($redirect = 'login.php') {
        if (!self::isLoggedIn()) {
            header("Location: $redirect");
            exit;
        }
    }
    
    /**
     * Require admin role
     */
    public static function requireAdmin($redirect = 'login.php') {
        self::requireLogin($redirect);
        if (!isset($_SESSION['login_type']) || $_SESSION['login_type'] != 1) {
            header("Location: $redirect");
            exit;
        }
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($key, $maxAttempts = 5, $decayMinutes = 15) {
        $cacheKey = 'rate_limit_' . $key;
        
        if (!isset($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $data = $_SESSION[$cacheKey];
        
        // Reset if decay period passed
        if (time() - $data['first_attempt'] > ($decayMinutes * 60)) {
            $_SESSION[$cacheKey] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Check if exceeded
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }
        
        // Increment attempts
        $_SESSION[$cacheKey]['attempts']++;
        return true;
    }
    
    /**
     * Clear rate limit
     */
    public static function clearRateLimit($key) {
        unset($_SESSION['rate_limit_' . $key]);
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = [], $conn = null) {
        // Wrap in try-catch to prevent logging failures from crashing the app
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $userId = $_SESSION['login_id'] ?? null;

            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'event' => $event,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'user_id' => $userId,
                'details' => $details
            ];

            // Log to file (silently fail if directory can't be created)
            $logFile = __DIR__ . '/../logs/security.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }

            if (is_writable($logDir) || is_writable($logFile)) {
                @file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
            }

            // Also log to database if connection provided
            if ($conn) {
                $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, ip_address, user_agent, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                if ($stmt) {
                    $detailsJson = json_encode($details);
                    $stmt->bind_param("issss", $userId, $event, $ip, $userAgent, $detailsJson);
                    @$stmt->execute();
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            // Silently fail - logging should never break the app
        }
    }

    /**
     * Check if IP is rate limited for login attempts
     * @param mysqli $conn Database connection
     * @param int $maxAttempts Maximum attempts allowed (default 5)
     * @param int $lockoutMinutes Lockout duration in minutes (default 15)
     * @return array ['allowed' => bool, 'remaining' => int, 'locked_until' => string|null]
     */
    public static function checkLoginRateLimit($conn, $maxAttempts = 5, $lockoutMinutes = 15) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $result = ['allowed' => true, 'remaining' => $maxAttempts, 'locked_until' => null];

        try {
            // Check if login_attempts table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'login_attempts'");
            if ($tableCheck->num_rows === 0) {
                return $result; // Table doesn't exist, allow login
            }

            // Count recent failed attempts
            $stmt = $conn->prepare("
                SELECT COUNT(*) as attempts,
                       MAX(attempted_at) as last_attempt
                FROM login_attempts
                WHERE ip_address = ?
                AND success = 0
                AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $stmt->bind_param("si", $ip, $lockoutMinutes);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $attempts = (int)$row['attempts'];
            $result['remaining'] = max(0, $maxAttempts - $attempts);

            if ($attempts >= $maxAttempts) {
                $result['allowed'] = false;
                $lockedUntil = strtotime($row['last_attempt']) + ($lockoutMinutes * 60);
                $result['locked_until'] = date('Y-m-d H:i:s', $lockedUntil);
            }
        } catch (Exception $e) {
            // On error, allow login (fail open for availability)
        }

        return $result;
    }

    /**
     * Record a login attempt
     * @param mysqli $conn Database connection
     * @param string|null $username Username attempted
     * @param bool $success Whether login was successful
     */
    public static function recordLoginAttempt($conn, $username = null, $success = false) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        try {
            // Check if table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'login_attempts'");
            if ($tableCheck->num_rows === 0) {
                return; // Table doesn't exist
            }

            $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, ?)");
            $successInt = $success ? 1 : 0;
            $stmt->bind_param("ssi", $ip, $username, $successInt);
            @$stmt->execute();
            $stmt->close();

            // If successful login, clear previous failed attempts for this IP
            if ($success) {
                $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND success = 0");
                $stmt->bind_param("s", $ip);
                @$stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            // Silently fail
        }
    }

    /**
     * Clean up old login attempts (call periodically)
     * @param mysqli $conn Database connection
     * @param int $hoursOld Delete attempts older than this many hours
     */
    public static function cleanupLoginAttempts($conn, $hoursOld = 24) {
        try {
            $tableCheck = $conn->query("SHOW TABLES LIKE 'login_attempts'");
            if ($tableCheck->num_rows === 0) {
                return;
            }

            $stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? HOUR)");
            $stmt->bind_param("i", $hoursOld);
            @$stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // Silently fail
        }
    }

    /**
     * Send lockout notification email
     * @param mysqli $conn Database connection
     * @param string $email User email address
     * @param string $name User name
     * @param int $minutes Lockout duration in minutes
     * @param bool $isAdmin Whether this is an admin account
     */
    public static function sendLockoutNotification($conn, $email, $name, $minutes = 15, $isAdmin = false) {
        try {
            $accountType = $isAdmin ? 'Admin' : 'Customer';
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $unlockTime = date('M j, Y g:i A', time() + ($minutes * 60));

            $subject = "Account Temporarily Locked - Basils Money Lenders";
            $message = "
                <div class='highlight'>
                    <p><strong>⚠️ Your account has been temporarily locked</strong></p>
                </div>
                <p>Hello $name,</p>
                <p>We detected multiple failed login attempts on your account and have temporarily locked it for security reasons.</p>
                <p><strong>Details:</strong></p>
                <ul>
                    <li><strong>Account Type:</strong> $accountType</li>
                    <li><strong>Locked At:</strong> " . date('M j, Y g:i A') . "</li>
                    <li><strong>IP Address:</strong> $ip</li>
                    <li><strong>Unlock Time:</strong> $unlockTime</li>
                </ul>
                <div class='highlight'>
                    <p><strong>What to do next:</strong></p>
                    <p>If this was you, please wait $minutes minutes before trying to log in again. If you've forgotten your password, you can reset it using the 'Forgot Password' link on the login page.</p>
                    <p>If you did not attempt to log in, your account may be under attack. Please contact us immediately.</p>
                </div>
                <p><strong>Security Tips:</strong></p>
                <ul>
                    <li>Use a strong, unique password for your account</li>
                    <li>Never share your password with anyone</li>
                    <li>Be cautious of phishing attempts</li>
                    <li>Keep your computer and browser up to date</li>
                </ul>
            ";

            // Try to send email
            if (function_exists('send_email_notification')) {
                @send_email_notification($email, $name, $subject, $message, $conn);
            }
        } catch (Exception $e) {
            // Silently fail - don't block the lockout process
        }
    }

    // Session timeout settings (in seconds)
    const SESSION_TIMEOUT = 1800;       // 30 minutes
    const SESSION_WARNING = 300;        // 5 minutes before expiry (show warning)

    /**
     * Check and enforce session timeout
     * @return array ['valid' => bool, 'remaining' => int, 'warning' => bool]
     */
    public static function checkSessionTimeout() {
        $result = [
            'valid' => true,
            'remaining' => self::SESSION_TIMEOUT,
            'warning' => false
        ];

        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return $result;
        }

        $elapsed = time() - $_SESSION['last_activity'];
        $remaining = self::SESSION_TIMEOUT - $elapsed;

        if ($remaining <= 0) {
            // Session expired
            $result['valid'] = false;
            $result['remaining'] = 0;
            return $result;
        }

        $result['remaining'] = $remaining;
        $result['warning'] = ($remaining <= self::SESSION_WARNING);

        return $result;
    }

    /**
     * Update last activity timestamp
     */
    public static function updateActivity() {
        $_SESSION['last_activity'] = time();
    }

    /**
     * Get session timeout info for JavaScript
     * @return array
     */
    public static function getSessionTimeoutInfo() {
        $timeout = self::checkSessionTimeout();
        return [
            'timeout' => self::SESSION_TIMEOUT,
            'warning' => self::SESSION_WARNING,
            'remaining' => $timeout['remaining'],
            'valid' => $timeout['valid']
        ];
    }
}

/**
 * Helper functions for templates
 */
function csrf_field() {
    return Security::csrfField();
}

function csrf_token() {
    return Security::generateCSRFToken();
}

function e($string) {
    return Security::sanitizeString($string);
}

function old($key, $default = '') {
    return isset($_POST[$key]) ? Security::sanitizeString($_POST[$key]) : $default;
}
