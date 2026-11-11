<?php
session_start();
if(isset($_SESSION['customer_id'])){
    header("location: customer_dashboard.php");
    exit;
}

// Error handling for free hosting
error_reporting(0);
ini_set('display_errors', 0);

include('db_connect.php');

// Include notifications safely
try {
    require_once 'includes/notifications.php';
} catch (Exception $e) {
    // Notifications not available
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if(empty($email)) {
        $error = 'Please enter your email address.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id, firstname, lastname, email FROM borrowers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows > 0) {
                $borrower = $result->fetch_assoc();

                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Check if password_resets table exists
                $tableCheck = @$conn->query("SHOW TABLES LIKE 'password_resets'");
                if (!$tableCheck || $tableCheck->num_rows === 0) {
                    // Create table if it doesn't exist
                    $conn->query("CREATE TABLE IF NOT EXISTS `password_resets` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `borrower_id` int(11) NOT NULL,
                        `token` varchar(64) NOT NULL,
                        `expires_at` datetime NOT NULL,
                        `used` tinyint(1) DEFAULT 0,
                        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `idx_token` (`token`),
                        KEY `idx_borrower` (`borrower_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                }

                // Invalidate any existing tokens for this user
                $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE borrower_id = ? AND used = 0");
                $stmt->bind_param("i", $borrower['id']);
                @$stmt->execute();

                // Insert new token
                $stmt = $conn->prepare("INSERT INTO password_resets (borrower_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $borrower['id'], $token, $expires_at);

            if($stmt->execute()) {
                // Build reset link
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $path = dirname($_SERVER['PHP_SELF']);
                $reset_link = $protocol . '://' . $host . $path . '/reset_password.php?token=' . $token;

                // Send email
                $subject = "Password Reset Request - Basils Money Lenders";
                $message = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #1a5f2a 0%, #0d3d16 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                        .btn { display: inline-block; background: #1a5f2a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Password Reset Request</h2>
                        </div>
                        <div class='content'>
                            <p>Hello {$borrower['firstname']},</p>
                            <p>We received a request to reset your password for your Basils Money Lenders account.</p>
                            <p>Click the button below to reset your password:</p>
                            <p style='text-align: center;'>
                                <a href='{$reset_link}' class='btn'>Reset Password</a>
                            </p>
                            <p>Or copy and paste this link into your browser:</p>
                            <p style='word-break: break-all; background: #eee; padding: 10px; border-radius: 5px;'>{$reset_link}</p>
                            <p><strong>This link will expire in 1 hour.</strong></p>
                            <p>If you didn't request this password reset, please ignore this email or contact us if you have concerns.</p>
                        </div>
                        <div class='footer'>
                            <p>&copy; " . date('Y') . " Basils Money Lenders. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";

                // Try to send email (fail silently if function doesn't exist or fails)
                if (function_exists('send_email_notification')) {
                    try {
                        @send_email_notification(
                            $borrower['email'],
                            $borrower['firstname'] . ' ' . $borrower['lastname'],
                            $subject,
                            $message,
                            $conn
                        );
                    } catch (Exception $e) {
                        // Email sending failed, but we still show success
                    }
                }

                // Always show success message (don't reveal if email exists)
                $success = 'If an account with that email exists, you will receive a password reset link shortly. Please check your inbox and spam folder.';
            } else {
                $error = 'An error occurred. Please try again later.';
            }
        } else {
            // Don't reveal that email doesn't exist (security)
            $success = 'If an account with that email exists, you will receive a password reset link shortly. Please check your inbox and spam folder.';
        }
        if (isset($stmt)) @$stmt->close();
        } catch (Exception $e) {
            // Database error - show generic message
            $success = 'If an account with that email exists, you will receive a password reset link shortly. Please check your inbox and spam folder.';
        }
    }
}

require_once __DIR__ . '/includes/portal_layout.php';

$pageHead = <<<'HTML'
<style>
    .auth-icon {
        width: 56px; height: 56px; border-radius: 16px; margin-bottom: 14px;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--brand-50); color: var(--brand); font-size: 1.4rem;
    }
</style>
HTML;

portal_auth_header('Forgot Password', ['head' => $pageHead]);
?>
    <span class="auth-icon" aria-hidden="true"><i class="fas fa-key"></i></span>
    <h1>Forgot Password?</h1>
    <p class="auth-lead">Enter your email address and we'll send you a link to reset your password.</p>

    <?php if($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success" role="status">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php else: ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email"
                       placeholder="Enter your registered email" autocomplete="email"
                       autocapitalize="none" spellcheck="false" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>
    <?php endif; ?>

    <div class="auth-divider">or</div>

    <p class="text-center mb-0">
        <a href="customer_login.php" class="font-weight-bold">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </p>
<?php
portal_auth_footer();
