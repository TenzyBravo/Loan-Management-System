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
                        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                        .btn { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Basils Money Lenders</title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .forgot-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 450px;
            width: 100%;
            margin: 20px;
        }
        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .forgot-header i {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 15px;
        }
        .forgot-header h3 {
            color: #333;
            font-weight: 600;
        }
        .forgot-header p {
            color: #666;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            color: white;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <i class="fas fa-key"></i>
            <h3>Forgot Password?</h3>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="Enter your registered email" required>
                </div>

                <button type="submit" class="btn btn-reset">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="customer_login.php">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
