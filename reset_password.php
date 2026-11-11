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

require_once __DIR__ . '/includes/portal_layout.php';

$pageHead = <<<'HTML'
<style>
    .auth-icon {
        width: 56px; height: 56px; border-radius: 16px; margin-bottom: 14px;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--brand-50); color: var(--brand); font-size: 1.4rem;
    }
    .auth-icon.is-success { background: var(--ok-bg); color: var(--ok); }
    .auth-icon.is-error { background: var(--bad-bg); color: var(--bad); }
    .password-requirements {
        background: var(--bg); padding: 12px 14px; border-radius: var(--radius-sm);
        margin-bottom: 18px; font-size: .84rem; color: var(--ink-2);
    }
    .password-requirements ul { margin: 4px 0 0; padding-left: 18px; }
    .password-requirements ul li { color: var(--muted); transition: color .2s; }
    .password-requirements ul li.valid { color: var(--ok); }
    .password-requirements ul li.valid::before { content: '\2713  '; font-weight: 700; }
    .password-strength { height: 5px; width: 0; border-radius: 3px; margin-top: 6px; transition: all .3s; }
    /* link-styled buttons: keep white text (".portal a" would turn it green) */
    .auth-card a.btn-primary, .auth-card a.btn-primary:hover, .auth-card a.btn-primary:focus { color: #fff; text-decoration: none; }
</style>
HTML;

$pageScripts = <<<'HTML'
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
HTML;

portal_auth_header('Reset Password', ['head' => $pageHead]);
?>
    <?php if($success): ?>
        <span class="auth-icon is-success" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
        <h1>Password Reset Successful!</h1>
        <p class="auth-lead"><?php echo $success; ?></p>
        <a href="customer_login.php" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-sign-in-alt"></i> Go to Login
        </a>

    <?php elseif($error && !$valid_token): ?>
        <span class="auth-icon is-error" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
        <h1>Link Expired</h1>
        <p class="auth-lead"><?php echo $error; ?></p>
        <a href="forgot_password.php" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-redo"></i> Request New Link
        </a>

    <?php elseif($valid_token): ?>
        <span class="auth-icon" aria-hidden="true"><i class="fas fa-lock"></i></span>
        <h1>Create New Password</h1>
        <p class="auth-lead">Hello <?php echo htmlspecialchars($reset_data['firstname']); ?>, enter your new password below.</p>

        <?php if($error): ?>
            <div class="alert alert-danger" role="alert">
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
                <li id="req-special">At least one special character (!@#$%^&amp;*)</li>
            </ul>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="Enter new password" minlength="8" autocomplete="new-password" required>
                <div class="password-strength" id="password-strength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                       placeholder="Confirm new password" minlength="8" autocomplete="new-password" required>
                <div class="invalid-feedback">Passwords do not match.</div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-save"></i> Reset Password
            </button>
        </form>

    <?php else: ?>
        <span class="auth-icon is-error" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
        <h1>Invalid Request</h1>
        <p class="auth-lead">No reset token provided. Please use the link from your email.</p>
        <a href="forgot_password.php" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-redo"></i> Request Password Reset
        </a>
    <?php endif; ?>

    <div class="auth-divider">or</div>

    <p class="text-center mb-0">
        <a href="customer_login.php" class="font-weight-bold">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </p>
<?php
portal_auth_footer(['scripts' => $pageScripts]);
