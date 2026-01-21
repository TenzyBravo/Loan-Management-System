<?php 
include 'db_connect.php';

// Get statistics
$today = date('Y-m-d');
$thisMonth = date('Y-m');
$thisYear = date('Y');

// Today's payments
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE DATE(date_created) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$paymentsToday = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// This month's payments
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE DATE_FORMAT(date_created, '%Y-%m') = ?");
$stmt->bind_param("s", $thisMonth);
$stmt->execute();
$paymentsMonth = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total borrowers
$totalBorrowers = $conn->query("SELECT COUNT(*) as count FROM borrowers")->fetch_assoc()['count'];

// Active loans
$activeLoans = $conn->query("SELECT COUNT(*) as count FROM loan_list WHERE status = 2")->fetch_assoc()['count'];

// Pending loans
$pendingLoans = $conn->query("SELECT COUNT(*) as count FROM loan_list WHERE status = 0")->fetch_assoc()['count'];

// Completed loans
$completedLoans = $conn->query("SELECT COUNT(*) as count FROM loan_list WHERE status = 3")->fetch_assoc()['count'];

// Total receivable (principal + interest - payments)
$totalLoansValue = $conn->query("SELECT COALESCE(SUM(l.amount + (l.amount * (p.interest_percentage/100))), 0) as total 
                                  FROM loan_list l 
                                  INNER JOIN loan_plan p ON p.id = l.plan_id 
                                  WHERE l.status = 2")->fetch_assoc()['total'];

$totalPayments = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments")->fetch_assoc()['total'];
$totalReceivable = $totalLoansValue - $totalPayments;

// Overdue loans
$overdueLoans = $conn->query("SELECT COUNT(DISTINCT l.id) as count 
                               FROM loan_list l 
                               INNER JOIN loan_schedules ls ON l.id = ls.loan_id 
                               LEFT JOIN payments p ON l.id = p.loan_id 
                               WHERE l.status = 2 
                               AND ls.date_due < CURDATE()
                               GROUP BY l.id
                               HAVING COUNT(ls.id) > COALESCE(COUNT(p.id), 0)")->num_rows;

// Monthly payment data for chart (last 12 months)
$monthlyPayments = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M Y', strtotime("-$i months"));
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE DATE_FORMAT(date_created, '%Y-%m') = ?");
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $monthlyPayments[] = [
        'month' => $monthLabel,
        'amount' => (float)$row['total']
    ];
}

// Loan distribution by type
$stmt = $conn->prepare("SELECT lt.type_name, COUNT(l.id) as count, SUM(l.amount) as total_amount
                              FROM loan_list l
                              INNER JOIN loan_types lt ON l.loan_type_id = lt.id
                              GROUP BY lt.id, lt.type_name");
$stmt->execute();
$loansByType = $stmt->get_result();
$loanTypeData = [];
while ($row = $loansByType->fetch_assoc()) {
    $loanTypeData[] = $row;
}
$stmt->close();

// Loan status distribution
$status_approved = 1;
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM loan_list WHERE status = ?");
$stmt->bind_param("i", $status_approved);
$stmt->execute();
$approvedLoans = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$status_denied = 4;
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM loan_list WHERE status = ?");
$stmt->bind_param("i", $status_denied);
$stmt->execute();
$deniedLoans = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// Overdue loans analysis by age
$overdueAnalysis = [];
$overdueQuery = $conn->query("
    SELECT
        CASE
            WHEN DATEDIFF(CURDATE(), ls.date_due) BETWEEN 1 AND 30 THEN '0-30 days'
            WHEN DATEDIFF(CURDATE(), ls.date_due) BETWEEN 31 AND 60 THEN '31-60 days'
            WHEN DATEDIFF(CURDATE(), ls.date_due) BETWEEN 61 AND 90 THEN '61-90 days'
            WHEN DATEDIFF(CURDATE(), ls.date_due) > 90 THEN '90+ days'
        END as age_range,
        COUNT(DISTINCT l.id) as count,
        COALESCE(SUM(l.monthly_installment), 0) as total_amount
    FROM loan_list l
    INNER JOIN loan_schedules ls ON l.id = ls.loan_id
    LEFT JOIN payments p ON p.loan_id = l.id AND DATE(p.date_created) = ls.date_due
    WHERE l.status = 2
    AND ls.date_due < CURDATE()
    AND p.id IS NULL
    GROUP BY age_range
    ORDER BY
        CASE age_range
            WHEN '0-30 days' THEN 1
            WHEN '31-60 days' THEN 2
            WHEN '61-90 days' THEN 3
            WHEN '90+ days' THEN 4
        END
");

if (!$overdueQuery) {
    error_log("Overdue query failed: " . $conn->error);
    $overdueAnalysis = [];
} else {
    while ($row = $overdueQuery->fetch_assoc()) {
        $overdueAnalysis[] = $row;
    }
}

// Collection rate calculation
$collectionRateQuery = $conn->query("
    SELECT
        COUNT(DISTINCT ls.id) as total_schedules,
        COUNT(DISTINCT CASE WHEN p.id IS NOT NULL THEN ls.id END) as paid_on_time
    FROM loan_schedules ls
    LEFT JOIN payments p ON ls.loan_id = p.loan_id
        AND DATE(p.date_created) = ls.date_due
    WHERE ls.date_due <= CURDATE()
    AND ls.loan_id IN (SELECT id FROM loan_list WHERE status IN (2, 3))
");

if (!$collectionRateQuery) {
    error_log("Collection rate query failed: " . $conn->error);
    $collectionRate = 0;
} else {
    $collectionStats = $collectionRateQuery->fetch_assoc();
    $collectionRate = $collectionStats['total_schedules'] > 0
        ? round(($collectionStats['paid_on_time'] / $collectionStats['total_schedules']) * 100, 1)
        : 0;
}

// Recent activity
$stmt = $conn->prepare("SELECT l.*, CONCAT(b.firstname, ' ', b.lastname) as borrower_name, lt.type_name
                              FROM loan_list l
                              INNER JOIN borrowers b ON l.borrower_id = b.id
                              LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
                              ORDER BY l.date_created DESC LIMIT 5");
$stmt->execute();
$recentLoansResult = $stmt->get_result();
$recentLoans = [];
while ($row = $recentLoansResult->fetch_assoc()) {
    $recentLoans[] = $row;
}
$stmt->close();

$stmt = $conn->prepare("SELECT p.*, CONCAT(b.firstname, ' ', b.lastname) as borrower_name, l.ref_no
                                 FROM payments p
                                 INNER JOIN loan_list l ON p.loan_id = l.id
                                 INNER JOIN borrowers b ON l.borrower_id = b.id
                                 ORDER BY p.date_created DESC LIMIT 5");
$stmt->execute();
$recentPaymentsResult = $stmt->get_result();
$recentPayments = [];
while ($row = $recentPaymentsResult->fetch_assoc()) {
    $recentPayments[] = $row;
}
$stmt->close();

// Upcoming payments (next 7 days)
$stmt = $conn->prepare("SELECT ls.*, l.ref_no, l.amount as loan_amount,
                                   CONCAT(b.firstname, ' ', b.lastname) as borrower_name,
                                   lp.months, lp.interest_percentage,
                                   (l.amount + (l.amount * lp.interest_percentage / 100)) / lp.months as monthly_payment
                                   FROM loan_schedules ls
                                   INNER JOIN loan_list l ON ls.loan_id = l.id
                                   INNER JOIN borrowers b ON l.borrower_id = b.id
                                   INNER JOIN loan_plan lp ON l.plan_id = lp.id
                                   WHERE l.status = 2
                                   AND ls.date_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                                   ORDER BY ls.date_due ASC
                                   LIMIT 10");
$stmt->execute();
$upcomingPaymentsResult = $stmt->get_result();
$upcomingPayments = [];
while ($row = $upcomingPaymentsResult->fetch_assoc()) {
    $upcomingPayments[] = $row;
}
$stmt->close();
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* Modern Dashboard Styling */
    .dashboard-card {
        border-radius: 12px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        overflow: hidden;
    }
    .dashboard-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .card-icon {
        font-size: 2.5rem;
        opacity: 0.9;
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--gray-800, #1f2937);
    }
    .stat-label {
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.9;
        color: var(--gray-500, #6b7280);
    }
    .chart-container {
        position: relative;
        height: 300px;
    }
    .activity-item {
        padding: 12px 15px;
        border-left: 3px solid transparent;
        transition: all 0.2s;
    }
    .activity-item:hover {
        background: var(--gray-50, #f9fafb);
        border-left-color: var(--primary-blue, #2563eb);
    }
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    .badge-overdue {
        background: var(--danger, #ef4444);
        color: white;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    .welcome-banner {
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        border-radius: 15px;
        color: white;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--gray-700, #374151);
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }
    .section-title i {
        margin-right: 10px;
        color: var(--primary-blue, #2563eb);
    }

    /* Overdue Analysis Bars */
    .overdue-bar {
        height: 8px;
        border-radius: 4px;
        background: var(--gray-200, #e5e7eb);
        position: relative;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }
    .overdue-bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 1s ease;
    }
    .overdue-metric {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
    }

    /* Collection Rate Circle */
    .progress-ring {
        position: relative;
        width: 200px;
        height: 200px;
        margin: 0 auto;
    }
    .progress-ring svg {
        transform: rotate(-90deg);
    }
    .progress-value {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }

    /* Status Legend */
    .status-legend {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .status-item {
        display: flex;
        align-items: center;
        padding: 0.5rem;
        border-radius: 0.375rem;
        background: var(--gray-50, #f9fafb);
    }
    .status-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 0.5rem;
    }
    .status-label {
        font-weight: 500;
        margin-right: 0.25rem;
    }
    .status-count {
        color: var(--gray-600, #4b5563);
    }
    .status-amount {
        color: var(--gray-500, #6b7280);
        font-size: 0.875rem;
        margin-left: 0.25rem;
    }

    /* Mobile Dashboard Optimizations */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 0.5rem !important;
        }

        .welcome-banner h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .welcome-banner p {
            font-size: 0.85rem;
        }

        .card-icon {
            font-size: 2rem;
        }

        .stat-value {
            font-size: 1.5rem;
        }

        .chart-container {
            height: 220px !important;
        }

        .overdue-bar {
            height: 6px;
        }

        .activity-item {
            font-size: 0.875rem;
            padding: 10px;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            font-size: 0.875rem;
        }

        /* Stack overdue analysis vertically */
        .col-md-4, .col-md-8 {
            -ms-flex: 0 0 100%;
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    @media (max-width: 480px) {
        .welcome-banner {
            padding: 8px;
        }

        .stat-value {
            font-size: 1.25rem;
        }

        .stat-label {
            font-size: 0.7rem;
        }

        .chart-container {
            height: 180px !important;
        }

        .section-title {
            font-size: 0.95rem;
        }
    }
</style>

<div class="container-fluid p-4">
    
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['login_name'] ?? 'Admin'); ?>!</h2>
                <p class="mb-0 opacity-75">Here's what's happening with your loans today.</p>
            </div>
            <div class="col-md-4 text-right">
                <p class="mb-0"><i class="fa fa-calendar-alt mr-2"></i><?php echo date('l, F j, Y'); ?></p>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row 1 -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Today's Payments</div>
                            <div class="stat-value">K <?php echo number_format($paymentsToday, 2); ?></div>
                        </div>
                        <div class="card-icon"><i class="fa fa-dollar-sign"></i></div>
                    </div>
                </div>
                <div class="card-footer bg-primary border-0 py-2">
                    <a href="admin.php?page=payments" class="text-white text-decoration-none small">
                        View all payments <i class="fa fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">This Month</div>
                            <div class="stat-value">K <?php echo number_format($paymentsMonth, 2); ?></div>
                        </div>
                        <div class="card-icon"><i class="fa fa-chart-line"></i></div>
                    </div>
                </div>
                <div class="card-footer bg-success border-0 py-2">
                    <span class="text-white small">
                        <i class="fa fa-calendar mr-1"></i> <?php echo date('F Y'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Total Receivable</div>
                            <div class="stat-value">K <?php echo number_format($totalReceivable, 2); ?></div>
                        </div>
                        <div class="card-icon"><i class="fa fa-hand-holding-usd"></i></div>
                    </div>
                </div>
                <div class="card-footer bg-info border-0 py-2">
                    <span class="text-white small">
                        From <?php echo $activeLoans; ?> active loans
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Pending Approvals</div>
                            <div class="stat-value"><?php echo $pendingLoans; ?></div>
                        </div>
                        <div class="card-icon"><i class="fa fa-clock"></i></div>
                    </div>
                </div>
                <div class="card-footer bg-warning border-0 py-2">
                    <a href="admin.php?page=loans" class="text-dark text-decoration-none small">
                        Review pending <i class="fa fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row 2 -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label text-muted">Total Borrowers</div>
                            <div class="stat-value text-primary"><?php echo $totalBorrowers; ?></div>
                        </div>
                        <div class="card-icon text-primary"><i class="fa fa-users"></i></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label text-muted">Active Loans</div>
                            <div class="stat-value text-success"><?php echo $activeLoans; ?></div>
                        </div>
                        <div class="card-icon text-success"><i class="fa fa-file-invoice-dollar"></i></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label text-muted">Completed Loans</div>
                            <div class="stat-value text-secondary"><?php echo $completedLoans; ?></div>
                        </div>
                        <div class="card-icon text-secondary"><i class="fa fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card dashboard-card h-100 <?php echo $overdueLoans > 0 ? 'border-danger' : ''; ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label text-muted">Overdue Loans</div>
                            <div class="stat-value text-danger"><?php echo $overdueLoans; ?></div>
                        </div>
                        <div class="card-icon text-danger"><i class="fa fa-exclamation-triangle"></i></div>
                    </div>
                </div>
                <?php if ($overdueLoans > 0): ?>
                <div class="card-footer bg-danger text-white py-2">
                    <small><i class="fa fa-bell mr-1"></i> Requires attention</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Overdue Loans & Collection Performance Row -->
    <div class="row mb-4">
        <!-- Overdue Loans Analysis -->
        <div class="col-xl-8 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa fa-exclamation-triangle text-warning"></i>
                        Overdue Loans Analysis
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($overdueAnalysis) > 0): ?>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <?php
                                $totalOverdue = array_sum(array_column($overdueAnalysis, 'count'));
                                $totalOverdueAmount = array_sum(array_column($overdueAnalysis, 'total_amount'));
                                ?>
                                <div class="text-center">
                                    <div class="stat-value text-danger">
                                        K <?php echo number_format($totalOverdueAmount, 2); ?>
                                    </div>
                                    <div class="stat-label">Total Overdue Amount</div>
                                    <div class="mt-2 text-danger">
                                        <i class="fa fa-arrow-up"></i>
                                        <?php echo $totalOverdue; ?> loans overdue
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="chart-container" style="height: 250px;">
                                    <canvas id="overdueChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row mt-3">
                            <?php foreach ($overdueAnalysis as $range): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="overdue-metric">
                                        <span class="font-weight-bold"><?php echo $range['age_range']; ?></span>
                                        <span class="text-muted"><?php echo $range['count']; ?> loans</span>
                                    </div>
                                    <div class="overdue-bar">
                                        <div class="overdue-bar-fill" style="
                                            width: <?php echo ($range['count'] / $totalOverdue * 100); ?>%;
                                            background: <?php
                                                echo $range['age_range'] == '0-30 days' ? '#f59e0b' :
                                                    ($range['age_range'] == '31-60 days' ? '#fb923c' :
                                                    ($range['age_range'] == '61-90 days' ? '#ef4444' : '#991b1b'));
                                            ?>;
                                        "></div>
                                    </div>
                                    <div class="text-right text-muted small">
                                        K <?php echo number_format($range['total_amount'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-success">No Overdue Loans!</h5>
                            <p class="text-muted">All loans are current with their payment schedules.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Collection Performance -->
        <div class="col-xl-4 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Collection Performance</h5>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <div class="progress-ring mb-3">
                        <svg viewBox="0 0 200 200" width="200" height="200">
                            <circle cx="100" cy="100" r="80" fill="none" stroke="#e5e7eb" stroke-width="12"/>
                            <circle cx="100" cy="100" r="80" fill="none"
                                    stroke="<?php echo $collectionRate >= 90 ? '#10b981' : ($collectionRate >= 70 ? '#f59e0b' : '#ef4444'); ?>"
                                    stroke-width="12"
                                    stroke-dasharray="502.4"
                                    stroke-dashoffset="<?php echo 502.4 * (1 - $collectionRate/100); ?>"
                                    stroke-linecap="round"
                                    style="transition: stroke-dashoffset 1s ease;"/>
                        </svg>
                        <div class="progress-value">
                            <div class="stat-value" style="font-size: 2.5rem;"><?php echo $collectionRate; ?>%</div>
                            <div class="stat-label">On-Time Rate</div>
                        </div>
                    </div>
                    <p class="text-center text-muted mb-0">
                        <?php echo $collectionStats['paid_on_time']; ?> of <?php echo $collectionStats['total_schedules']; ?> payments received on time
                    </p>
                    <div class="mt-3 w-100">
                        <?php if ($collectionRate >= 90): ?>
                            <div class="alert alert-success mb-0">
                                <i class="fa fa-check-circle"></i> Excellent collection performance!
                            </div>
                        <?php elseif ($collectionRate >= 70): ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fa fa-exclamation-triangle"></i> Good, but room for improvement
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger mb-0">
                                <i class="fa fa-times-circle"></i> Action required to improve collection
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Payment Trend Chart -->
        <div class="col-xl-8 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="section-title mb-0"><i class="fa fa-chart-area"></i> Payment Trends (Last 12 Months)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="paymentTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Loan Status Pie Chart -->
        <div class="col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="section-title mb-0"><i class="fa fa-chart-pie"></i> Loan Status Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="loanStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity & Upcoming Row -->
    <div class="row">
        <!-- Recent Activity -->
        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="section-title mb-0"><i class="fa fa-history"></i> Recent Activity</h6>
                    <a href="admin.php?page=loans" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentLoans as $loan): ?>
                        <div class="activity-item d-flex align-items-center">
                            <div class="activity-icon bg-<?php 
                                echo $loan['status'] == 0 ? 'warning' : 
                                    ($loan['status'] == 2 ? 'success' : 
                                    ($loan['status'] == 4 ? 'danger' : 'info')); 
                            ?> text-white mr-3">
                                <i class="fa fa-<?php 
                                    echo $loan['status'] == 0 ? 'clock' : 
                                        ($loan['status'] == 2 ? 'check' : 
                                        ($loan['status'] == 4 ? 'times' : 'file')); 
                                ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="font-weight-bold"><?php echo htmlspecialchars($loan['borrower_name']); ?></div>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($loan['type_name'] ?? 'Loan'); ?> - 
                                    K <?php echo number_format($loan['amount'], 2); ?>
                                    <span class="badge badge-<?php 
                                        echo $loan['status'] == 0 ? 'warning' : 
                                            ($loan['status'] == 2 ? 'success' : 
                                            ($loan['status'] == 4 ? 'danger' : 'info')); 
                                    ?> ml-2">
                                        <?php 
                                        $statuses = ['Pending', 'Approved', 'Released', 'Completed', 'Denied'];
                                        echo $statuses[$loan['status']] ?? 'Unknown';
                                        ?>
                                    </span>
                                </small>
                            </div>
                            <small class="text-muted"><?php echo date('M j', strtotime($loan['date_created'])); ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Payments -->
        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="section-title mb-0"><i class="fa fa-calendar-check"></i> Upcoming Payments (Next 7 Days)</h6>
                    <span class="badge badge-info"><?php echo count($upcomingPayments); ?> due</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Borrower</th>
                                    <th>Ref #</th>
                                    <th>Due Date</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($upcomingPayments) > 0): ?>
                                    <?php foreach ($upcomingPayments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['borrower_name']); ?></td>
                                        <td><code><?php echo htmlspecialchars($payment['ref_no']); ?></code></td>
                                        <td>
                                            <?php 
                                            $dueDate = strtotime($payment['date_due']);
                                            $isToday = date('Y-m-d') == $payment['date_due'];
                                            $isTomorrow = date('Y-m-d', strtotime('+1 day')) == $payment['date_due'];
                                            ?>
                                            <span class="<?php echo $isToday ? 'text-danger font-weight-bold' : ($isTomorrow ? 'text-warning' : ''); ?>">
                                                <?php echo $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : date('M j', $dueDate)); ?>
                                            </span>
                                        </td>
                                        <td class="text-right font-weight-bold">
                                            K <?php echo number_format($payment['monthly_payment'], 2); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fa fa-check-circle fa-2x mb-2 text-success"></i><br>
                                            No upcoming payments in the next 7 days
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="section-title mb-0"><i class="fa fa-money-bill-wave"></i> Recent Payments</h6>
                    <a href="admin.php?page=payments" class="btn btn-sm btn-outline-success">View All Payments</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Borrower</th>
                                    <th>Loan Ref</th>
                                    <th>Payee</th>
                                    <th class="text-right">Amount</th>
                                    <th class="text-right">Penalty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPayments as $payment): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($payment['date_created'])); ?></td>
                                    <td><?php echo htmlspecialchars($payment['borrower_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($payment['ref_no']); ?></code></td>
                                    <td><?php echo htmlspecialchars($payment['payee']); ?></td>
                                    <td class="text-right text-success font-weight-bold">
                                        K <?php echo number_format($payment['amount'], 2); ?>
                                    </td>
                                    <td class="text-right <?php echo $payment['penalty_amount'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                        K <?php echo number_format($payment['penalty_amount'], 2); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
// Chart.js defaults for modern appearance
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 13;
Chart.defaults.color = '#6b7280'; // var(--gray-500)

// Overdue Loans Chart
const overdueData = <?php echo json_encode($overdueAnalysis); ?>;
if (overdueData && overdueData.length > 0) {
    const overdueCtx = document.getElementById('overdueChart').getContext('2d');
    new Chart(overdueCtx, {
        type: 'bar',
        data: {
            labels: overdueData.map(item => item.age_range),
            datasets: [{
                label: 'Number of Loans',
                data: overdueData.map(item => item.count),
                backgroundColor: [
                    'rgba(251, 191, 36, 0.8)',  // yellow (0-30)
                    'rgba(251, 146, 60, 0.8)',  // orange (31-60)
                    'rgba(239, 68, 68, 0.8)',   // red (61-90)
                    'rgba(153, 27, 27, 0.8)'    // dark red (90+)
                ],
                borderWidth: 0,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' loans overdue';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: '#f3f4f6' }
                },
                x: { grid: { display: false } }
            }
        }
    });
}

// Payment Trend Chart
const paymentCtx = document.getElementById('paymentTrendChart').getContext('2d');
const paymentData = <?php echo json_encode($monthlyPayments); ?>;

new Chart(paymentCtx, {
    type: 'line',
    data: {
        labels: paymentData.map(item => item.month),
        datasets: [{
            label: 'Payments (K)',
            data: paymentData.map(item => item.amount),
            borderColor: '#2563eb', // var(--primary-blue)
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#2563eb',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: { size: 14, weight: '600' },
                bodyFont: { size: 13 },
                padding: 12,
                callbacks: {
                    label: function(context) {
                        return 'K ' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'K ' + value.toLocaleString();
                    }
                },
                grid: { color: '#f3f4f6' }
            },
            x: { grid: { display: false } }
        }
    }
});

// Loan Status Doughnut Chart
const statusCtx = document.getElementById('loanStatusChart').getContext('2d');
const statusColors = {
    'Pending': '#f59e0b',    // var(--warning)
    'Approved': '#0ea5e9',   // var(--info)
    'Released': '#2563eb',   // var(--primary-blue)
    'Completed': '#10b981',  // var(--success)
    'Denied': '#ef4444'      // var(--danger)
};

const loanStatusData = [
    { status: 'Pending', count: <?php echo $pendingLoans; ?> },
    { status: 'Approved', count: <?php echo $approvedLoans; ?> },
    { status: 'Released', count: <?php echo $activeLoans; ?> },
    { status: 'Completed', count: <?php echo $completedLoans; ?> },
    { status: 'Denied', count: <?php echo $deniedLoans; ?> }
];

new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: loanStatusData.map(item => item.status),
        datasets: [{
            data: loanStatusData.map(item => item.count),
            backgroundColor: loanStatusData.map(item => statusColors[item.status]),
            borderWidth: 0,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    font: { size: 12, weight: '500' }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: { size: 14, weight: '600' },
                bodyFont: { size: 13 },
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                        return `${context.label}: ${context.parsed} (${percentage}%)`;
                    }
                }
            }
        }
    }
});
</script>
