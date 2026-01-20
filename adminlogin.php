<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Admin Login | Loan Management System</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .login-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        .login-form {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .logo {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2rem;
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.9rem;
        }

        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }

        @media (max-width: 576px) {
            .login-container {
                margin: 10px;
            }

            .login-header {
                padding: 20px;
            }

            .login-header h2 {
                font-size: 1.5rem;
            }

            .login-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-lock"></i>
            </div>
            <h2>Admin Login</h2>
            <p>Access your administrative dashboard</p>
        </div>

        <form class="login-form" action="ajax.php?action=login" method="post">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-key"></i> Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt"></i> Login to Dashboard
            </button>

            <div class="form-footer">
                <p>Default credentials: admin / admin123</p>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Handle form submission
            $('.login-form').on('submit', function(e) {
                e.preventDefault();

                var formData = $(this).serialize();

                $.ajax({
                    url: 'ajax.php?action=login',
                    method: 'POST',
                    data: formData,
                    success: function(resp) {
                        if(resp == 1) {
                            // Successful login - redirect to admin dashboard
                            window.location.href = 'admin.php'; // Using the admin interface
                        } else if(resp == 3) {
                            alert('Invalid username or password!');
                        } else {
                            alert('An error occurred during login. Please try again.');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred during login. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>