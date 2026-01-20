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

// Get loan count and stats
$stmt = $conn->prepare("SELECT COUNT(*) as total,
                             SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending,
                             SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as active,
                             SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as completed
                             FROM loan_list WHERE borrower_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$loan_stats = $stmt->get_result()->fetch_assoc();
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
    <title>Customer Dashboard | Loan Management System</title>
    
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
            left: 0;
            top: 0;
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
        }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-text h4 {
            margin: 0;
            color: #333;
        }
        .welcome-text p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .stat-card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-card p {
            color: #666;
            margin: 0;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            font-weight: 600;
            color: #333;
        }
        .badge-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .logout-btn:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
        }
        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
        }
        .notification-item:hover {
            background: #f8f9fa;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <i class="fas fa-hand-holding-usd"></i><br>
            Loan Portal
        </div>
        <nav class="nav flex-column mt-4">
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
                <h4>Welcome back, <?php echo $customer['firstname']; ?>! 👋</h4>
                <p>Here's what's happening with your loans today</p>
            </div>
            <div>
                <a href="customer_logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card text-center" style="border-left: 4px solid #667eea;">
                    <i class="fas fa-file-alt icon" style="color: #667eea;"></i>
                    <h3><?php echo $loan_stats['total'] ?: 0; ?></h3>
                    <p>Total Applications</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center" style="border-left: 4px solid #f39c12;">
                    <i class="fas fa-clock icon" style="color: #f39c12;"></i>
                    <h3><?php echo $loan_stats['pending'] ?: 0; ?></h3>
                    <p>Pending Review</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center" style="border-left: 4px solid #28a745;">
                    <i class="fas fa-check-circle icon" style="color: #28a745;"></i>
                    <h3><?php echo $loan_stats['active'] ?: 0; ?></h3>
                    <p>Active Loans</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center" style="border-left: 4px solid #17a2b8;">
                    <i class="fas fa-flag-checkered icon" style="color: #17a2b8;"></i>
                    <h3><?php echo $loan_stats['completed'] ?: 0; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Recent Loans -->
            <div class="col-md-8">
                <div class="card">
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
                                            <th>Loan Type</th>
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
                                                    case 1: $status_class = 'info'; $status_text = 'Confirmed'; break;
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
                            <div class="text-center py-5">
                                <i class="fas fa-inbox" style="font-size: 60px; color: #ddd;"></i>
                                <p class="mt-3 text-muted">No loan applications yet</p>
                                <a href="customer_apply_loan.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Apply for a Loan
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bell"></i> Notifications
                    </div>
                    <div class="card-body p-0">
                        <?php if($notifications->num_rows > 0): ?>
                            <?php while($notif = $notifications->fetch_assoc()): ?>
                            <div class="notification-item">
                                <div class="d-flex justify-content-between">
                                    <strong style="color: #333;"><?php echo $notif['title']; ?></strong>
                                    <small class="text-muted">
                                        <?php echo date('M d', strtotime($notif['created_at'])); ?>
                                    </small>
                                </div>
                                <p class="mb-0 mt-1" style="font-size: 0.9rem; color: #666;">
                                    <?php echo $notif['message']; ?>
                                </p>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-bell-slash" style="font-size: 40px; color: #ddd;"></i>
                                <p class="mt-2 text-muted">No notifications</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Document Status -->
                <div class="card mt-3">
                    <div class="card-header">
                        <i class="fas fa-file-upload"></i> Document Status
                    </div>
                    <div class="card-body">
                        <?php 
                        $doc_types = array('id' => 'ID Document', 'employment_proof' => 'Employment Proof', 'payslip' => 'Pay Slip');
                        while($doc = $documents->fetch_assoc()):
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong><?php echo $doc_types[$doc['document_type']]; ?></strong>
                            </div>
                            <div>
                                <?php
                                $doc_status_class = $doc['status'] == 0 ? 'warning' : ($doc['status'] == 1 ? 'success' : 'danger');
                                $doc_status_text = $doc['status'] == 0 ? 'Pending' : ($doc['status'] == 1 ? 'Verified' : 'Rejected');
                                ?>
                                <span class="badge badge-<?php echo $doc_status_class; ?>">
                                    <?php echo $doc_status_text; ?>
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
