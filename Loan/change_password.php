<?php 
require_once 'includes/security.php';
Security::secureSession();
Security::requireLogin();
?>
<style>
    .password-container {
        max-width: 500px;
        margin: 0 auto;
    }
    .password-strength {
        height: 5px;
        margin-top: 5px;
        border-radius: 3px;
        transition: all 0.3s;
    }
    .strength-weak { background: #dc3545; width: 25%; }
    .strength-fair { background: #ffc107; width: 50%; }
    .strength-good { background: #17a2b8; width: 75%; }
    .strength-strong { background: #28a745; width: 100%; }
    .requirement {
        font-size: 0.85rem;
        color: #6c757d;
    }
    .requirement.met {
        color: #28a745;
    }
    .requirement.met::before {
        content: '✓ ';
    }
    .requirement:not(.met)::before {
        content: '○ ';
    }
</style>

<div class="container-fluid p-4">
    <div class="card password-container">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fa fa-key mr-2"></i>Change Password</h5>
        </div>
        <div class="card-body">
            <div id="alert-container"></div>
            
            <form id="change-password-form">
                <?php echo Security::csrfField(); ?>
                
                <div class="form-group">
                    <label for="current_password">Current Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="password-strength" id="password-strength"></div>
                    <div class="mt-2">
                        <div class="requirement" id="req-length">At least 8 characters</div>
                        <div class="requirement" id="req-number">Contains a number</div>
                        <div class="requirement" id="req-letter">Contains a letter</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <small id="match-status" class="form-text"></small>
                </div>
                
                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-primary btn-block" id="submit-btn">
                        <i class="fa fa-save mr-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
        <div class="card-footer text-muted small">
            <i class="fa fa-info-circle mr-1"></i>
            After changing your password, you will remain logged in. Use your new password for future logins.
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Toggle password visibility
    $('.toggle-password').click(function() {
        const targetId = $(this).data('target');
        const input = $('#' + targetId);
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Password strength checker
    $('#new_password').on('input', function() {
        const password = $(this).val();
        let strength = 0;
        
        // Check requirements
        const hasLength = password.length >= 8;
        const hasNumber = /\d/.test(password);
        const hasLetter = /[a-zA-Z]/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        const hasUpper = /[A-Z]/.test(password);
        const hasLower = /[a-z]/.test(password);
        
        // Update requirement indicators
        $('#req-length').toggleClass('met', hasLength);
        $('#req-number').toggleClass('met', hasNumber);
        $('#req-letter').toggleClass('met', hasLetter);
        
        // Calculate strength
        if (hasLength) strength++;
        if (hasNumber) strength++;
        if (hasLetter) strength++;
        if (hasSpecial) strength++;
        if (hasUpper && hasLower) strength++;
        
        // Update strength bar
        const strengthBar = $('#password-strength');
        strengthBar.removeClass('strength-weak strength-fair strength-good strength-strong');
        
        if (password.length === 0) {
            strengthBar.css('width', '0');
        } else if (strength <= 2) {
            strengthBar.addClass('strength-weak');
        } else if (strength === 3) {
            strengthBar.addClass('strength-fair');
        } else if (strength === 4) {
            strengthBar.addClass('strength-good');
        } else {
            strengthBar.addClass('strength-strong');
        }
        
        checkPasswordMatch();
    });
    
    // Password match checker
    $('#confirm_password').on('input', checkPasswordMatch);
    
    function checkPasswordMatch() {
        const newPass = $('#new_password').val();
        const confirmPass = $('#confirm_password').val();
        const matchStatus = $('#match-status');
        
        if (confirmPass.length === 0) {
            matchStatus.text('').removeClass('text-success text-danger');
        } else if (newPass === confirmPass) {
            matchStatus.text('Passwords match').removeClass('text-danger').addClass('text-success');
        } else {
            matchStatus.text('Passwords do not match').removeClass('text-success').addClass('text-danger');
        }
    }
    
    // Form submission
    $('#change-password-form').submit(function(e) {
        e.preventDefault();
        
        const newPass = $('#new_password').val();
        const confirmPass = $('#confirm_password').val();
        
        // Validation
        if (newPass !== confirmPass) {
            showAlert('Passwords do not match', 'danger');
            return;
        }
        
        if (newPass.length < 8) {
            showAlert('Password must be at least 8 characters', 'danger');
            return;
        }
        
        // Disable submit button
        const btn = $('#submit-btn');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-2"></i>Changing...');
        
        $.ajax({
            url: 'ajax_secure.php?action=change_password',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showAlert(response.message, 'success');
                    $('#change-password-form')[0].reset();
                    $('#password-strength').css('width', '0');
                    $('.requirement').removeClass('met');
                    $('#match-status').text('').removeClass('text-success text-danger');
                } else {
                    showAlert(response.message, 'danger');
                }
            },
            error: function() {
                showAlert('An error occurred. Please try again.', 'danger');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa fa-save mr-2"></i>Change Password');
            }
        });
    });
    
    function showAlert(message, type) {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        $('#alert-container').html(alert);
    }
});
</script>
