<?php
session_start();
if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

include('db_connect.php');

$customer_id = $_SESSION['customer_id'];

// Get all loans for this customer (simplified - no payment details exposed)
$stmt = $conn->prepare("SELECT l.*, lt.type_name, lp.months, lp.interest_percentage
                FROM loan_list l
                LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
                LEFT JOIN loan_plan lp ON l.plan_id = lp.id
                WHERE l.borrower_id = ?
                ORDER BY l.date_created DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$loans_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Loans - Customer Portal</title>
    
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
        
        .loan-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .loan-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        
        .loan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .loan-ref {
            font-size: 1.3rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-released {
            background: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-denied {
            background: #f8d7da;
            color: #721c24;
        }
        
        .loan-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .detail-label {
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        
        .btn-custom {
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
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
            margin-bottom: 20px;
        }
        
        .loan-purpose {
            margin-top: 15px;
            padding: 15px;
            background: #f0f4ff;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .info-note {
            background: #e8f4fd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 0.9rem;
            color: #0c5460;
        }
        
        .info-note i {
            margin-right: 8px;
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
                        <a class="nav-link active" href="customer_my_loans.php">
                            <i class="fas fa-file-invoice-dollar"></i> My Loans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_my_documents.php">
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
                        <a class="nav-link text-danger" href="customer_logout.php" style="color: #dc3545 !important;">
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
                <i class="fas fa-file-invoice-dollar"></i> My Loan Applications
            </h1>
            <p class="page-subtitle">View and track your loan applications</p>
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

        <!-- Loans List -->
        <?php if($loans_result && $loans_result->num_rows > 0): ?>
            <?php while($loan = $loans_result->fetch_assoc()): 
                // Calculate monthly payment for display only
                $monthly_payment = 0;
                if($loan['months'] > 0) {
                    $interest = $loan['amount'] * ($loan['interest_percentage'] / 100);
                    $total_payable = $loan['amount'] + $interest;
                    $monthly_payment = $total_payable / $loan['months'];
                }
                
                // Status display
                $status_text = 'Pending';
                $status_class = 'status-pending';
                $status_icon = 'fa-clock';
                switch($loan['status']) {
                    case 0:
                        $status_text = 'Pending Review';
                        $status_class = 'status-pending';
                        $status_icon = 'fa-clock';
                        break;
                    case 1:
                        $status_text = 'Approved';
                        $status_class = 'status-approved';
                        $status_icon = 'fa-check';
                        break;
                    case 2:
                        $status_text = 'Active';
                        $status_class = 'status-released';
                        $status_icon = 'fa-check-circle';
                        break;
                    case 3:
                        $status_text = 'Completed';
                        $status_class = 'status-completed';
                        $status_icon = 'fa-flag-checkered';
                        break;
                    case 4:
                        $status_text = 'Denied';
                        $status_class = 'status-denied';
                        $status_icon = 'fa-times';
                        break;
                }
            ?>
            
            <div class="loan-card">
                <div class="loan-header">
                    <div class="loan-ref">
                        <i class="fas fa-hashtag"></i> <?php echo $loan['ref_no']; ?>
                    </div>
                    <div class="status-badge <?php echo $status_class; ?>">
                        <i class="fas <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                    </div>
                </div>
                
                <div class="loan-details">
                    <div class="detail-item">
                        <div class="detail-label">Loan Type</div>
                        <div class="detail-value"><?php echo $loan['type_name'] ?? 'Standard'; ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Loan Amount</div>
                        <div class="detail-value">K <?php echo number_format($loan['amount'], 2); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Payment Plan</div>
                        <div class="detail-value"><?php echo $loan['months']; ?> months</div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Interest Rate</div>
                        <div class="detail-value"><?php echo $loan['interest_percentage']; ?>%</div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Monthly Payment</div>
                        <div class="detail-value">K <?php echo number_format($monthly_payment, 2); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Application Date</div>
                        <div class="detail-value"><?php echo date('M d, Y', strtotime($loan['date_created'])); ?></div>
                    </div>
                </div>
                
                <?php if(!empty($loan['purpose'])): ?>
                <div class="loan-purpose">
                    <strong><i class="fas fa-info-circle"></i> Purpose:</strong> 
                    <?php echo htmlspecialchars($loan['purpose']); ?>
                </div>
                <?php endif; ?>
                
                <?php if($loan['status'] == 2): ?>
                <div class="info-note">
                    <i class="fas fa-info-circle"></i>
                    <strong>Payment Information:</strong> Please visit our office or contact us for payment details and schedule.
                </div>
                <?php endif; ?>
                
                <?php if($loan['status'] == 4 && !empty($loan['remarks'])): ?>
                <div class="info-note" style="background: #f8d7da; color: #721c24;">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Reason:</strong> <?php echo htmlspecialchars($loan['remarks']); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php endwhile; ?>
            
        <?php else: ?>
            
            <!-- Empty State -->
            <div class="loan-card">
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="empty-text">
                        You haven't applied for any loans yet
                    </div>
                    <a href="customer_apply_loan.php" class="btn btn-primary-custom btn-custom">
                        <i class="fas fa-plus-circle"></i> Apply for Your First Loan
                    </a>
                </div>
            </div>
            
        <?php endif; ?>

    </div>

    <!-- Bootstrap JS -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
