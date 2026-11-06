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
require_once 'includes/security.php';
require_once 'includes/notifications.php';

$error = '';
$success = '';
$valid_token = false;
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$reset_data = null;

// Validate token
if(!empty($token)) {
    try {
        // Check if table exists first
        $tableCheck = @$conn->query("SHOW TABLES LIKE 'password_resets'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            $error = 'Password reset is not configured. Please contact support.';
        } else {
            $stmt = $conn->prepare("
                SELECT pr.*, b.email, b.firstname
                FROM password_resets pr
                JOIN borrowers b ON pr.borrower_id = b.id
                WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
            ");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows > 0) {
                $valid_token = true;
                $reset_data = $result->fetch_assoc();
            } else {
                $error = 'This password reset link is invalid or has expired. Please request a new one.';
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $error = 'An error occurred. Please try again or request a new reset link.';
    }
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token && $reset_data) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate password strength
    $passwordCheck = Security::validatePasswordStrength($password);

    if(!$passwordCheck['valid']) {
        $error = implode(' ', $passwordCheck['errors']);
    } elseif($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Update borrower password
            $stmt = $conn->prepare("UPDATE borrowers SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $reset_data['borrower_id']);

            if($stmt->execute()) {
                // Mark token as used
                $stmt2 = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                $stmt2->bind_param("i", $reset_data['id']);
                @$stmt2->execute();
                @$stmt2->close();

                // Send confirmation email
                if (function_exists('send_email_notification')) {
                    $emailSubject = "Password Changed Successfully - Basils Money Lenders";
                    $emailMessage = "
                        <p>Your password has been changed successfully.</p>
                        <p><strong>When:</strong> " . date('M j, Y g:i A') . "</p>
                        <p><strong>IP Address:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "</p>
                        <div class='highlight'>
                            <p><strong>Did you make this change?</strong></p>
                            <p>If you did not change your password, please contact us immediately or reset your password again.</p>
                        </div>
                        <p>For security reasons, we recommend:</p>
                        <ul>
                            <li>Use a unique password for your account</li>
                            <li>Never share your password with anyone</li>
                            <li>Enable two-factor authentication if available</li>
                        </ul>
                    ";

                    try {
                        @send_email_notification(
                            $reset_data['email'],
                            $reset_data['firstname'],
                            $emailSubject,
                            $emailMessage,
                            $conn
                        );
                    } catch (Exception $e) {
                        // Email failed, but password was changed successfully
                    }
                }

                $success = 'Your password has been reset successfully! You can now log in with your new password.';
                $valid_token = false; // Hide the form
            } else {
                $error = 'An error occurred. Please try again.';
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Basils Money Lenders</title>

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
        .reset-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 450px;
            width: 100%;
            margin: 20px;
        }
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .reset-header i {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 15px;
        }
        .reset-header.success i {
            color: #28a745;
        }
        .reset-header.error i {
            color: #dc3545;
        }
        .reset-header h3 {
            color: #333;
            font-weight: 600;
        }
        .reset-header p {
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
        .btn-login {
            background: #28a745;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            color: white;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-login:hover {
            background: #218838;
            color: white;
            text-decoration: none;
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
        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .password-requirements ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        .password-requirements ul li {
            color: #666;
            transition: color 0.3s;
        }
        .password-requirements ul li.valid {
            color: #28a745;
        }
        .password-requirements ul li.valid::before {
            content: '✓ ';
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <?php if($success): ?>
            <div class="reset-header success">
                <i class="fas fa-check-circle"></i>
                <h3>Password Reset Successful!</h3>
                <p><?php echo $success; ?></p>
            </div>
            <a href="customer_login.php" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Go to Login
            </a>

        <?php elseif($error && !$valid_token): ?>
            <div class="reset-header error">
                <i class="fas fa-exclamation-circle"></i>
                <h3>Link Expired</h3>
                <p><?php echo $error; ?></p>
            </div>
            <a href="forgot_password.php" class="btn-reset" style="text-decoration: none; display: block; text-align: center;">
                <i class="fas fa-redo"></i> Request New Link
            </a>

        <?php elseif($valid_token): ?>
            <div class="reset-header">
                <i class="fas fa-lock"></i>
                <h3>Create New Password</h3>
                <p>Hello <?php echo htmlspecialchars($reset_data['firstname']); ?>, enter your new password below.</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="password-requirements">
                <strong><i class="fas fa-info-circle"></i> Password Requirements:</strong>
                <ul>
                    <li id="req-length">At least 8 characters long</li>
                    <li id="req-uppercase">At least one uppercase letter (A-Z)</li>
                    <li id="req-lowercase">At least one lowercase letter (a-z)</li>
                    <li id="req-number">At least one number (0-9)</li>
                    <li id="req-special">At least one special character (!@#$%^&*)</li>
                </ul>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> New Password</label>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Enter new password" minlength="8" required>
                    <div class="password-strength" id="password-strength"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                           placeholder="Confirm new password" minlength="8" required>
                </div>

                <button type="submit" class="btn btn-reset">
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>

        <?php else: ?>
            <div class="reset-header error">
                <i class="fas fa-exclamation-circle"></i>
                <h3>Invalid Request</h3>
                <p>No reset token provided. Please use the link from your email.</p>
            </div>
            <a href="forgot_password.php" class="btn-reset" style="text-decoration: none; display: block; text-align: center;">
                <i class="fas fa-redo"></i> Request Password Reset
            </a>
        <?php endif; ?>

        <div class="back-link">
            <a href="customer_login.php">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator with requirement validation
        $('#password').on('input', function() {
            var password = $(this).val();
            var strength = 0;
            var bar = $('#password-strength');

            // Check each requirement
            var hasLength = password.length >= 8;
            var hasLowercase = password.match(/[a-z]/);
            var hasUppercase = password.match(/[A-Z]/);
            var hasNumber = password.match(/[0-9]/);
            var hasSpecial = password.match(/[^a-zA-Z0-9]/);

            // Update requirement list
            $('#req-length').toggleClass('valid', hasLength);
            $('#req-lowercase').toggleClass('valid', hasLowercase);
            $('#req-uppercase').toggleClass('valid', hasUppercase);
            $('#req-number').toggleClass('valid', hasNumber);
            $('#req-special').toggleClass('valid', hasSpecial);

            // Calculate strength
            if (hasLength) strength++;
            if (hasLowercase) strength++;
            if (hasUppercase) strength++;
            if (hasNumber) strength++;
            if (hasSpecial) strength++;

            var colors = ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#20c997'];
            var widths = ['20%', '40%', '60%', '80%', '100%'];
            var labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];

            if (password.length > 0) {
                bar.css({
                    'width': widths[strength - 1] || '20%',
                    'background': colors[strength - 1] || '#dc3545'
                });
                bar.attr('title', labels[strength - 1] || 'Very Weak');
            } else {
                bar.css('width', '0');
                // Reset all requirement indicators
                $('.password-requirements ul li').removeClass('valid');
            }
        });

        // Password match validation
        $('#confirm_password').on('input blur', function() {
            if ($(this).val() && $(this).val() !== $('#password').val()) {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
    </script>
</body>
</html>
