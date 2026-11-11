<?php
session_start();
if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

include('db_connect.php');
require_once __DIR__ . '/includes/finance.php';
require_once __DIR__ . '/includes/portal_layout.php';

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

$page_head = <<<'HTML'
<style>
    .loan-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 8px 12px; margin-bottom: 14px; }
    .loan-ref { font-size: 1.05rem; font-weight: 700; margin: 0; overflow-wrap: anywhere; }
    .loan-sub { color: var(--muted); font-size: .85rem; }
    .loan-figs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    @media (min-width: 768px) { .loan-figs { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; } }
    .loan-fig { background: var(--bg); border-radius: var(--radius-sm); padding: 10px 12px; min-width: 0; }
    .loan-fig-label { font-size: .74rem; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; }
    .loan-fig-value { font-weight: 600; font-size: 1rem; margin-top: 2px; overflow-wrap: anywhere; }
    .loan-fig.is-key .loan-fig-value { font-size: 1.15rem; font-weight: 700; color: var(--brand); }
    .loan-note { margin: 14px 0 0; font-size: .9rem; }
</style>
HTML;

portal_header('My Loans', 'loans', ['head' => $page_head]);
?>
    <div class="page-head">
        <div>
            <h1>My Loan Applications</h1>
            <p>View and track your loan applications</p>
        </div>
        <a href="customer_apply_loan.php" class="btn btn-gold btn-block-sm">
            <i class="fas fa-plus-circle"></i> Apply for Loan
        </a>
    </div>

    <!-- Success Message -->
    <?php if(isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_msg']; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
    </div>
    <?php unset($_SESSION['success_msg']); endif; ?>

    <!-- Error Message -->
    <?php if(isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_msg']; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
    </div>
    <?php unset($_SESSION['error_msg']); endif; ?>

    <!-- Loans List -->
    <?php if($loans_result && $loans_result->num_rows > 0): ?>
        <?php while($loan = $loans_result->fetch_assoc()):
            // Use the loan's own term, rate and stored totals (display only)
            $loan_unit = normalizeTermUnit($loan['term_unit'] ?? 'month');
            $loan_count = max(1, (int) ($loan['duration_months'] ?? ($loan['months'] ?? 1)));
            $loan_rate = (float) ($loan['interest_rate'] ?? 0);
            $monthly_payment = (float) ($loan['monthly_installment'] ?? 0);
            if($monthly_payment <= 0 && $loan['months'] > 0) {
                $interest = $loan['amount'] * ($loan['interest_percentage'] / 100);
                $total_payable = $loan['amount'] + $interest;
                $monthly_payment = $total_payable / $loan['months'];
            }
            $rate_pending = ($loan_rate <= 0 && $loan['status'] == 0);

            // Stored totals for display (shown only when the loan has them)
            $stored_total = (float) ($loan['total_payable'] ?? 0);
            $stored_outstanding = (float) ($loan['outstanding_balance'] ?? 0);

            // Status display
            $status_text = 'Pending';
            $status_class = 'p-badge-pending';
            $status_icon = 'fa-clock';
            switch($loan['status']) {
                case 0:
                    $status_text = 'Pending Review';
                    $status_class = 'p-badge-pending';
                    $status_icon = 'fa-clock';
                    break;
                case 1:
                    $status_text = 'Approved';
                    $status_class = 'p-badge-approved';
                    $status_icon = 'fa-check';
                    break;
                case 2:
                    $status_text = 'Active';
                    $status_class = 'p-badge-active';
                    $status_icon = 'fa-check-circle';
                    break;
                case 3:
                    $status_text = 'Completed';
                    $status_class = 'p-badge-done';
                    $status_icon = 'fa-flag-checkered';
                    break;
                case 4:
                    $status_text = 'Denied';
                    $status_class = 'p-badge-denied';
                    $status_icon = 'fa-times';
                    break;
            }
        ?>

        <article class="p-card">
            <div class="loan-head">
                <div>
                    <h2 class="loan-ref">#<?php echo htmlspecialchars((string) $loan['ref_no']); ?></h2>
                    <div class="loan-sub">
                        <?php echo htmlspecialchars((string) ($loan['type_name'] ?? 'Standard')); ?>
                        &middot; Applied <?php echo date('M d, Y', strtotime($loan['date_created'])); ?>
                    </div>
                </div>
                <span class="p-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
            </div>

            <div class="loan-figs">
                <div class="loan-fig is-key">
                    <div class="loan-fig-label">Loan Amount</div>
                    <div class="loan-fig-value tabular">K <?php echo number_format($loan['amount'], 2); ?></div>
                </div>

                <div class="loan-fig is-key">
                    <div class="loan-fig-label">Total Payable</div>
                    <div class="loan-fig-value tabular"><?php echo $rate_pending ? 'Pending review' : ($stored_total > 0 ? 'K ' . number_format($stored_total, 2) : '&mdash;'); ?></div>
                </div>

                <div class="loan-fig">
                    <div class="loan-fig-label"><?php echo $loan_unit === 'week' ? 'Amount Due' : 'Monthly Payment'; ?></div>
                    <div class="loan-fig-value tabular"><?php echo $rate_pending ? 'Pending review' : 'K ' . number_format($monthly_payment, 2); ?></div>
                </div>

                <div class="loan-fig">
                    <div class="loan-fig-label">Repayment Term</div>
                    <div class="loan-fig-value"><?php echo formatLoanTerm($loan_count, $loan_unit); ?></div>
                </div>

                <div class="loan-fig">
                    <div class="loan-fig-label">Interest Rate</div>
                    <div class="loan-fig-value tabular"><?php echo $rate_pending ? 'Pending review' : (($loan_rate > 0 ? $loan_rate : $loan['interest_percentage']) . '% total'); ?></div>
                </div>

                <?php if($loan['status'] == 2 && $stored_outstanding > 0): ?>
                <div class="loan-fig is-key">
                    <div class="loan-fig-label">Outstanding</div>
                    <div class="loan-fig-value tabular">K <?php echo number_format($stored_outstanding, 2); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <?php if(!empty($loan['purpose'])): ?>
            <p class="loan-note text-muted">
                <strong class="text-body">Purpose:</strong>
                <?php echo htmlspecialchars($loan['purpose']); ?>
            </p>
            <?php endif; ?>

            <?php if($loan['status'] == 0): ?>
            <div class="alert alert-warning loan-note mb-0">
                <i class="fas fa-clock"></i>
                Applications are reviewed within 24-48 hours.
            </div>
            <?php endif; ?>

            <?php if($loan['status'] == 2): ?>
            <div class="alert alert-info loan-note mb-0">
                <i class="fas fa-info-circle"></i>
                <strong>Payment Information:</strong> Please visit our office or contact us for payment details and schedule.
            </div>
            <?php endif; ?>

            <?php if($loan['status'] == 4 && !empty($loan['remarks'])): ?>
            <div class="alert alert-danger loan-note mb-0">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Reason:</strong> <?php echo htmlspecialchars($loan['remarks']); ?>
            </div>
            <?php endif; ?>
        </article>

        <?php endwhile; ?>

    <?php else: ?>

        <!-- Empty State -->
        <div class="p-card">
            <div class="p-empty">
                <i class="fas fa-file-invoice"></i>
                <h3>You haven't applied for any loans yet</h3>
                <a href="customer_apply_loan.php" class="btn btn-gold mt-2">
                    <i class="fas fa-plus-circle"></i> Apply for Your First Loan
                </a>
            </div>
        </div>

    <?php endif; ?>
<?php
portal_footer();
