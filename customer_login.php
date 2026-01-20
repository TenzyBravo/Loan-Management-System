<?php
session_start();
if(isset($_SESSION['customer_id'])){
    header("location: customer_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login | Loan Management System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
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
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            margin: 20px;
        }
        .login-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            text-align: center;
        }
        .login-left h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .login-left p {
            font-size: 1rem;
            opacity: 0.9;
        }
        .login-left i {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.8;
        }
        .login-right {
            padding: 60px 40px;
        }
        .login-right h3 {
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
        }
        .login-right p {
            color: #666;
            margin-bottom: 30px;
        }
        .form-group label {
            font-weight: 500;
            color: #555;
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
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            color: white;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .divider {
            text-align: center;
            margin: 20px 0;
            color: #999;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .alert {
            border-radius: 8px;
        }
        .admin-link {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            padding: 8px 20px;
            border-radius: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .admin-link:hover {
            color: #764ba2;
            text-decoration: none;
        }
        @media (max-width: 768px) {
            .login-left {
                display: none;
            }
        }
    </style>
</head>
<body>
    <a href="login.php" class="admin-link">
        <i class="fas fa-user-shield"></i> Admin Login
    </a>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="login-container">
                    <div class="row no-gutters">
                        <!-- Left Side - Welcome Section -->
                        <div class="col-md-5 login-left d-flex flex-column justify-content-center">
                            <i class="fas fa-hand-holding-usd"></i>
                            <h2>Welcome Back!</h2>
                            <p>Access your loan applications and manage your account securely.</p>
                            <div class="mt-4">
                                <i class="fas fa-shield-alt"></i>
                                <p class="mt-2" style="font-size: 0.9rem;">Secure & Encrypted Connection</p>
                            </div>
                        </div>

                        <!-- Right Side - Login Form -->
                        <div class="col-md-7 login-right">
                            <h3>Customer Login</h3>
                            <p>Please enter your credentials to continue</p>

                            <?php if(isset($_SESSION['login_error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php 
                                    echo $_SESSION['login_error'];
                                    unset($_SESSION['login_error']);
                                    ?>
                                    <button type="button" class="close" data-dismiss="alert">
                                        <span>&times;</span>
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
                                    <button type="button" class="close" data-dismiss="alert">
                                        <span>&times;</span>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <form id="customer-login-form" method="POST" action="customer_auth.php">
                                <div class="form-group">
                                    <label for="username">
                                        <i class="fas fa-user"></i> Username or Email
                                    </label>
                                    <input type="text" class="form-control" id="username" name="username"
                                           placeholder="Enter your username or email" required>
                                </div>

                                <div class="form-group">
                                    <label for="password">
                                        <i class="fas fa-lock"></i> Password
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Enter your password" required>
                                </div>

                                <div class="form-group form-check">
                                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">Remember me</label>
                                </div>

                                <button type="submit" class="btn btn-login">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>

                                <div class="register-link">
                                    <p>Don't have an account? 
                                        <a href="customer_register.php">
                                            <i class="fas fa-user-plus"></i> Apply Now
                                        </a>
                                    </p>
                                    <a href="forgot_password.php" style="color: #999; font-size: 0.9rem;">
                                        <i class="fas fa-key"></i> Forgot Password?
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
