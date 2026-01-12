<?php
session_start();
if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

include('db_connect.php');

$customer_id = $_SESSION['customer_id'];

// Get all documents for this customer
$docs_query = "SELECT * FROM borrower_documents 
               WHERE borrower_id = $customer_id 
               ORDER BY upload_date DESC";
$docs_result = $conn->query($docs_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents - Customer Portal</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="assets/font-awesome/css/all.min.css">
    
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            margin: 0 10px;
        }
        
        .navbar-nav .nav-link:hover {
            color: white !important;
        }
        
        .container-main {
            margin-top: 30px;
            margin-bottom: 50px;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 1.1rem;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .document-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .document-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .doc-type {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        
        .doc-icon {
            font-size: 2.5rem;
            color: #667eea;
            margin-right: 15px;
        }
        
        .status-badge {
            padding: 6px 15px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-verified {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .doc-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }
        
        .info-item {
            font-size: 0.9rem;
            color: #666;
        }
        
        .info-label {
            font-weight: 600;
            color: #333;
        }
        
        .doc-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-custom {
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.9rem;
            text-decoration: none;
        }
        
        .btn-view {
            background: #667eea;
            color: white;
            border: none;
        }
        
        .btn-view:hover {
            background: #5568d3;
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-update {
            background: #28a745;
            color: white;
            border: none;
        }
        
        .btn-update:hover {
            background: #218838;
            transform: translateY(-2px);
            color: white;
        }
        
        .upload-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .upload-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #ddd;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-text {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .verification-notes {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            color: #856404;
        }
        
        .required-docs {
            background: #e8f4fd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .required-docs h6 {
            color: #0c5460;
            margin-bottom: 10px;
        }
        
        .required-docs ul {
            margin: 0;
            padding-left: 20px;
            color: #0c5460;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="customer_dashboard.php">
                <i class="fas fa-hand-holding-usd"></i> LoanPro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="customer_dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_my_loans.php">
                            <i class="fas fa-file-invoice-dollar"></i> My Loans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="customer_my_documents.php">
                            <i class="fas fa-file-upload"></i> My Documents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_apply_loan.php">
                            <i class="fas fa-plus-circle"></i> Apply for Loan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container container-main">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-file-upload"></i> My Documents
            </h1>
            <p class="page-subtitle">Upload and manage your required documents</p>
        </div>

        <!-- Success Message -->
        <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_msg']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_msg']); endif; ?>

        <!-- Error Message -->
        <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_msg']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_msg']); endif; ?>

        <!-- Upload New Document -->
        <div class="upload-section">
            <h3 class="upload-title">
                <i class="fas fa-cloud-upload-alt"></i> Upload New Document
            </h3>
            
            <div class="required-docs">
                <h6><i class="fas fa-info-circle"></i> Required Documents:</h6>
                <ul>
                    <li><strong>National ID / Passport</strong> - Government-issued identification</li>
                    <li><strong>Employment Proof</strong> - Employment letter or contract</li>
                    <li><strong>Recent Pay Slip</strong> - Last 3 months payslip</li>
                </ul>
            </div>
            
            <form action="customer_update_document.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">Document Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="document_type" required>
                                <option value="">Select Document Type</option>
                                <option value="id">National ID / Passport</option>
                                <option value="employment_proof">Employment Proof</option>
                                <option value="payslip">Recent Pay Slip</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">Choose File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="document" 
                                   accept=".pdf,.jpg,.jpeg,.png" required>
                            <small class="text-muted">Accepted: PDF, JPG, PNG (Max 5MB)</small>
                        </div>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-group w-100">
                            <button type="submit" class="btn btn-primary-custom w-100">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Documents List -->
        <h4 class="mb-3"><i class="fas fa-folder-open"></i> Uploaded Documents</h4>
        
        <?php if($docs_result && $docs_result->num_rows > 0): ?>
            <?php while($doc = $docs_result->fetch_assoc()): 
                // Format document type
                $type_labels = [
                    'id' => 'National ID / Passport',
                    'employment_proof' => 'Employment Proof',
                    'payslip' => 'Recent Pay Slip'
                ];
                $type_display = $type_labels[$doc['document_type']] ?? ucfirst(str_replace('_', ' ', $doc['document_type']));
                
                // Status display
                $status_text = 'Pending Verification';
                $status_class = 'status-pending';
                $status_icon = 'fa-clock';
                
                switch($doc['status']) {
                    case 1:
                        $status_text = 'Verified';
                        $status_class = 'status-verified';
                        $status_icon = 'fa-check-circle';
                        break;
                    case 2:
                        $status_text = 'Rejected';
                        $status_class = 'status-rejected';
                        $status_icon = 'fa-times-circle';
                        break;
                }
                
                // File size in KB/MB
                $file_size = $doc['file_size'];
                if($file_size < 1024) {
                    $size_display = $file_size . ' B';
                } elseif($file_size < 1048576) {
                    $size_display = round($file_size / 1024, 2) . ' KB';
                } else {
                    $size_display = round($file_size / 1048576, 2) . ' MB';
                }
                
                // File icon based on extension
                $ext = pathinfo($doc['file_name'], PATHINFO_EXTENSION);
                $file_icon = 'fa-file-alt';
                if(in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    $file_icon = 'fa-file-image';
                } elseif($ext == 'pdf') {
                    $file_icon = 'fa-file-pdf';
                }
            ?>
            
            <div class="document-card">
                <div class="doc-header">
                    <div style="display: flex; align-items: center;">
                        <i class="fas <?php echo $file_icon; ?> doc-icon"></i>
                        <div>
                            <div class="doc-type"><?php echo $type_display; ?></div>
                            <small class="text-muted"><?php echo $doc['file_name']; ?></small>
                        </div>
                    </div>
                    <div class="status-badge <?php echo $status_class; ?>">
                        <i class="fas <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                    </div>
                </div>
                
                <div class="doc-info">
                    <div class="info-item">
                        <span class="info-label">Uploaded:</span> 
                        <?php echo date('M d, Y h:i A', strtotime($doc['upload_date'])); ?>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">File Size:</span> 
                        <?php echo $size_display; ?>
                    </div>
                    
                    <?php if($doc['status'] != 0 && $doc['verification_date']): ?>
                    <div class="info-item">
                        <span class="info-label">Reviewed:</span> 
                        <?php echo date('M d, Y', strtotime($doc['verification_date'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if($doc['status'] == 2 && !empty($doc['verification_notes'])): ?>
                <div class="verification-notes">
                    <strong><i class="fas fa-exclamation-triangle"></i> Rejection Reason:</strong><br>
                    <?php echo htmlspecialchars($doc['verification_notes']); ?>
                    <br><small>Please upload a new document to replace this one.</small>
                </div>
                <?php endif; ?>
                
                <div class="doc-actions">
                    <a href="<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-view btn-custom">
                        <i class="fas fa-eye"></i> View
                    </a>
                    
                    <a href="<?php echo $doc['file_path']; ?>" download class="btn btn-view btn-custom" style="background: #6c757d;">
                        <i class="fas fa-download"></i> Download
                    </a>
                    
                    <?php if($doc['status'] == 0 || $doc['status'] == 2): ?>
                    <a href="?replace=<?php echo $doc['id']; ?>&type=<?php echo $doc['document_type']; ?>" 
                       class="btn btn-update btn-custom"
                       onclick="return confirm('Replace this document with a new file?');">
                        <i class="fas fa-sync-alt"></i> Replace
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php endwhile; ?>
            
        <?php else: ?>
            
            <!-- Empty State -->
            <div class="document-card">
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <div class="empty-text">
                        No documents uploaded yet
                    </div>
                    <p class="text-muted">Use the form above to upload your required documents</p>
                </div>
            </div>
            
        <?php endif; ?>

    </div>

    <!-- Bootstrap JS -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
