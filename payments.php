<?php
include 'db_connect.php';
require_once 'includes/helpers.php';

// Get all active loans (status = 1 Approved OR status = 2 Released) with borrower info
$loans_query = $conn->query("
    SELECT
        l.id as loan_id,
        l.ref_no,
        l.amount as principal,
        l.interest_rate,
        l.total_payable,
        l.total_interest,
        l.outstanding_balance,
        l.monthly_installment,
        l.date_released,
        l.date_created,
        l.duration_months,
        l.status,
        CONCAT(b.lastname, ', ', b.firstname) as borrower_name,
        b.contact_no,
        b.email,
        b.id as borrower_id,
        COALESCE((SELECT SUM(amount) FROM payments WHERE loan_id = l.id), 0) as total_paid,
        (SELECT COUNT(*) FROM payments WHERE loan_id = l.id) as payment_count
    FROM loan_list l
    INNER JOIN borrowers b ON l.borrower_id = b.id
    WHERE l.status IN (1, 2)
    ORDER BY COALESCE(l.date_released, l.date_created) ASC
");

// Get completed loans for history
$completed_query = $conn->query("
    SELECT
        l.id as loan_id,
        l.ref_no,
        l.amount as principal,
        l.total_payable,
        l.date_released,
        l.status,
        CONCAT(b.lastname, ', ', b.firstname) as borrower_name,
        COALESCE((SELECT SUM(amount) FROM payments WHERE loan_id = l.id), 0) as total_paid,
        (SELECT MAX(date_created) FROM payments WHERE loan_id = l.id) as last_payment_date
    FROM loan_list l
    INNER JOIN borrowers b ON l.borrower_id = b.id
    WHERE l.status = 3
    ORDER BY l.date_created DESC
    LIMIT 20
");

$today = date('Y-m-d');
$penalty_rate = 5; // 5% overdue penalty
?>

<style>
    .borrower-table {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .borrower-table th {
        background: #f8fafc;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: #64748b;
        padding: 15px;
        border-bottom: 2px solid #e2e8f0;
    }
    .borrower-table td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
    .borrower-table tr:hover {
        background: #f8fafc;
    }
    .borrower-name {
        font-weight: 600;
        color: #1e293b;
    }
    .borrower-contact {
        font-size: 0.85rem;
        color: #64748b;
    }
    .loan-ref {
        background: #e0e7ff;
        color: #4338ca;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .amount-cell {
        font-weight: 600;
    }
    .amount-paid {
        color: #16a34a;
    }
    .amount-due {
        color: #dc2626;
    }
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-current {
        background: #dcfce7;
        color: #166534;
    }
    .status-overdue {
        background: #fee2e2;
        color: #dc2626;
    }
    .status-due-soon {
        background: #fef3c7;
        color: #b45309;
    }
    .btn-paid {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        border: none;
        color: white;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .btn-paid:hover {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        color: white;
        transform: translateY(-1px);
    }
    .btn-overdue {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border: none;
        color: white;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .btn-overdue:hover {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
        color: white;
        transform: translateY(-1px);
    }
    .summary-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }
    .summary-box {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .summary-box h3 {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
    }
    .summary-box p {
        color: #64748b;
        margin: 5px 0 0 0;
        font-size: 0.9rem;
    }
    .summary-box.active { border-left: 4px solid #2563eb; }
    .summary-box.paid { border-left: 4px solid #22c55e; }
    .summary-box.overdue { border-left: 4px solid #ef4444; }
    .summary-box.collected { border-left: 4px solid #8b5cf6; }

    .due-date-info {
        font-size: 0.8rem;
        color: #64748b;
    }
    .due-date-info.overdue {
        color: #dc2626;
        font-weight: 600;
    }

    .tab-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    .tab-btn {
        padding: 10px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        background: white;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .tab-btn:hover {
        border-color: #2563eb;
        color: #2563eb;
    }
    .tab-btn.active {
        background: #2563eb;
        border-color: #2563eb;
        color: white;
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }

    @media (max-width: 768px) {
        .summary-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .borrower-table {
            font-size: 0.85rem;
        }
    }
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fa fa-money-bill-wave"></i> Payment Management</h4>
            <p class="text-muted mb-0">Record loan payments and manage overdue accounts</p>
        </div>
    </div>

    <?php
    // Calculate summaries
    $active_count = 0;
    $overdue_count = 0;
    $total_outstanding = 0;
    $total_collected = 0;

    $loans_data = [];
    while($loan = $loans_query->fetch_assoc()) {
        $active_count++;

        // Calculate outstanding
        $outstanding = $loan['total_payable'] - $loan['total_paid'];
        $total_outstanding += $outstanding;
        $total_collected += $loan['total_paid'];

        // Check if loan duration has passed (overdue check)
        $is_overdue = false;
        $days_since_release = 0;
        $start_date = $loan['date_released'] ?: $loan['date_created'];
        if($start_date) {
            $release_date = new DateTime($start_date);
            $now = new DateTime();
            $days_since_release = $release_date->diff($now)->days;
            $loan_duration_days = ($loan['duration_months'] ?: 1) * 30;

            // Loan is overdue if duration has passed and not fully paid
            if($days_since_release > $loan_duration_days && $outstanding > 0) {
                $is_overdue = true;
                $overdue_count++;
            }
        }

        $loan['outstanding'] = $outstanding;
        $loan['is_overdue'] = $is_overdue;
        $loan['days_since_release'] = $days_since_release;
        $loans_data[] = $loan;
    }

    $completed_data = [];
    while($completed = $completed_query->fetch_assoc()) {
        $completed_data[] = $completed;
    }
    ?>

    <!-- Summary Cards -->
    <div class="summary-row">
        <div class="summary-box active">
            <h3><?php echo $active_count; ?></h3>
            <p>Active Loans</p>
        </div>
        <div class="summary-box overdue">
            <h3><?php echo $overdue_count; ?></h3>
            <p>Overdue Loans</p>
        </div>
        <div class="summary-box collected">
            <h3><?php echo formatCurrency($total_collected); ?></h3>
            <p>Total Collected</p>
        </div>
        <div class="summary-box paid">
            <h3><?php echo formatCurrency($total_outstanding); ?></h3>
            <p>Outstanding Balance</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tab-buttons">
        <button class="tab-btn active" onclick="showTab('active')">
            <i class="fa fa-users"></i> Active Borrowers (<?php echo $active_count; ?>)
        </button>
        <button class="tab-btn" onclick="showTab('completed')">
            <i class="fa fa-check-circle"></i> Completed Loans (<?php echo count($completed_data); ?>)
        </button>
        <button class="tab-btn" onclick="showTab('history')">
            <i class="fa fa-history"></i> Payment History
        </button>
    </div>

    <!-- Active Borrowers Tab -->
    <div id="tab-active" class="tab-content active">
        <div class="borrower-table">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Borrower</th>
                        <th>Loan Ref</th>
                        <th>Loan Amount</th>
                        <th>Total Payable</th>
                        <th>Paid</th>
                        <th>Outstanding</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($loans_data) == 0): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No active loans</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach($loans_data as $loan):
                        $penalty_amount = $loan['is_overdue'] ? ($loan['outstanding'] * $penalty_rate / 100) : 0;
                        $start_date = $loan['date_released'] ?: $loan['date_created'];
                        $due_date = $start_date ? date('M d, Y', strtotime($start_date . ' + ' . ($loan['duration_months'] ?: 1) . ' months')) : 'N/A';
                    ?>
                    <tr>
                        <td>
                            <div class="borrower-name"><?php echo htmlspecialchars($loan['borrower_name']); ?></div>
                            <div class="borrower-contact">
                                <i class="fa fa-phone"></i> <?php echo htmlspecialchars($loan['contact_no']); ?>
                            </div>
                        </td>
                        <td><span class="loan-ref"><?php echo $loan['ref_no']; ?></span></td>
                        <td class="amount-cell"><?php echo formatCurrency($loan['principal']); ?></td>
                        <td class="amount-cell"><?php echo formatCurrency($loan['total_payable']); ?></td>
                        <td class="amount-cell amount-paid"><?php echo formatCurrency($loan['total_paid']); ?></td>
                        <td class="amount-cell amount-due"><?php echo formatCurrency($loan['outstanding']); ?></td>
                        <td>
                            <?php echo $loan['duration_months'] ?: 1; ?> month(s)
                            <div class="due-date-info <?php echo $loan['is_overdue'] ? 'overdue' : ''; ?>">
                                Due: <?php echo $due_date; ?>
                                <?php if($loan['is_overdue']): ?>
                                    <br><i class="fa fa-exclamation-triangle"></i> OVERDUE
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if($loan['is_overdue']): ?>
                                <span class="status-badge status-overdue">Overdue</span>
                            <?php else: ?>
                                <span class="status-badge status-current">Current</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-paid" onclick="markAsPaid(<?php echo $loan['loan_id']; ?>, <?php echo $loan['outstanding']; ?>, '<?php echo htmlspecialchars($loan['borrower_name']); ?>')">
                                <i class="fa fa-check"></i> Paid
                            </button>
                            <?php if($loan['is_overdue']): ?>
                            <button class="btn-overdue mt-1" onclick="markAsOverdue(<?php echo $loan['loan_id']; ?>, <?php echo $loan['outstanding']; ?>, <?php echo $penalty_amount; ?>, '<?php echo htmlspecialchars($loan['borrower_name']); ?>')">
                                <i class="fa fa-exclamation-triangle"></i> +5% Penalty
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Completed Loans Tab -->
    <div id="tab-completed" class="tab-content">
        <div class="borrower-table">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Borrower</th>
                        <th>Loan Ref</th>
                        <th>Loan Amount</th>
                        <th>Total Paid</th>
                        <th>Last Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($completed_data) == 0): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No completed loans yet</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach($completed_data as $loan): ?>
                    <tr>
                        <td>
                            <div class="borrower-name"><?php echo htmlspecialchars($loan['borrower_name']); ?></div>
                        </td>
                        <td><span class="loan-ref"><?php echo $loan['ref_no']; ?></span></td>
                        <td class="amount-cell"><?php echo formatCurrency($loan['principal']); ?></td>
                        <td class="amount-cell amount-paid"><?php echo formatCurrency($loan['total_paid']); ?></td>
                        <td><?php echo $loan['last_payment_date'] ? date('M d, Y', strtotime($loan['last_payment_date'])) : 'N/A'; ?></td>
                        <td><span class="status-badge" style="background: #dcfce7; color: #166534;">Completed</span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment History Tab -->
    <div id="tab-history" class="tab-content">
        <div class="borrower-table">
            <table class="table mb-0" id="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Borrower</th>
                        <th>Loan Ref</th>
                        <th>Amount Paid</th>
                        <th>Penalty</th>
                        <th>Total</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $history = $conn->query("
                        SELECT p.*, l.ref_no, CONCAT(b.firstname, ' ', b.lastname) as borrower_name
                        FROM payments p
                        INNER JOIN loan_list l ON p.loan_id = l.id
                        INNER JOIN borrowers b ON l.borrower_id = b.id
                        ORDER BY p.date_created DESC
                        LIMIT 100
                    ");

                    if($history->num_rows == 0):
                    ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No payment history yet</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php while($h = $history->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, Y H:i', strtotime($h['date_created'])); ?></td>
                        <td class="borrower-name"><?php echo htmlspecialchars($h['borrower_name']); ?></td>
                        <td><span class="loan-ref"><?php echo $h['ref_no']; ?></span></td>
                        <td class="amount-cell"><?php echo formatCurrency($h['amount']); ?></td>
                        <td class="amount-cell" style="color: #f59e0b;"><?php echo formatCurrency($h['penalty_amount']); ?></td>
                        <td class="amount-cell amount-paid"><?php echo formatCurrency($h['amount'] + $h['penalty_amount']); ?></td>
                        <td><?php echo htmlspecialchars($h['payee'] ?: 'Admin'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Mark as Paid Modal -->
<div class="modal fade" id="paidModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fa fa-check-circle"></i> Mark Loan as Paid</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fa fa-info-circle"></i>
                    You are about to record full payment for this loan.
                </div>
                <p><strong>Borrower:</strong> <span id="paid_borrower_name"></span></p>
                <p><strong>Outstanding Amount:</strong> <span id="paid_amount" class="text-success font-weight-bold"></span></p>

                <form id="paid-form">
                    <input type="hidden" name="loan_id" id="paid_loan_id">
                    <input type="hidden" name="amount" id="paid_amount_input">
                    <input type="hidden" name="penalty_amount" value="0">

                    <div class="form-group">
                        <label>Payment Method</label>
                        <select class="form-control" name="payment_method">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <input type="text" class="form-control" name="payee" placeholder="Any notes about this payment">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-lg" onclick="confirmPaid()">
                    <i class="fa fa-check"></i> Confirm Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Mark as Overdue Modal -->
<div class="modal fade" id="overdueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fa fa-exclamation-triangle"></i> Apply Overdue Penalty</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    This will add a <strong>5% penalty</strong> to the outstanding balance.
                </div>
                <p><strong>Borrower:</strong> <span id="overdue_borrower_name"></span></p>
                <p><strong>Outstanding Amount:</strong> <span id="overdue_amount"></span></p>
                <p><strong>5% Penalty:</strong> <span id="overdue_penalty" class="text-warning font-weight-bold"></span></p>
                <hr>
                <p><strong>New Total Due:</strong> <span id="overdue_total" class="text-danger font-weight-bold" style="font-size: 1.2rem;"></span></p>

                <form id="overdue-form">
                    <input type="hidden" name="loan_id" id="overdue_loan_id">
                    <input type="hidden" name="penalty_amount" id="overdue_penalty_input">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning btn-lg" onclick="confirmOverdue()">
                    <i class="fa fa-exclamation-triangle"></i> Apply Penalty
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Tab switching
function showTab(tab) {
    $('.tab-content').removeClass('active');
    $('.tab-btn').removeClass('active');
    $('#tab-' + tab).addClass('active');
    $('[onclick="showTab(\'' + tab + '\')"]').addClass('active');
}

// Mark as Paid
function markAsPaid(loanId, amount, borrowerName) {
    $('#paid_loan_id').val(loanId);
    $('#paid_amount_input').val(amount);
    $('#paid_borrower_name').text(borrowerName);
    $('#paid_amount').text('K ' + amount.toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#paidModal').modal('show');
}

// Confirm Paid
function confirmPaid() {
    var formData = $('#paid-form').serialize();

    start_load();
    $.ajax({
        url: 'ajax.php?action=mark_loan_paid',
        method: 'POST',
        data: formData,
        success: function(resp) {
            if(resp == 1) {
                alert_toast('Loan marked as fully paid!', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                alert_toast('Error: ' + resp, 'error');
                end_load();
            }
        },
        error: function() {
            alert_toast('Server error', 'error');
            end_load();
        }
    });
}

// Mark as Overdue (apply penalty)
function markAsOverdue(loanId, outstanding, penalty, borrowerName) {
    $('#overdue_loan_id').val(loanId);
    $('#overdue_penalty_input').val(penalty);
    $('#overdue_borrower_name').text(borrowerName);
    $('#overdue_amount').text('K ' + outstanding.toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#overdue_penalty').text('K ' + penalty.toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#overdue_total').text('K ' + (outstanding + penalty).toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#overdueModal').modal('show');
}

// Confirm Overdue (apply penalty to outstanding)
function confirmOverdue() {
    var formData = $('#overdue-form').serialize();

    start_load();
    $.ajax({
        url: 'ajax.php?action=apply_overdue_penalty',
        method: 'POST',
        data: formData,
        success: function(resp) {
            if(resp == 1) {
                alert_toast('5% penalty applied to outstanding balance', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                alert_toast('Error: ' + resp, 'error');
                end_load();
            }
        },
        error: function() {
            alert_toast('Server error', 'error');
            end_load();
        }
    });
}
</script>
