<?php
session_start();
if(isset($_SESSION['customer_id'])){
    header("location: customer_dashboard.php");
    exit;
}

require_once __DIR__ . '/includes/portal_layout.php';

$pageHead = <<<'HTML'
<style>
    /* Step indicator */
    .step-indicator { display: flex; gap: 8px; margin: 0 0 24px; padding: 0; list-style: none; }
    .step { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; position: relative; }
    .step:not(:last-child)::after {
        content: ''; position: absolute; top: 17px; left: calc(50% + 22px); right: calc(-50% + 22px);
        height: 2px; background: var(--line);
    }
    .step.completed:not(:last-child)::after { background: var(--brand); }
    .step-number {
        width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
        background: var(--bg); color: var(--muted); font-weight: 600; border: 2px solid var(--line);
    }
    .step.active .step-number { background: var(--brand); border-color: var(--brand); color: #fff; }
    .step.completed .step-number { background: var(--brand-50); border-color: var(--brand); color: var(--brand); }
    .step-label { font-size: .78rem; color: var(--muted); font-weight: 500; line-height: 1.2; }
    .step.active .step-label { color: var(--ink); }

    .reg-section + .reg-section { margin-top: 8px; padding-top: 18px; border-top: 1px solid var(--line); }

    /* File inputs: same height as the other fields, long names truncate */
    .portal .custom-file, .portal .custom-file-input { height: 46px; }
    .portal .custom-file-label {
        height: 46px; line-height: 30px; border-radius: var(--radius-sm); border-color: #cfd8d3;
        overflow: hidden; white-space: nowrap; text-overflow: ellipsis; padding-right: 96px; font-size: 15px; font-weight: 400;
    }
    .portal .custom-file-label::after { height: 44px; line-height: 30px; content: "Browse"; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; }
    .portal .custom-file-input:focus ~ .custom-file-label { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(26, 95, 42, .15); }
    .file-requirements { background: var(--bg); padding: 12px 14px; border-radius: var(--radius-sm); margin-top: 8px; font-size: .84rem; color: var(--ink-2); }
    .file-requirements ul { margin: 2px 0 6px; padding-left: 18px; }

    .step-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; margin-top: 8px; }
    .step-actions .btn-primary { margin-left: auto; }
    @media (max-width: 575.98px) {
        .step-actions { flex-direction: column-reverse; }
        .step-actions .btn { width: 100%; }
    }
    .portal .custom-control-label { font-weight: 400; }
</style>
HTML;

$pageScripts = <<<'HTML'
<script>
    // Update file input labels
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').text(fileName || 'Choose file...');
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
HTML;

portal_auth_header('Customer Registration', ['head' => $pageHead, 'wide' => true]);
?>
    <h1>Loan Application Form</h1>
    <p class="auth-lead">Complete the form below to apply for a loan. Fields marked <span class="req"></span> are required.</p>

    <!-- Step Indicator -->
    <ol class="step-indicator" aria-label="Application steps">
        <li class="step active">
            <span class="step-number">1</span>
            <span class="step-label">Personal Info</span>
        </li>
        <li class="step">
            <span class="step-number">2</span>
            <span class="step-label">Documents</span>
        </li>
        <li class="step">
            <span class="step-number">3</span>
            <span class="step-label">Account</span>
        </li>
    </ol>

    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <?php
            echo $_SESSION['error_msg'];
            unset($_SESSION['error_msg']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <form id="application-form" method="POST" action="customer_register_process.php" enctype="multipart/form-data">

        <!-- Step 1: Personal Information -->
        <div class="form-step active" id="step1">
            <div class="reg-section">
                <h2 class="p-card-title"><i class="fas fa-user"></i> Personal Details</h2>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reg_firstname" class="req">First Name</label>
                            <input type="text" class="form-control" id="reg_firstname" name="firstname"
                                   autocomplete="given-name" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reg_middlename">Middle Name</label>
                            <input type="text" class="form-control" id="reg_middlename" name="middlename"
                                   autocomplete="additional-name">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reg_lastname" class="req">Last Name</label>
                            <input type="text" class="form-control" id="reg_lastname" name="lastname"
                                   autocomplete="family-name" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reg_tax_id">NRC Number</label>
                    <input type="text" id="reg_tax_id" name="tax_id" placeholder="Enter your NRC Number" class="form-control">
                </div>
            </div>

            <div class="reg-section">
                <h2 class="p-card-title"><i class="fas fa-address-book"></i> Contact Details</h2>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="reg_email" class="req">Email Address</label>
                            <input type="email" class="form-control" id="reg_email" name="email"
                                   autocomplete="email" autocapitalize="none" spellcheck="false" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="reg_contact_no" class="req">Contact Number</label>
                            <input type="tel" class="form-control" id="reg_contact_no" name="contact_no"
                                   placeholder="+1234567890" autocomplete="tel" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reg_address" class="req">Address</label>
                    <textarea class="form-control" id="reg_address" name="address" rows="3"
                              autocomplete="street-address" required></textarea>
                </div>
            </div>

            <div class="step-actions">
                <button type="button" class="btn btn-primary btn-lg" onclick="nextStep(2)">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Step 2: Document Upload -->
        <div class="form-step" id="step2" style="display: none;">
            <h2 class="p-card-title"><i class="fas fa-file-upload"></i> Required Documents</h2>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Important:</strong> Please upload clear, legible copies of the following documents.
            </div>

            <div class="form-group">
                <label for="id_document" class="req">
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
                <label for="employment_proof" class="req">
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
                <label for="payslip" class="req">
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

            <div class="step-actions">
                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="prevStep(1)">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-primary btn-lg" onclick="nextStep(3)">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Step 3: Account Setup -->
        <div class="form-step" id="step3" style="display: none;">
            <h2 class="p-card-title"><i class="fas fa-user-lock"></i> Create Your Account</h2>

            <div class="alert alert-warning">
                <i class="fas fa-shield-alt"></i>
                <strong>Security Tip:</strong> Choose a strong password with at least 8 characters,
                including uppercase, lowercase, and numbers.
            </div>

            <div class="form-group">
                <label for="reg_username" class="req">Username</label>
                <input type="text" class="form-control" id="reg_username" name="username"
                       placeholder="Choose a unique username" autocomplete="username"
                       autocapitalize="none" spellcheck="false" required>
                <small class="form-text text-muted">
                    This will be used to log in to your account
                </small>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password" class="req">Password</label>
                        <input type="password" class="form-control" name="password"
                               id="password" minlength="8" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="confirm_password" class="req">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password"
                               id="confirm_password" minlength="8" autocomplete="new-password" required>
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

            <div class="step-actions">
                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="prevStep(2)">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> Submit Application
                </button>
            </div>
        </div>

    </form>

    <div class="auth-divider">or</div>

    <p class="text-center mb-0">Already have an account?
        <a href="customer_login.php" class="font-weight-bold">
            <i class="fas fa-sign-in-alt"></i> Login Here
        </a>
    </p>
<?php
portal_auth_footer(['scripts' => $pageScripts]);
