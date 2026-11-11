<?php
session_start();
if(isset($_SESSION['customer_id'])){
    header("location: customer_dashboard.php");
    exit;
}

require_once __DIR__ . '/includes/portal_layout.php';

$pageScripts = <<<'HTML'
<script>
    // Show / hide password
    $('#toggle-password').on('click', function() {
        var $input = $('#password');
        var show = $input.attr('type') === 'password';
        $input.attr('type', show ? 'text' : 'password');
        $(this).attr('aria-label', show ? 'Hide password' : 'Show password')
               .attr('aria-pressed', show ? 'true' : 'false');
        $(this).find('i').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show);
    });
</script>
HTML;

$pageHead = <<<'HTML'
<style>
    .pw-toggle { min-width: 48px; border-color: #cfd8d3; color: var(--muted); background: #fff; }
    .pw-toggle:hover, .pw-toggle:focus { color: var(--brand); background: var(--bg); }
    .input-group > .form-control:not(:last-child) { border-right: 0; }
    .auth-links { text-align: center; font-size: .92rem; }
    .auth-links a { font-weight: 600; }
    .auth-links .auth-staff { color: var(--muted); font-weight: 400; font-size: .85rem; }
</style>
HTML;

portal_auth_header('Customer Login', ['head' => $pageHead]);
?>
    <h1>Customer Login</h1>
    <p class="auth-lead">Welcome back! Please enter your credentials to continue.</p>

    <?php if(isset($_SESSION['login_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <?php
            echo $_SESSION['login_error'];
            unset($_SESSION['login_error']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i>
            <?php
            echo $_SESSION['success_msg'];
            unset($_SESSION['success_msg']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <form id="customer-login-form" method="POST" action="customer_auth.php">
        <div class="form-group">
            <label for="username">Username or Email</label>
            <input type="text" class="form-control" id="username" name="username"
                   placeholder="Enter your username or email" autocomplete="username"
                   autocapitalize="none" spellcheck="false" required>
        </div>

        <div class="form-group">
            <div class="d-flex justify-content-between align-items-baseline">
                <label for="password">Password</label>
                <a href="forgot_password.php" class="small">Forgot Password?</a>
            </div>
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="Enter your password" autocomplete="current-password" required>
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary pw-toggle" id="toggle-password"
                            aria-label="Show password" aria-pressed="false">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="form-group custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="remember" name="remember">
            <label class="custom-control-label" for="remember">Remember me</label>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>

    <div class="auth-divider">or</div>

    <div class="auth-links">
        <p class="mb-3">Don't have an account?</p>
        <a href="customer_register.php" class="btn btn-outline-primary btn-block">
            <i class="fas fa-user-plus"></i> Apply Now
        </a>
        <p class="mt-4 mb-0">
            <a href="login.php" class="auth-staff"><i class="fas fa-user-shield"></i> Admin Login</a>
        </p>
    </div>
<?php
portal_auth_footer(['scripts' => $pageScripts]);
