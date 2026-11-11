<?php
session_start();
if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

include('db_connect.php');

$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT * FROM borrowers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM borrower_documents WHERE borrower_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$documents = $stmt->get_result();
$stmt->close();

require_once __DIR__ . '/includes/portal_layout.php';

$page_head = <<<'HTML'
<style>
    .profile-summary { display: flex; align-items: center; gap: 16px; }
    .profile-avatar {
        flex: 0 0 64px; width: 64px; height: 64px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: var(--brand); color: var(--gold); font-size: 1.6rem; font-weight: 700;
    }
    .profile-summary-main { min-width: 0; }
    .profile-summary-main h2 { font-size: 1.15rem; margin: 0; overflow-wrap: anywhere; }
    .profile-summary-main p { margin: 2px 0 0; color: var(--muted); font-size: .9rem; overflow-wrap: anywhere; }
    .doc-status-row { padding: 14px 0; border-top: 1px solid var(--line); }
    .doc-status-row:first-of-type { border-top: 0; padding-top: 0; }
    .doc-status-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .doc-status-head h3 { font-size: .95rem; margin: 0; }
    .doc-status-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
    .doc-status-actions .btn { flex: 1 1 auto; }
    .portal .custom-file, .portal .custom-file-input, .portal .custom-file-label { min-height: 44px; }
    .portal .custom-file-label { display: flex; align-items: center; overflow: hidden; }
    .portal .custom-file-label::after { height: 100%; display: flex; align-items: center; }
</style>
HTML;

$avatar_name = trim((string)($customer['firstname'] ?? ''));
$avatar_initial = $avatar_name !== '' ? strtoupper(substr($avatar_name, 0, 1)) : '';

