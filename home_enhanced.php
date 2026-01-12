<?php 
include 'db_connect.php';

// Get statistics
$today = date('Y-m-d');
$thisMonth = date('Y-m');
$thisYear = date('Y');

// Today's payments
$paymentsToday = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE DATE(date_created) = '$today'")->fetch_assoc()['total'];

// This month's payments
$paymentsMonth = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE DATE_FORMAT(date_created, '%Y-%m') = '$thisMonth'")->fetch_assoc()['total'];

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
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE DATE_FORMAT(date_created, '%Y-%m') = '$month'");
    $monthlyPayments[] = [
        'month' => $monthLabel,
        'amount' => (float)$result->fetch_assoc()['total']
    ];
}

// Loan distribution by type
$loansByType = $conn->query("SELECT lt.type_name, COUNT(l.id) as count, SUM(l.amount) as total_amount 
                              FROM loan_list l 
                              INNER JOIN loan_types lt ON l.loan_type_id = lt.id 
                              GROUP BY lt.id, lt.type_name");
$loanTypeData = [];
while ($row = $loansByType->fetch_assoc()) {
    $loanTypeData[] = $row;
}

// Loan status distribution
$loanStatusData = [
    ['status' => 'Pending', 'count' => $pendingLoans, 'color' => '#ffc107'],
    ['status' => 'Approved', 'count' => $conn->query("SELECT COUNT(*) as c FROM loan_list WHERE status = 1")->fetch_assoc()['c'], 'color' => '#17a2b8'],
    ['status' => 'Released', 'count' => $activeLoans, 'color' => '#28a745'],
    ['status' => 'Completed', 'count' => $completedLoans, 'color' => '#6c757d'],
    ['status' => 'Denied', 'count' => $conn->query("SELECT COUNT(*) as c FROM loan_list WHERE status = 4")->fetch_assoc()['c'], 'color' => '#dc3545']
];

// Recent activity
$recentLoans = $conn->query("SELECT l.*, CONCAT(b.firstname, ' ', b.lastname) as borrower_name, lt.type_name 
                              FROM loan_list l 
                              INNER JOIN borrowers b ON l.borrower_id = b.id 
                              LEFT JOIN loan_types lt ON l.loan_type_id = lt.id 
                              ORDER BY l.date_created DESC LIMIT 5");

$recentPayments = $conn->query("SELECT p.*, CONCAT(b.firstname, ' ', b.lastname) as borrower_name, l.ref_no 
                                 FROM payments p 
                                 INNER JOIN loan_list l ON p.loan_id = l.id 
                                 INNER JOIN borrowers b ON l.borrower_id = b.id 
                                 ORDER BY p.date_created DESC LIMIT 5");

// Upcoming payments (next 7 days)
$upcomingPayments = $conn->query("SELECT ls.*, l.ref_no, l.amount as loan_amount, 
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
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .dashboard-card {
        border-radius: 10px;
        transition: transform 0.2s, box-shadow 0.2s;
        border: none;
        overflow: hidden;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }
    .card-icon {
        font-size: 2.5rem;
        opacity: 0.8;
    }
    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
    }
    .stat-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.9;
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
        background: #f8f9fa;
        border-left-color: #007bff;
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
        background: #dc3545;
        color: white;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    .welcome-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        color: white;
        padding: 25px;
        margin-bottom: 25px;
    }
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }
    .section-title i {
        margin-right: 10px;
        color: #007bff;
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
                            <div class="stat-value">$<?php echo number_format($paymentsToday, 2); ?></div>
                        </div>
                        <div class="card-icon"><i class="fa fa-dollar-sign"></i></div>
                    </div>
                </div>
                <div class="card-footer bg-primary border-0 py-2">
                    <a href="index.php?page=payments" class="text-white text-decoration-none small">
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
                            <div class="stat-value">$<?php echo number_format($paymentsMonth, 2); ?></div>
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
                            <div class="stat-value">$<?php echo number_format($totalReceivable, 2); ?></div>
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
                    <a href="index.php?page=loans" class="text-dark text-decoration-none small">
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
                    <a href="index.php?page=loans" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php while ($loan = $recentLoans->fetch_assoc()): ?>
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
                                    $<?php echo number_format($loan['amount'], 2); ?>
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
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Payments -->
        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="section-title mb-0"><i class="fa fa-calendar-check"></i> Upcoming Payments (Next 7 Days)</h6>
                    <span class="badge badge-info"><?php echo $upcomingPayments->num_rows; ?> due</span>
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
                                <?php if ($upcomingPayments->num_rows > 0): ?>
                                    <?php while ($payment = $upcomingPayments->fetch_assoc()): ?>
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
                                            $<?php echo number_format($payment['monthly_payment'], 2); ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
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
                    <a href="index.php?page=payments" class="btn btn-sm btn-outline-success">View All Payments</a>
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
                                <?php while ($payment = $recentPayments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($payment['date_created'])); ?></td>
                                    <td><?php echo htmlspecialchars($payment['borrower_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($payment['ref_no']); ?></code></td>
                                    <td><?php echo htmlspecialchars($payment['payee']); ?></td>
                                    <td class="text-right text-success font-weight-bold">
                                        $<?php echo number_format($payment['amount'], 2); ?>
                                    </td>
                                    <td class="text-right <?php echo $payment['penalty_amount'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                        $<?php echo number_format($payment['penalty_amount'], 2); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
// Payment Trend Chart
const paymentCtx = document.getElementById('paymentTrendChart').getContext('2d');
const paymentData = <?php echo json_encode($monthlyPayments); ?>;

new Chart(paymentCtx, {
    type: 'line',
    data: {
        labels: paymentData.map(item => item.month),
        datasets: [{
            label: 'Payments ($)',
            data: paymentData.map(item => item.amount),
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#007bff',
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
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: { size: 14 },
                bodyFont: { size: 13 },
                padding: 12,
                callbacks: {
                    label: function(context) {
                        return '$' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Loan Status Pie Chart
const statusCtx = document.getElementById('loanStatusChart').getContext('2d');
const statusData = <?php echo json_encode($loanStatusData); ?>;

new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusData.map(item => item.status),
        datasets: [{
            data: statusData.map(item => item.count),
            backgroundColor: statusData.map(item => item.color),
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
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return `${context.label}: ${context.parsed} (${percentage}%)`;
                    }
                }
            }
        }
    }
});
</script>
