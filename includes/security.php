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
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            self::regenerateSession();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            self::regenerateSession();
        }
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
        
        // Log to file
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        // Also log to database if connection provided
        if ($conn) {
            $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, ip_address, user_agent, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $detailsJson = json_encode($details);
                $stmt->bind_param("issss", $userId, $event, $ip, $userAgent, $detailsJson);
                $stmt->execute();
                $stmt->close();
            }
        }
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
