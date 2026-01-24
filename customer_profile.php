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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Brian Investments</title>
    
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            background: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
            position: fixed;
            width: 250px;
        }
        .sidebar .brand {
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 25px;
            margin: 5px 15px;
            border-radius: 8px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        .profile-header {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: #667eea;
        }
        .section-title {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #667eea;
            font-weight: 600;
        }
        .document-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .document-card:hover {
            border-color: #667eea;
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <i class="fas fa-hand-holding-usd"></i><br>
            Brian Investments
        </div>
        <nav class="nav flex-column mt-4">
            <a class="nav-link" href="customer_dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a class="nav-link" href="customer_apply_loan.php">
                <i class="fas fa-plus-circle"></i> Apply for Loan
            </a>
            <a class="nav-link" href="customer_my_loans.php">
                <i class="fas fa-list"></i> My Loans
            </a>
            <a class="nav-link" href="customer_my_documents.php">
                <i class="fas fa-file-alt"></i> My Documents
            </a>
            <a class="nav-link active" href="customer_profile.php">
                <i class="fas fa-user"></i> My Profile
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            
            <?php if(isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h3><?php echo $customer['firstname'] . ' ' . $customer['lastname']; ?></h3>
                <p class="mb-0"><?php echo $customer['email']; ?></p>
            </div>

            <div class="row">
                <!-- Personal Information -->
                <div class="col-md-8">
                    <div class="profile-card">
                        <h5 class="section-title">
                            <i class="fas fa-user-edit"></i> Personal Information
                        </h5>
                        
                        <form method="POST" action="customer_update_profile.php">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>First Name</label>
                                        <input type="text" class="form-control" name="firstname" 
                                               value="<?php echo $customer['firstname']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Middle Name</label>
                                        <input type="text" class="form-control" name="middlename" 
                                               value="<?php echo $customer['middlename']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Last Name</label>
                                        <input type="text" class="form-control" name="lastname" 
                                               value="<?php echo $customer['lastname']; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email Address</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo $customer['email']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Contact Number</label>
                                        <input type="text" class="form-control" name="contact_no" 
                                               value="<?php echo $customer['contact_no']; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Address</label>
                                <textarea class="form-control" name="address" rows="3" required><?php echo $customer['address']; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Tax ID / SSN</label>
                                <input type="text" class="form-control" name="tax_id" 
                                       value="<?php echo $customer['tax_id']; ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="profile-card">
                        <h5 class="section-title">
                            <i class="fas fa-lock"></i> Change Password
                        </h5>
                        
                        <form method="POST" action="customer_change_password.php">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" class="form-control" name="new_password" 
                                               id="new_password" minlength="8" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" 
                                               id="confirm_password" minlength="8" required>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Documents Section -->
                <div class="col-md-4">
                    <div class="profile-card">
                        <h5 class="section-title">
                            <i class="fas fa-file-upload"></i> My Documents
                        </h5>
                        
                        <?php 
                        $doc_types = array('id' => 'Government ID', 'employment_proof' => 'Employment Proof', 'payslip' => 'Pay Slip');
                        $doc_array = array();
                        while($doc = $documents->fetch_assoc()) {
                            $doc_array[$doc['document_type']] = $doc;
                        }
                        
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
                        
                        <div class="document-card">
                            <h6><?php echo $label; ?></h6>
                            <span class="badge badge-<?php echo $doc_status_class; ?>"><?php echo $doc_status_text; ?></span>
                            <br><br>
                            
                            <?php if($has_doc): ?>
                                <a href="<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if($doc['status'] != 1): ?>
                                <button class="btn btn-sm btn-primary update-doc-btn" 
                                        data-type="<?php echo $key; ?>"
                                        data-label="<?php echo $label; ?>">
                                    <i class="fas fa-upload"></i> Update
                                </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-sm btn-primary update-doc-btn" 
                                        data-type="<?php echo $key; ?>"
                                        data-label="<?php echo $label; ?>">
                                    <i class="fas fa-upload"></i> Upload
                                </button>
                            <?php endif; ?>
                            
                            <?php if($has_doc && $doc['verification_notes']): ?>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-comment"></i> <?php echo $doc['verification_notes']; ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Update Modal -->
    <div class="modal fade" id="update-doc-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-upload"></i> Update Document</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
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
                            <label>Select New File</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="document" 
                                       accept=".jpg,.jpeg,.png,.pdf" required>
                                <label class="custom-file-label">Choose file...</label>
                            </div>
                            <small class="form-text text-muted">
                                Accepted formats: JPG, PNG, PDF (Max 5MB)
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Reason for Update</label>
                            <textarea class="form-control" name="reason" rows="3" 
                                      placeholder="Please explain why you're updating this document..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
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
</body>
</html>
