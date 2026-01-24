<?php
include 'db_connect.php';
require_once 'includes/security.php';

// Get current admin info
$admin_id = $_SESSION['login_id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$admin) {
    echo '<div class="alert alert-danger">Admin not found</div>';
    exit;
}
?>

<style>
    .profile-container {
        max-width: 800px;
        margin: 0 auto;
    }
    .profile-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }
    .profile-avatar {
        width: 100px;
        height: 100px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 40px;
        border: 3px solid rgba(255,255,255,0.5);
    }
    .profile-name {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .profile-role {
        opacity: 0.9;
        font-size: 0.95rem;
    }
    .profile-body {
        padding: 25px;
    }
    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        width: 150px;
        color: #666;
        font-weight: 500;
    }
    .info-value {
        flex: 1;
        color: #333;
    }
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #667eea;
    }
    .btn-change-password {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-change-password:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    .password-requirements {
        font-size: 0.85rem;
        color: #666;
        margin-top: 10px;
    }
    .password-requirements li {
        margin-bottom: 5px;
    }
    .form-group label {
        font-weight: 500;
        color: #333;
    }
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fa fa-user-circle"></i> My Profile</h4>
            <p class="text-muted mb-0">View and manage your account settings</p>
        </div>
    </div>

    <div class="profile-container">
        <!-- Profile Info Card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fa fa-user"></i>
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($admin['name']); ?></div>
                <div class="profile-role">
                    <?php
                    $role = 'Staff';
                    if($admin['type'] == 1) $role = 'Administrator';
                    elseif($admin['type'] == 2) $role = 'Manager';
                    elseif($admin['type'] == 3) $role = 'Staff';
                    echo $role;
                    ?>
                </div>
            </div>
            <div class="profile-body">
                <h5 class="section-title"><i class="fa fa-info-circle"></i> Account Information</h5>

                <div class="info-row">
                    <div class="info-label">Username</div>
                    <div class="info-value"><?php echo htmlspecialchars($admin['username']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($admin['name']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Role</div>
                    <div class="info-value">
                        <span class="badge badge-primary"><?php echo $role; ?></span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Account Status</div>
                    <div class="info-value">
                        <span class="badge badge-success">Active</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="profile-card">
            <div class="profile-body">
                <h5 class="section-title"><i class="fa fa-lock"></i> Change Password</h5>

                <form id="change-password-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" class="form-control" name="current_password" id="current_password" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" class="form-control" name="new_password" id="new_password" required minlength="6">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required minlength="6">
                            </div>
                        </div>
                    </div>

                    <ul class="password-requirements">
                        <li>Password must be at least 6 characters long</li>
                        <li>Use a mix of letters and numbers for better security</li>
                    </ul>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-change-password">
                            <i class="fa fa-save"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Profile Card -->
        <div class="profile-card">
            <div class="profile-body">
                <h5 class="section-title"><i class="fa fa-edit"></i> Update Profile</h5>

                <form id="update-profile-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" class="form-control" name="name" id="profile_name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" class="form-control" name="username" id="profile_username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-change-password">
                            <i class="fa fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Change Password Form
$('#change-password-form').submit(function(e) {
    e.preventDefault();

    var newPass = $('#new_password').val();
    var confirmPass = $('#confirm_password').val();

    if(newPass !== confirmPass) {
        alert_toast('New passwords do not match', 'error');
        return;
    }

    if(newPass.length < 6) {
        alert_toast('Password must be at least 6 characters', 'error');
        return;
    }

    start_load();
    $.ajax({
        url: 'ajax.php?action=admin_change_password',
        method: 'POST',
        data: $(this).serialize(),
        success: function(resp) {
            if(resp == 1) {
                alert_toast('Password changed successfully', 'success');
                $('#change-password-form')[0].reset();
            } else {
                try {
                    var response = JSON.parse(resp);
                    alert_toast(response.message || 'Error changing password', 'error');
                } catch(e) {
                    alert_toast('Error changing password', 'error');
                }
            }
            end_load();
        },
        error: function() {
            alert_toast('Server error', 'error');
            end_load();
        }
    });
});

// Update Profile Form
$('#update-profile-form').submit(function(e) {
    e.preventDefault();

    start_load();
    $.ajax({
        url: 'ajax.php?action=admin_update_profile',
        method: 'POST',
        data: $(this).serialize(),
        success: function(resp) {
            if(resp == 1) {
                alert_toast('Profile updated successfully', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                try {
                    var response = JSON.parse(resp);
                    alert_toast(response.message || 'Error updating profile', 'error');
                } catch(e) {
                    alert_toast('Error updating profile', 'error');
                }
            }
            end_load();
        },
        error: function() {
            alert_toast('Server error', 'error');
            end_load();
        }
    });
});
</script>
