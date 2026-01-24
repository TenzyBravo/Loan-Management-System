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
    <title>Customer Registration | Brian Investments</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .application-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
        }
        .application-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .application-header h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .application-header p {
            color: #666;
            font-size: 1.1rem;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .step-number {
            width: 50px;
            height: 50px;
            background: #e0e0e0;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #666;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        .step.active .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        .step-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }
        .section-title {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .form-control, .custom-file-input {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
        }
        .custom-file-label {
            border-radius: 8px;
            padding: 12px 15px;
        }
        .custom-file-label::after {
            padding: 12px 15px;
            border-radius: 0 8px 8px 0;
        }
        .file-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        .file-requirements ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        .back-to-login a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="application-container">
            <div class="application-header">
                <i class="fas fa-file-signature" style="font-size: 60px; color: #667eea; margin-bottom: 15px;"></i>
                <h2>Loan Application Form</h2>
                <p>Complete the form below to apply for a loan</p>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-label">Personal Info</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-label">Documents</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">Account</div>
                </div>
            </div>

            <?php if(isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                    echo $_SESSION['error_msg'];
                    unset($_SESSION['error_msg']);
                    ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <form id="application-form" method="POST" action="customer_register_process.php" enctype="multipart/form-data">
                
                <!-- Step 1: Personal Information -->
                <div class="form-step active" id="step1">
                    <h4 class="section-title">
                        <i class="fas fa-user"></i> Personal Information
                    </h4>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field">First Name</label>
                                <input type="text" class="form-control" name="firstname" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Middle Name</label>
                                <input type="text" class="form-control" name="middlename">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="required-field">Last Name</label>
                                <input type="text" class="form-control" name="lastname" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field">Email Address</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field">Contact Number</label>
                                <input type="text" class="form-control" name="contact_no" 
                                       placeholder="+1234567890" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="required-field">Address</label>
                        <textarea class="form-control" name="address" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>NRC Number</label>
                        <input type="text" name="tax_id" placeholder="Enter your NRC Number" class="form-control">
                    </div>

                    <div class="text-right">
                        <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Document Upload -->
                <div class="form-step" id="step2" style="display: none;">
                    <h4 class="section-title">
                        <i class="fas fa-file-upload"></i> Required Documents
                    </h4>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Important:</strong> Please upload clear, legible copies of the following documents.
                    </div>

                    <div class="form-group">
                        <label class="required-field">
                            <i class="fas fa-id-card"></i> Government-Issued ID
                        </label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="id_document" 
                                   name="id_document" accept=".jpg,.jpeg,.png,.pdf" required>
                            <label class="custom-file-label" for="id_document">Choose file...</label>
                        </div>
                        <div class="file-requirements">
                            <strong>Accepted documents:</strong>
                            <ul>
                                <li>Driver's License</li>
                                <li>Passport</li>
                                <li>National ID</li>
                            </ul>
                            <strong>Format:</strong> JPG, PNG, or PDF | <strong>Max size:</strong> 5MB
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="required-field">
                            <i class="fas fa-briefcase"></i> Proof of Employment
                        </label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="employment_proof" 
                                   name="employment_proof" accept=".jpg,.jpeg,.png,.pdf" required>
                            <label class="custom-file-label" for="employment_proof">Choose file...</label>
                        </div>
                        <div class="file-requirements">
                            <strong>Accepted documents:</strong>
                            <ul>
                                <li>Employment Certificate</li>
                                <li>Company ID</li>
                                <li>Contract of Employment</li>
                            </ul>
                            <strong>Format:</strong> JPG, PNG, or PDF | <strong>Max size:</strong> 5MB
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="required-field">
                            <i class="fas fa-money-check-alt"></i> Latest Pay Slip
                        </label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="payslip" 
                                   name="payslip" accept=".jpg,.jpeg,.png,.pdf" required>
                            <label class="custom-file-label" for="payslip">Choose file...</label>
                        </div>
                        <div class="file-requirements">
                            <strong>Requirements:</strong>
                            <ul>
                                <li>Must be from the last 3 months</li>
                                <li>Must show employer name and salary details</li>
                            </ul>
                            <strong>Format:</strong> JPG, PNG, or PDF | <strong>Max size:</strong> 5MB
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="button" class="btn btn-secondary" onclick="prevStep(1)">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Account Setup -->
                <div class="form-step" id="step3" style="display: none;">
                    <h4 class="section-title">
                        <i class="fas fa-user-lock"></i> Create Your Account
                    </h4>

                    <div class="alert alert-warning">
                        <i class="fas fa-shield-alt"></i>
                        <strong>Security Tip:</strong> Choose a strong password with at least 8 characters, 
                        including uppercase, lowercase, and numbers.
                    </div>

                    <div class="form-group">
                        <label class="required-field">Username</label>
                        <input type="text" class="form-control" name="username"
                               placeholder="Choose a unique username" required>
                        <small class="form-text text-muted">
                            This will be used to log in to your account
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field">Password</label>
                                <input type="password" class="form-control" name="password" 
                                       id="password" minlength="8" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required-field">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" 
                                       id="confirm_password" minlength="8" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="terms" 
                                   name="terms" required>
                            <label class="custom-control-label" for="terms">
                                I agree to the <a href="#" target="_blank">Terms and Conditions</a> 
                                and <a href="#" target="_blank">Privacy Policy</a>
                            </label>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Application
                        </button>
                    </div>
                </div>

            </form>

            <div class="back-to-login">
                <p>Already have an account? 
                    <a href="customer_login.php">
                        <i class="fas fa-sign-in-alt"></i> Login Here
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Update file input labels
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });

        // Step navigation
        function nextStep(step) {
            // Validate current step before moving
            var currentStep = $('.form-step.active');
            var inputs = currentStep.find('input[required], textarea[required], select[required]');
            var valid = true;

            inputs.each(function() {
                if (!this.checkValidity()) {
                    valid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!valid) {
                alert('Please fill in all required fields');
                return;
            }

            // Hide current step
            $('.form-step').removeClass('active').hide();
            $('#step' + step).addClass('active').show();

            // Update step indicator
            $('.step').removeClass('active').removeClass('completed');
            for (var i = 1; i < step; i++) {
                $('.step').eq(i - 1).addClass('completed');
            }
            $('.step').eq(step - 1).addClass('active');

            // Scroll to top
            $('html, body').animate({scrollTop: 0}, 'slow');
        }

        function prevStep(step) {
            $('.form-step').removeClass('active').hide();
            $('#step' + step).addClass('active').show();

            // Update step indicator
            $('.step').removeClass('active').removeClass('completed');
            for (var i = 1; i < step; i++) {
                $('.step').eq(i - 1).addClass('completed');
            }
            $('.step').eq(step - 1).addClass('active');

            // Scroll to top
            $('html, body').animate({scrollTop: 0}, 'slow');
        }

        // Password match validation
        $('#confirm_password').on('blur', function() {
            if ($(this).val() !== $('#password').val()) {
                $(this).addClass('is-invalid');
                alert('Passwords do not match!');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // Form submission
        $('#application-form').on('submit', function(e) {
            if ($('#password').val() !== $('#confirm_password').val()) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    </script>
</body>
</html>
