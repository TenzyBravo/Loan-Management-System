<?php
session_start();
if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

include('db_connect.php');
require_once __DIR__ . '/includes/finance.php';
include('includes/notifications.php');
include('includes/security.php');

// Update last activity on page load
Security::updateActivity();

$customer_id = $_SESSION['customer_id'];

// Get notification count
$notification_count = get_customer_notification_count($conn, $customer_id);
$customer_notifications = get_customer_notifications($conn, $customer_id, 5);

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

require_once __DIR__ . '/includes/portal_layout.php';

// Map loan status codes to portal badge classes and labels
$loan_status_badges = array(
    0 => array('p-badge-pending', 'Pending'),
    1 => array('p-badge-approved', 'Approved'),
    2 => array('p-badge-active', 'Active'),
    3 => array('p-badge-done', 'Completed'),
    4 => array('p-badge-denied', 'Denied'),
);

$page_head = <<<'HTML'
<style>
    .dash-bell { position: relative; display: inline-flex; align-items: center; justify-content: center;
        width: 44px; height: 44px; border-radius: 50%; background: var(--surface); border: 1px solid var(--line);
        color: var(--ink-2); cursor: pointer; }
    .dash-bell:hover { border-color: var(--brand); color: var(--brand); }
    .notif-badge { position: absolute; top: -3px; right: -3px; min-width: 20px; height: 20px; padding: 0 5px;
        border-radius: 999px; background: var(--bad); color: #fff; font-size: .68rem; font-weight: 600;
        display: flex; align-items: center; justify-content: center; border: 2px solid var(--surface); }
    .notification-dropdown-menu { width: 320px; max-width: calc(100vw - 32px); max-height: 360px; overflow-y: auto; padding: 0; }
    .notif-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; border-bottom: 1px solid var(--line); }
    .notif-header h6 { margin: 0; font-weight: 600; }
    .notif-item, .notification-item { display: flex; gap: 12px; padding: 12px 15px; border-bottom: 1px solid var(--line); color: inherit; }
    .notif-item:hover { background: var(--bg); text-decoration: none; color: inherit; }
    .notif-item.unread { background: var(--brand-50); }
    .notification-item:last-child { border-bottom: 0; }
    .notif-icon { width: 36px; height: 36px; border-radius: 50%; background: var(--brand-50); color: var(--brand);
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .notif-content { flex: 1; min-width: 0; }
    .notif-content strong { display: block; font-size: .85rem; }
    .notif-content p { margin: 0; font-size: .8rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .notif-time { font-size: .72rem; color: var(--muted); margin-top: 2px; }
    .notif-empty { padding: 28px; text-align: center; color: var(--muted); }
    .dash-hero-progress { height: 8px; border-radius: 999px; background: rgba(255,255,255,.25); overflow: hidden; }
    .dash-hero-progress span { display: block; height: 100%; background: var(--gold); border-radius: 999px; }
</style>
HTML;

portal_header('Dashboard', 'home', ['head' => $page_head]);
?>

<!-- Greeting + notifications -->
<div class="page-head">
    <div class="welcome-text">
        <h1>Welcome back, <?php echo htmlspecialchars($customer['firstname']); ?>!</h1>
        <p>Here's what's happening with your loans today</p>
        <?php if (!empty($customer['last_login'])): ?>
            <small class="text-muted">
                <i class="fas fa-clock"></i> Last login: <?php echo date('M j, Y g:i A', strtotime($customer['last_login'])); ?>
            </small>
        <?php endif; ?>
    </div>
    <div class="dropdown">
        <div class="dash-bell notification-bell-btn" data-toggle="dropdown" role="button" tabindex="0" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-bell"></i>
            <?php if ($notification_count > 0): ?>
            <span class="notif-badge"><?php echo $notification_count > 9 ? '9+' : $notification_count; ?></span>
            <?php endif; ?>
        </div>
        <div class="dropdown-menu dropdown-menu-right notification-dropdown-menu">
            <div class="notif-header">
                <h6><i class="fas fa-bell mr-2"></i>Notifications</h6>
                <?php if ($notification_count > 0): ?>
                <a href="customer_mark_notifications_read.php" style="font-size: 0.8rem;">Mark all read</a>
                <?php endif; ?>
            </div>

            <?php if (!empty($customer_notifications)): ?>
                <?php foreach ($customer_notifications as $notif): ?>
                <a href="customer_my_loans.php" class="notif-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                    <div class="notif-icon">
                        <i class="fas <?php echo get_notification_icon($notif['type']); ?>"></i>
                    </div>
                    <div class="notif-content">
                        <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                        <div class="notif-time"><?php echo time_ago($notif['created_at']); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="notif-empty">
                <i class="fas fa-check-circle" style="font-size: 2rem; color: var(--brand-100);"></i>
                <p class="mt-2 mb-0">No notifications</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if($active_loan): ?>
<?php
$total_payable = $active_loan['total_payable'] > 0 ? $active_loan['total_payable'] : $active_loan['amount'];
$total_paid = $active_loan['total_paid'];
$outstanding = max(0, $total_payable - $total_paid);
$progress = $total_payable > 0 ? ($total_paid / $total_payable) * 100 : 0;
$monthly = $active_loan['monthly_installment'] > 0 ? $active_loan['monthly_installment'] : $total_payable;
$duration = $active_loan['duration_months'] > 0 ? $active_loan['duration_months'] : 1;
$active_unit = normalizeTermUnit($active_loan['term_unit'] ?? 'month');
$installments_total = $active_unit === 'week' ? 1 : $duration;
$installment_label = $active_unit === 'week' ? 'Amount Due (' . formatLoanTerm($duration, 'week') . ' loan)' : 'Monthly Installment';
$active_badge = $loan_status_badges[(int) $active_loan['status']] ?? array('p-badge-neutral', '');
?>
<!-- Active loan: most important information first -->
<section class="p-hero" aria-label="Active loan">
    <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap: 8px;">
        <div class="p-hero-sub">Outstanding balance</div>
        <span class="p-badge <?php echo $active_badge[0]; ?>"><?php echo $active_badge[1]; ?></span>
    </div>
    <div class="p-hero-amount tabular">K <?php echo number_format($outstanding, 2); ?></div>
    <div class="p-hero-sub mb-3">
        <?php echo htmlspecialchars($installment_label); ?>:
        <strong class="tabular">K <?php echo number_format($monthly, 2); ?></strong>
        &middot; Ref <?php echo htmlspecialchars($active_loan['ref_no']); ?>
    </div>
    <div class="d-flex justify-content-between p-hero-sub mb-1">
        <span>Payment Progress</span>
        <span class="tabular"><?php echo number_format($progress, 1); ?>%</span>
    </div>
    <div class="dash-hero-progress mb-2" role="progressbar" aria-valuenow="<?php echo number_format(min($progress, 100), 1, '.', ''); ?>" aria-valuemin="0" aria-valuemax="100">
        <span style="width: <?php echo min($progress, 100); ?>%"></span>
    </div>
    <div class="p-hero-sub mb-3">
        Payments Made: <span class="tabular"><?php echo $active_loan['payments_made']; ?> of <?php echo $installments_total; ?></span>
    </div>
    <a href="customer_my_loans.php" class="btn btn-light">
        <i class="fas fa-file-invoice-dollar mr-1"></i> View My Loans
    </a>
</section>
<?php endif; ?>

<!-- Quick actions -->
<nav class="p-actions" aria-label="Quick actions">
    <a class="p-action<?php echo $active_loan ? '' : ' is-primary'; ?>" href="customer_apply_loan.php">
        <i class="fas fa-plus-circle"></i> Apply for Loan
    </a>
    <a class="p-action<?php echo $active_loan ? ' is-primary' : ''; ?>" href="customer_my_loans.php">
        <i class="fas fa-file-invoice-dollar"></i> My Loans
    </a>
    <a class="p-action" href="customer_my_documents.php">
        <i class="fas fa-upload"></i> Upload Documents
    </a>
    <a class="p-action" href="customer_profile.php">
        <i class="fas fa-user"></i> My Profile
    </a>
</nav>

<!-- Statistics -->
<div class="p-stats">
    <div class="p-stat is-info">
        <div class="p-stat-icon"><i class="fas fa-file-alt"></i></div>
        <div class="p-stat-label">Total Applications</div>
        <div class="p-stat-value"><?php echo $loan_stats['total'] ?: 0; ?></div>
    </div>
    <div class="p-stat is-gold">
        <div class="p-stat-icon"><i class="fas fa-clock"></i></div>
        <div class="p-stat-label">Pending Review</div>
        <div class="p-stat-value"><?php echo $loan_stats['pending'] ?: 0; ?></div>
    </div>
    <div class="p-stat">
        <div class="p-stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="p-stat-label">Active Loans</div>
        <div class="p-stat-value"><?php echo $loan_stats['active'] ?: 0; ?></div>
    </div>
    <div class="p-stat is-info">
        <div class="p-stat-icon"><i class="fas fa-flag-checkered"></i></div>
        <div class="p-stat-label">Completed</div>
        <div class="p-stat-value"><?php echo $loan_stats['completed'] ?: 0; ?></div>
    </div>
</div>

<?php if($active_loan): ?>
<!-- Active loan breakdown -->
<section class="p-card">
    <h2 class="p-card-title"><i class="fas fa-money-bill-wave"></i> Active Loan Payment Progress</h2>
    <dl class="p-kv">
        <dt>Reference</dt>
        <dd><?php echo htmlspecialchars($active_loan['ref_no']); ?></dd>
        <dt>Loan Amount</dt>
        <dd class="tabular">K <?php echo number_format($active_loan['amount'], 2); ?></dd>
        <dt>Total Payable</dt>
        <dd class="tabular">K <?php echo number_format($total_payable, 2); ?></dd>
        <dt>Total Paid</dt>
        <dd class="tabular text-success">K <?php echo number_format($total_paid, 2); ?></dd>
        <dt>Outstanding</dt>
        <dd class="tabular text-danger">K <?php echo number_format($outstanding, 2); ?></dd>
        <dt><?php echo htmlspecialchars($installment_label); ?></dt>
        <dd class="tabular">K <?php echo number_format($monthly, 2); ?></dd>
        <dt>Payments Made</dt>
        <dd class="tabular"><?php echo $active_loan['payments_made']; ?> of <?php echo $installments_total; ?></dd>
    </dl>
</section>
<?php endif; ?>

<div class="row">
    <!-- Recent Loans -->
    <div class="col-lg-8">
        <section class="p-card">
            <h2 class="p-card-title"><i class="fas fa-list"></i> Recent Loan Applications</h2>
            <?php if($recent_loans->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-stack">
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
                            <?php $badge = $loan_status_badges[(int) $loan['status']] ?? array('p-badge-neutral', ''); ?>
                            <tr>
                                <td data-label="Ref No."><strong><?php echo htmlspecialchars($loan['ref_no']); ?></strong></td>
                                <td data-label="Type"><?php echo htmlspecialchars((string) $loan['type_name']); ?></td>
                                <td data-label="Amount" class="tabular">K <?php echo number_format($loan['amount'], 2); ?></td>
                                <td data-label="Status">
                                    <span class="p-badge <?php echo $badge[0]; ?>"><?php echo $badge[1]; ?></span>
                                </td>
                                <td data-label="Date"><?php echo date('M d, Y', strtotime($loan['date_created'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-empty">
                    <i class="fas fa-inbox"></i>
                    <p>No loan applications yet</p>
                    <a href="customer_apply_loan.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Apply for a Loan
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- Notifications -->
        <section class="p-card">
            <h2 class="p-card-title"><i class="fas fa-bell"></i> Notifications</h2>
            <div style="max-height: 280px; overflow-y: auto; margin: 0 -4px;">
                <?php if($notifications->num_rows > 0): ?>
                    <?php while($notif = $notifications->fetch_assoc()): ?>
                    <div class="notification-item d-block">
                        <div class="d-flex justify-content-between" style="gap: 8px;">
                            <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($notif['title']); ?></strong>
                            <small class="text-muted text-nowrap">
                                <?php echo date('M d', strtotime($notif['created_at'])); ?>
                            </small>
                        </div>
                        <p class="mb-0 mt-1 text-muted" style="font-size: 0.85rem;">
                            <?php echo htmlspecialchars($notif['message']); ?>
                        </p>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-empty py-3">
                        <i class="fas fa-bell-slash"></i>
                        <p class="mb-0">No notifications</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Document Status -->
        <section class="p-card">
            <h2 class="p-card-title"><i class="fas fa-file-upload"></i> Document Status</h2>
            <?php
            $doc_types = array('id' => 'ID Document', 'employment_proof' => 'Employment Proof', 'payslip' => 'Pay Slip');
            $has_docs = false;
            while($doc = $documents->fetch_assoc()):
                $has_docs = true;
            ?>
            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom" style="gap: 8px;">
                <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($doc_types[$doc['document_type']] ?? $doc['document_type']); ?></span>
                <?php
                $doc_status_class = $doc['status'] == 0 ? 'p-badge-pending' : ($doc['status'] == 1 ? 'p-badge-done' : 'p-badge-denied');
                $doc_status_text = $doc['status'] == 0 ? 'Pending' : ($doc['status'] == 1 ? 'Verified' : 'Rejected');
                ?>
                <span class="p-badge <?php echo $doc_status_class; ?>"><?php echo $doc_status_text; ?></span>
            </div>
            <?php endwhile; ?>
            <?php if(!$has_docs): ?>
            <div class="p-empty py-3">
                <i class="fas fa-folder-open"></i>
                <p class="mb-3">No documents uploaded</p>
                <a href="customer_my_documents.php" class="btn btn-outline-primary">
                    <i class="fas fa-upload"></i> Upload Documents
                </a>
            </div>
            <?php else: ?>
            <a href="customer_my_documents.php" class="btn btn-outline-primary btn-block mt-3">
                <i class="fas fa-folder-open"></i> My Documents
            </a>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php ob_start(); ?>
<script>
    // Session timeout warning
    (function() {
        var sessionTimeout = <?php echo Security::SESSION_TIMEOUT; ?>;
        var warningTime = <?php echo Security::SESSION_WARNING; ?>;
        var lastActivity = Date.now();
        var warningShown = false;

        // Track activity
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(function(event) {
            document.addEventListener(event, function() {
                var now = Date.now();
                if (now - lastActivity > 60000) {  // Update once per minute
                    lastActivity = now;
                    $.post('customer_session.php?action=extend');
                }
            }, { passive: true });
        });

        // Check session periodically
        setInterval(function() {
            $.get('customer_session.php?action=check', function(resp) {
                try {
                    var data = JSON.parse(resp);
                    if (!data.valid) {
                        alert('Your session has expired. Please login again.');
                        window.location.href = 'customer_login.php?expired=1';
                    } else if (data.warning && !warningShown) {
                        warningShown = true;
                        if (confirm('Your session will expire in ' + Math.floor(data.remaining/60) + ' minutes. Click OK to stay logged in.')) {
                            $.post('customer_session.php?action=extend', function() {
                                warningShown = false;
                            });
                        }
                    }
                } catch(e) {}
            });
        }, 60000);  // Check every minute
    })();
</script>
<?php portal_footer(['scripts' => ob_get_clean()]); ?>
