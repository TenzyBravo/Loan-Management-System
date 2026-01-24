<?php
session_start();
if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

include('db_connect.php');

$customer_id = $_SESSION['customer_id'];

// Get customer info
$stmt = $conn->prepare("SELECT * FROM borrowers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get loan count and stats (include status 1 and 2 as active)
$stmt = $conn->prepare("SELECT COUNT(*) as total,
                             SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending,
                             SUM(CASE WHEN status IN (1, 2) THEN 1 ELSE 0 END) as active,
                             SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as completed
                             FROM loan_list WHERE borrower_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$loan_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get active loan payment details (for showing payment progress) - include status 1 and 2
$stmt = $conn->prepare("
    SELECT l.*,
           COALESCE((SELECT SUM(amount) FROM payments WHERE loan_id = l.id), 0) as total_paid,
           (SELECT COUNT(*) FROM payments WHERE loan_id = l.id) as payments_made
    FROM loan_list l
    WHERE l.borrower_id = ? AND l.status IN (1, 2)
    ORDER BY l.date_created DESC
    LIMIT 1
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$active_loan = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent loans
$stmt = $conn->prepare("SELECT l.*, lt.type_name, lp.months, lp.interest_percentage
                              FROM loan_list l
                              LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
                              LEFT JOIN loan_plan lp ON l.plan_id = lp.id
                              WHERE l.borrower_id = ?
                              ORDER BY l.date_created DESC LIMIT 5");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$recent_loans = $stmt->get_result();
$stmt->close();

// Get notifications
$stmt = $conn->prepare("SELECT * FROM customer_notifications
                               WHERE borrower_id = ?
                               ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();

// Get document status
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
    <title>Customer Dashboard | Brian Investments</title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }
        body {
            background: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 250px;
            min-height: 100vh;
            padding: 20px 0;
            flex-shrink: 0;
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
            transition: all 0.3s;
            display: block;
            text-decoration: none;
        }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .main-content {
            flex: 1;
            padding: 20px;
            min-width: 0;
        }
        .top-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .welcome-text h4 {
            margin: 0;
            color: #333;
            font-size: 1.2rem;
        }
        .welcome-text p {
            margin: 0;
            color: #666;
            font-size: 0.85rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            height: 100%;
        }
        .stat-card .icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .stat-card h3 {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 10px 0 5px 0;
        }
        .stat-card p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        .card-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            font-weight: 600;
            color: #333;
            padding: 15px;
        }
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        .logout-btn:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
        }
        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
        }
        .notification-item:hover {
            background: #f8f9fa;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #555;
            font-size: 0.85rem;
        }
        .table td {
            vertical-align: middle;
            font-size: 0.9rem;
        }
        .progress-card {
            border-left: 4px solid #28a745;
        }
        .progress-card .card-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .info-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .info-box small {
            color: #666;
            display: block;
            margin-bottom: 5px;
        }
        .info-box h5 {
            margin: 0;
            font-weight: 600;
        }

        /* Mobile Styles */
        @media (max-width: 991px) {
            .wrapper {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                min-height: auto;
                padding: 10px 0;
            }
            .sidebar .brand {
                padding: 10px;
                font-size: 1.2rem;
            }
            .sidebar nav {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px;
            }
            .sidebar .nav-link {
                padding: 8px 15px;
                margin: 3px;
                font-size: 0.85rem;
            }
            .main-content {
                padding: 15px;
            }
        }
        @media (max-width: 576px) {
            .top-bar {
                flex-direction: column;
                text-align: center;
            }
            .stat-card {
                padding: 15px;
            }
            .stat-card h3 {
                font-size: 1.5rem;
            }
            .stat-card .icon {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="brand">
                <i class="fas fa-hand-holding-usd"></i> Brian Investments
            </div>
            <nav>
                <a class="nav-link active" href="customer_dashboard.php">
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
                <a class="nav-link" href="customer_profile.php">
                    <i class="fas fa-user"></i> My Profile
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="welcome-text">
                    <h4>Welcome back, <?php echo htmlspecialchars($customer['firstname']); ?>!</h4>
                    <p>Here's what's happening with your loans today</p>
                </div>
                <a href="customer_logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center" style="border-left: 4px solid #667eea;">
                        <i class="fas fa-file-alt icon" style="color: #667eea;"></i>
                        <h3><?php echo $loan_stats['total'] ?: 0; ?></h3>
                        <p>Total Applications</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center" style="border-left: 4px solid #f39c12;">
                        <i class="fas fa-clock icon" style="color: #f39c12;"></i>
                        <h3><?php echo $loan_stats['pending'] ?: 0; ?></h3>
                        <p>Pending Review</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center" style="border-left: 4px solid #28a745;">
                        <i class="fas fa-check-circle icon" style="color: #28a745;"></i>
                        <h3><?php echo $loan_stats['active'] ?: 0; ?></h3>
                        <p>Active Loans</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center" style="border-left: 4px solid #17a2b8;">
                        <i class="fas fa-flag-checkered icon" style="color: #17a2b8;"></i>
                        <h3><?php echo $loan_stats['completed'] ?: 0; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
            </div>

            <?php if($active_loan): ?>
            <!-- Active Loan Payment Progress -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card progress-card">
                        <div class="card-header">
                            <i class="fas fa-money-bill-wave"></i> Active Loan Payment Progress
                        </div>
                        <div class="card-body">
                            <?php
                            $total_payable = $active_loan['total_payable'] > 0 ? $active_loan['total_payable'] : $active_loan['amount'];
                            $total_paid = $active_loan['total_paid'];
                            $outstanding = max(0, $total_payable - $total_paid);
                            $progress = $total_payable > 0 ? ($total_paid / $total_payable) * 100 : 0;
                            $monthly = $active_loan['monthly_installment'] > 0 ? $active_loan['monthly_installment'] : $total_payable;
                            $duration = $active_loan['duration_months'] > 0 ? $active_loan['duration_months'] : 1;
                            ?>
                            <div class="row mb-3">
                                <div class="col-6 col-md-3 mb-2">
                                    <div class="info-box">
                                        <small>Loan Amount</small>
                                        <h5 class="text-primary">K <?php echo number_format($active_loan['amount'], 2); ?></h5>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-2">
                                    <div class="info-box">
                                        <small>Total Payable</small>
                                        <h5>K <?php echo number_format($total_payable, 2); ?></h5>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-2">
                                    <div class="info-box">
                                        <small>Total Paid</small>
                                        <h5 class="text-success">K <?php echo number_format($total_paid, 2); ?></h5>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-2">
                                    <div class="info-box">
                                        <small>Outstanding</small>
                                        <h5 class="text-danger">K <?php echo number_format($outstanding, 2); ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Payment Progress</span>
                                    <span class="font-weight-bold"><?php echo number_format($progress, 1); ?>%</span>
                                </div>
                                <div class="progress" style="height: 20px; border-radius: 10px;">
                                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                         role="progressbar"
                                         style="width: <?php echo min($progress, 100); ?>%">
                                        <?php echo number_format($progress, 1); ?>%
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <div class="info-box">
                                        <small>Reference</small>
                                        <h5><?php echo $active_loan['ref_no']; ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="info-box">
                                        <small>Monthly Installment</small>
                                        <h5>K <?php echo number_format($monthly, 2); ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="info-box">
                                        <small>Payments Made</small>
                                        <h5><?php echo $active_loan['payments_made']; ?> of <?php echo $duration; ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row mt-3">
                <!-- Recent Loans -->
                <div class="col-lg-8 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="fas fa-list"></i> Recent Loan Applications
                        </div>
                        <div class="card-body">
                            <?php if($recent_loans->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Ref No.</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($loan = $recent_loans->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?php echo $loan['ref_no']; ?></strong></td>
                                                <td><?php echo $loan['type_name']; ?></td>
                                                <td>K <?php echo number_format($loan['amount'], 2); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    $status_text = '';
                                                    switch($loan['status']) {
                                                        case 0: $status_class = 'warning'; $status_text = 'Pending'; break;
                                                        case 1: $status_class = 'info'; $status_text = 'Approved'; break;
                                                        case 2: $status_class = 'success'; $status_text = 'Active'; break;
                                                        case 3: $status_class = 'primary'; $status_text = 'Completed'; break;
                                                        case 4: $status_class = 'danger'; $status_text = 'Denied'; break;
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?php echo $status_class; ?> badge-status">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($loan['date_created'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox" style="font-size: 50px; color: #ddd;"></i>
                                    <p class="mt-3 text-muted">No loan applications yet</p>
                                    <a href="customer_apply_loan.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Apply for a Loan
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Notifications -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <i class="fas fa-bell"></i> Notifications
                        </div>
                        <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                            <?php if($notifications->num_rows > 0): ?>
                                <?php while($notif = $notifications->fetch_assoc()): ?>
                                <div class="notification-item">
                                    <div class="d-flex justify-content-between">
                                        <strong style="color: #333; font-size: 0.9rem;"><?php echo htmlspecialchars($notif['title']); ?></strong>
                                        <small class="text-muted">
                                            <?php echo date('M d', strtotime($notif['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-0 mt-1" style="font-size: 0.85rem; color: #666;">
                                        <?php echo htmlspecialchars($notif['message']); ?>
                                    </p>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-bell-slash" style="font-size: 30px; color: #ddd;"></i>
                                    <p class="mt-2 text-muted mb-0" style="font-size: 0.9rem;">No notifications</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Document Status -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-file-upload"></i> Document Status
                        </div>
                        <div class="card-body">
                            <?php
                            $doc_types = array('id' => 'ID Document', 'employment_proof' => 'Employment Proof', 'payslip' => 'Pay Slip');
                            $has_docs = false;
                            while($doc = $documents->fetch_assoc()):
                                $has_docs = true;
                            ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span style="font-size: 0.9rem;"><?php echo $doc_types[$doc['document_type']] ?? $doc['document_type']; ?></span>
                                <?php
                                $doc_status_class = $doc['status'] == 0 ? 'warning' : ($doc['status'] == 1 ? 'success' : 'danger');
                                $doc_status_text = $doc['status'] == 0 ? 'Pending' : ($doc['status'] == 1 ? 'Verified' : 'Rejected');
                                ?>
                                <span class="badge badge-<?php echo $doc_status_class; ?>">
                                    <?php echo $doc_status_text; ?>
                                </span>
                            </div>
                            <?php endwhile; ?>
                            <?php if(!$has_docs): ?>
                            <div class="text-center py-2">
                                <p class="text-muted mb-2" style="font-size: 0.9rem;">No documents uploaded</p>
                                <a href="customer_my_documents.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-upload"></i> Upload Documents
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