portal_header('My Profile', 'profile', ['head' => $page_head]);
?>

            <div class="page-head">
                <div>
                    <h1>My Profile</h1>
                    <p>Your personal details, password and documents</p>
                </div>
            </div>

            <?php if(isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="p-card">
                <div class="profile-summary">
                    <span class="profile-avatar" aria-hidden="true">
                        <?php if($avatar_initial !== ''): ?>
                            <?php echo htmlspecialchars($avatar_initial); ?>
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </span>
                    <div class="profile-summary-main">
                        <h2><?php echo htmlspecialchars($customer['firstname'] . ' ' . $customer['lastname']); ?></h2>
                        <p><?php echo htmlspecialchars((string)$customer['email']); ?></p>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Personal Information -->
                <div class="col-lg-8">
                    <div class="p-card">
                        <h2 class="p-card-title">
                            <i class="fas fa-user-edit"></i> Personal Information
                        </h2>

                        <form method="POST" action="customer_update_profile.php">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="req" for="pf-firstname">First Name</label>
                                        <input type="text" class="form-control" name="firstname" id="pf-firstname" autocomplete="given-name"
                                               value="<?php echo htmlspecialchars((string)$customer['firstname']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pf-middlename">Middle Name</label>
                                        <input type="text" class="form-control" name="middlename" id="pf-middlename" autocomplete="additional-name"
                                               value="<?php echo htmlspecialchars((string)$customer['middlename']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="req" for="pf-lastname">Last Name</label>
                                        <input type="text" class="form-control" name="lastname" id="pf-lastname" autocomplete="family-name"
                                               value="<?php echo htmlspecialchars((string)$customer['lastname']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="req" for="pf-email">Email Address</label>
                                        <input type="email" class="form-control" name="email" id="pf-email" autocomplete="email"
                                               value="<?php echo htmlspecialchars((string)$customer['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="req" for="pf-contact">Contact Number</label>
                                        <input type="tel" class="form-control" name="contact_no" id="pf-contact" autocomplete="tel"
                                               value="<?php echo htmlspecialchars((string)$customer['contact_no']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="req" for="pf-address">Address</label>
                                <textarea class="form-control" name="address" id="pf-address" rows="3" autocomplete="street-address" required><?php echo htmlspecialchars((string)$customer['address']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label class="req" for="pf-tax-id">Tax ID / SSN</label>
                                <input type="text" class="form-control" name="tax_id" id="pf-tax-id"
                                       value="<?php echo htmlspecialchars((string)$customer['tax_id']); ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="p-card">
                        <h2 class="p-card-title">
                            <i class="fas fa-lock"></i> Change Password
                        </h2>

                        <form method="POST" action="customer_change_password.php">
                            <div class="form-group">
                                <label class="req" for="current_password">Current Password</label>
                                <input type="password" class="form-control" name="current_password" id="current_password" autocomplete="current-password" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="req" for="new_password">New Password</label>
                                        <input type="password" class="form-control" name="new_password"
                                               id="new_password" minlength="8" autocomplete="new-password" required>
                                        <small class="form-text text-muted">At least 8 characters</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="req" for="confirm_password">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password"
                                               id="confirm_password" minlength="8" autocomplete="new-password" required>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-gold btn-lg">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Documents Section -->
                <div class="col-lg-4">
                    <div class="p-card">
                        <h2 class="p-card-title">
                            <i class="fas fa-file-upload"></i> My Documents
                        </h2>

                        <?php
                        $doc_types = array('id' => 'Government ID', 'employment_proof' => 'Employment Proof', 'payslip' => 'Pay Slip');
                        $doc_array = array();
                        while($doc = $documents->fetch_assoc()) {
                            $doc_array[$doc['document_type']] = $doc;
                        }

                        // Map the Bootstrap status colours to portal badges
                        $doc_badge_map = array('secondary' => 'p-badge-neutral', 'warning' => 'p-badge-pending', 'success' => 'p-badge-done', 'danger' => 'p-badge-denied');

                        foreach($doc_types as $key => $label):
                            $doc_status_class = 'secondary';
                            $doc_status_text = 'Not Uploaded';
                            $has_doc = false;

                            if(isset($doc_array[$key])) {
                                $has_doc = true;
                                $doc = $doc_array[$key];
                                $doc_status_class = $doc['status'] == 0 ? 'warning' : ($doc['status'] == 1 ? 'success' : 'danger');
                                $doc_status_text = $doc['status'] == 0 ? 'Pending Verification' : ($doc['status'] == 1 ? 'Verified' : 'Rejected');
                            }
                        ?>

                        <div class="doc-status-row">
                            <div class="doc-status-head">
                                <h3><?php echo $label; ?></h3>
                                <span class="p-badge <?php echo $doc_badge_map[$doc_status_class]; ?>"><?php echo $doc_status_text; ?></span>
                            </div>

                            <?php if($has_doc && $doc['verification_notes']): ?>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-comment"></i> <?php echo htmlspecialchars((string)$doc['verification_notes']); ?>
                                </small>
                            </div>
                            <?php endif; ?>

                            <div class="doc-status-actions">
                            <?php if($has_doc): ?>
                                <a href="<?php echo htmlspecialchars((string)$doc['file_path']); ?>" target="_blank" rel="noopener" class="btn btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if($doc['status'] != 1): ?>
                                <button type="button" class="btn btn-gold update-doc-btn"
                                        data-type="<?php echo $key; ?>"
                                        data-label="<?php echo $label; ?>">
                                    <i class="fas fa-upload"></i> Update
                                </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="button" class="btn btn-gold update-doc-btn"
                                        data-type="<?php echo $key; ?>"
                                        data-label="<?php echo $label; ?>">
                                    <i class="fas fa-upload"></i> Upload
                                </button>
                            <?php endif; ?>
                            </div>
                        </div>

                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

    <!-- Document Update Modal -->
    <div class="modal fade" id="update-doc-modal" tabindex="-1" role="dialog" aria-labelledby="update-doc-modal-title">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="update-doc-modal-title"><i class="fas fa-upload"></i> Update Document</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="customer_update_document.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="document_type" id="doc-type">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            You are updating: <strong id="doc-label"></strong>
                        </div>

                        <div class="form-group">
                            <label class="req" for="update-doc-file">Select New File</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="update-doc-file" name="document"
                                       accept=".jpg,.jpeg,.png,.pdf" required>
                                <label class="custom-file-label" for="update-doc-file">Choose file...</label>
                            </div>
                            <small class="form-text text-muted">
                                Accepted formats: JPG, PNG, PDF (Max 5MB)
                            </small>
                        </div>

                        <div class="form-group mb-0">
                            <label for="update-doc-reason">Reason for Update</label>
                            <textarea class="form-control" name="reason" id="update-doc-reason" rows="3"
                                      placeholder="Please explain why you're updating this document..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php
portal_footer(['scripts' => <<<'HTML'
<script>
    // Update document modal
    $('.update-doc-btn').click(function(){
        var type = $(this).data('type');
        var label = $(this).data('label');

        $('#doc-type').val(type);
        $('#doc-label').text(label);
        $('#update-doc-modal').modal('show');
    });

    // Update file input label
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    // Password match validation
    $('#confirm_password').on('blur', function() {
        if ($(this).val() !== $('#new_password').val()) {
            $(this).addClass('is-invalid');
            alert('Passwords do not match!');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
</script>
HTML
]);
?>
