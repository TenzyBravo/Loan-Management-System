<?php
include 'db_connect.php';
require_once 'includes/helpers.php';

// Get all active loans (status = 2 = Released) with payment info
$loans_query = $conn->query("
    SELECT
        l.id as loan_id,
        l.ref_no,
        l.amount as principal,
        l.total_payable,
        l.monthly_installment,
        l.date_released,
        l.duration_months,
        CONCAT(b.lastname, ', ', b.firstname, ' ', b.middlename) as borrower_name,
        b.contact_no,
        lp.months,
        lp.penalty_rate,
        (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE loan_id = l.id) as total_paid,
        (SELECT COUNT(*) FROM payments WHERE loan_id = l.id) as payments_made,
        (SELECT MIN(date_due) FROM loan_schedules WHERE loan_id = l.id AND date_due >= CURDATE()
         AND id NOT IN (SELECT schedule_id FROM payments WHERE schedule_id IS NOT NULL)) as next_due_date
    FROM loan_list l
    INNER JOIN borrowers b ON l.borrower_id = b.id
    LEFT JOIN loan_plan lp ON l.plan_id = lp.id
    WHERE l.status = 2
    ORDER BY l.date_created DESC
");

// Get today for overdue check
$today = date('Y-m-d');
?>

<style>
    .payment-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 15px;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    .payment-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    }
    .payment-card.overdue {
        border-left: 4px solid #ef4444;
    }
    .payment-card.current {
        border-left: 4px solid #10b981;
    }
    .payment-card.completed {
        border-left: 4px solid #6b7280;
        opacity: 0.7;
    }
    .card-header-section {
        background: #f8fafc;
        padding: 15px 20px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .borrower-info h5 {
        margin: 0;
        font-weight: 600;
        color: #1f2937;
    }
    .borrower-info small {
        color: #6b7280;
    }
    .loan-ref {
        background: #e0e7ff;
        color: #4338ca;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .card-body-section {
        padding: 20px;
    }
    .loan-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 15px;
    }
    .stat-item {
        text-align: center;
    }
    .stat-label {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stat-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
    }
    .progress-section {
        margin-bottom: 15px;
    }
    .progress {
        height: 8px;
        border-radius: 4px;
        background: #e5e7eb;
    }
    .progress-bar {
        border-radius: 4px;
    }
    .action-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding-top: 15px;
        border-top: 1px solid #e5e7eb;
    }
    .btn-mark-paid {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-mark-paid:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        color: white;
    }
    .btn-mark-overdue {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-mark-overdue:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        color: white;
    }
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .status-overdue {
        background: #fee2e2;
        color: #dc2626;
    }
    .status-current {
        background: #d1fae5;
        color: #059669;
    }
    .status-completed {
        background: #e5e7eb;
        color: #4b5563;
    }
    .filter-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    .filter-tab {
        padding: 10px 20px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        background: white;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }
    .filter-tab:hover {
        border-color: #2563eb;
        color: #2563eb;
    }
    .filter-tab.active {
        background: #2563eb;
        border-color: #2563eb;
        color: white;
    }
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }
    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .summary-card.overdue-card {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        border-left: 4px solid #ef4444;
    }
    .summary-card.current-card {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-left: 4px solid #10b981;
    }
    .summary-card.total-card {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-left: 4px solid #2563eb;
    }
    .summary-card.collected-card {
        background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
        border-left: 4px solid #7c3aed;
    }
    .next-due {
        font-size: 0.85rem;
        color: #6b7280;
    }
    .next-due.overdue {
        color: #dc2626;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .loan-stats {
            grid-template-columns: repeat(2, 1fr);
        }
        .summary-cards {
            grid-template-columns: repeat(2, 1fr);
        }
        .filter-tabs {
            flex-wrap: wrap;
        }
    }
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fa fa-money-bill-wave"></i> Payment Management</h4>
            <p class="text-muted mb-0">Track and record loan payments from borrowers</p>
        </div>
        <button class="btn btn-primary" id="view_all_payments">
            <i class="fa fa-history"></i> View Payment History
        </button>
    </div>

    <?php
    // Calculate summary statistics
    $total_loans = 0;
    $total_receivable = 0;
    $total_collected = 0;
    $overdue_count = 0;
    $current_count = 0;

    $loans_data = [];
    while($loan = $loans_query->fetch_assoc()) {
        $total_loans++;
        $remaining = $loan['total_payable'] - $loan['total_paid'];
        $total_receivable += $remaining;
        $total_collected += $loan['total_paid'];

        // Check if overdue
        $is_overdue = false;
        if($loan['next_due_date'] && $loan['next_due_date'] < $today) {
            $is_overdue = true;
            $overdue_count++;
        } else {
            $current_count++;
        }

        $loan['is_overdue'] = $is_overdue;
        $loan['remaining'] = $remaining;
        $loans_data[] = $loan;
    }
    ?>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card total-card">
            <div class="stat-label">Active Loans</div>
            <div class="stat-value" style="font-size: 2rem;"><?php echo $total_loans ?></div>
        </div>
        <div class="summary-card collected-card">
            <div class="stat-label">Total Collected</div>
            <div class="stat-value" style="font-size: 1.5rem; color: #7c3aed;"><?php echo formatCurrency($total_collected) ?></div>
        </div>
        <div class="summary-card current-card">
            <div class="stat-label">Current</div>
            <div class="stat-value" style="font-size: 2rem; color: #059669;"><?php echo $current_count ?></div>
        </div>
        <div class="summary-card overdue-card">
            <div class="stat-label">Overdue</div>
            <div class="stat-value" style="font-size: 2rem; color: #dc2626;"><?php echo $overdue_count ?></div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <button class="filter-tab active" data-filter="all">
            <i class="fa fa-list"></i> All Loans (<?php echo $total_loans ?>)
        </button>
        <button class="filter-tab" data-filter="overdue">
            <i class="fa fa-exclamation-circle"></i> Overdue (<?php echo $overdue_count ?>)
        </button>
        <button class="filter-tab" data-filter="current">
            <i class="fa fa-check-circle"></i> Current (<?php echo $current_count ?>)
        </button>
    </div>

    <!-- Loan Payment Cards -->
    <div id="loans-container">
        <?php if(count($loans_data) == 0): ?>
            <div class="text-center py-5">
                <i class="fa fa-inbox fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No Active Loans</h5>
                <p class="text-muted">There are no released loans requiring payment tracking.</p>
            </div>
        <?php else: ?>
            <?php foreach($loans_data as $loan):
                $progress = $loan['total_payable'] > 0 ? ($loan['total_paid'] / $loan['total_payable']) * 100 : 0;
                $months_total = $loan['months'] ?? $loan['duration_months'] ?? 1;
                $payments_remaining = $months_total - $loan['payments_made'];
                $monthly = $loan['monthly_installment'] ?? ($loan['total_payable'] / $months_total);
                $penalty_rate = $loan['penalty_rate'] ?? 5;
                $penalty_amount = $loan['is_overdue'] ? ($monthly * $penalty_rate / 100) : 0;

                $card_class = $loan['is_overdue'] ? 'overdue' : 'current';
                $status_class = $loan['is_overdue'] ? 'status-overdue' : 'status-current';
                $status_text = $loan['is_overdue'] ? 'Overdue' : 'Current';

                // If fully paid
                if($loan['remaining'] <= 0) {
                    $card_class = 'completed';
                    $status_class = 'status-completed';
                    $status_text = 'Fully Paid';
                }
            ?>
            <div class="payment-card <?php echo $card_class ?>" data-status="<?php echo $card_class ?>">
                <div class="card-header-section">
                    <div class="borrower-info">
                        <h5><?php echo $loan['borrower_name'] ?></h5>
                        <small><i class="fa fa-phone"></i> <?php echo $loan['contact_no'] ?></small>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="status-badge <?php echo $status_class ?>"><?php echo $status_text ?></span>
                        <span class="loan-ref"><?php echo $loan['ref_no'] ?></span>
                    </div>
                </div>

                <div class="card-body-section">
                    <div class="loan-stats">
                        <div class="stat-item">
                            <div class="stat-label">Loan Amount</div>
                            <div class="stat-value"><?php echo formatCurrency($loan['principal']) ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Total Payable</div>
                            <div class="stat-value"><?php echo formatCurrency($loan['total_payable']) ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Paid</div>
                            <div class="stat-value" style="color: #059669;"><?php echo formatCurrency($loan['total_paid']) ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Remaining</div>
                            <div class="stat-value" style="color: <?php echo $loan['is_overdue'] ? '#dc2626' : '#1f2937' ?>;"><?php echo formatCurrency($loan['remaining']) ?></div>
                        </div>
                    </div>

                    <div class="progress-section">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Payment Progress</small>
                            <small class="text-muted"><?php echo $loan['payments_made'] ?> of <?php echo $months_total ?> payments (<?php echo number_format($progress, 1) ?>%)</small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: <?php echo min($progress, 100) ?>%"></div>
                        </div>
                    </div>

                    <?php if($loan['remaining'] > 0): ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="next-due <?php echo $loan['is_overdue'] ? 'overdue' : '' ?>">
                                <?php if($loan['next_due_date']): ?>
                                    <i class="fa fa-calendar"></i>
                                    Next Due: <?php echo date('M d, Y', strtotime($loan['next_due_date'])) ?>
                                    <?php if($loan['is_overdue']): ?>
                                        <span class="badge badge-danger ml-2">OVERDUE</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <i class="fa fa-check-circle text-success"></i> All scheduled payments made
                                <?php endif; ?>
                            </div>
                            <div class="mt-1">
                                <strong>Monthly: <?php echo formatCurrency($monthly) ?></strong>
                                <?php if($loan['is_overdue']): ?>
                                    <span class="text-danger ml-2">+ <?php echo formatCurrency($penalty_amount) ?> penalty</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="action-buttons" style="border: none; padding: 0;">
                            <button class="btn-mark-paid" onclick="recordPayment(<?php echo $loan['loan_id'] ?>, <?php echo $monthly ?>, 0)">
                                <i class="fa fa-check"></i> Mark Paid (<?php echo formatCurrency($monthly) ?>)
                            </button>
                            <?php if($loan['is_overdue']): ?>
                            <button class="btn-mark-overdue" onclick="recordPayment(<?php echo $loan['loan_id'] ?>, <?php echo $monthly ?>, <?php echo $penalty_amount ?>)">
                                <i class="fa fa-exclamation-triangle"></i> Paid + Penalty (<?php echo formatCurrency($monthly + $penalty_amount) ?>)
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Recording Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fa fa-check-circle"></i> Record Payment</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="quick-payment-form">
                    <input type="hidden" name="loan_id" id="modal_loan_id">

                    <div class="form-group">
                        <label>Payment Amount</label>
                        <input type="number" step="0.01" class="form-control form-control-lg" name="amount" id="modal_amount" required>
                    </div>

                    <div class="form-group">
                        <label>Penalty Amount</label>
                        <input type="number" step="0.01" class="form-control" name="penalty_amount" id="modal_penalty" value="0">
                    </div>

                    <div class="form-group">
                        <label>Payee Name</label>
                        <input type="text" class="form-control" name="payee" id="modal_payee" placeholder="Who made the payment?">
                    </div>

                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d') ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitPayment()">
                    <i class="fa fa-check"></i> Confirm Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-history"></i> Payment History</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <table class="table table-striped" id="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Borrower</th>
                            <th>Amount</th>
                            <th>Penalty</th>
                            <th>Action</th>
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
                            LIMIT 50
                        ");
                        while($h = $history->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($h['date_created'])) ?></td>
                            <td><?php echo $h['ref_no'] ?></td>
                            <td><?php echo $h['borrower_name'] ?></td>
                            <td><?php echo formatCurrency($h['amount']) ?></td>
                            <td><?php echo formatCurrency($h['penalty_amount']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" onclick="deletePayment(<?php echo $h['id'] ?>)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Filter functionality
$('.filter-tab').click(function() {
    $('.filter-tab').removeClass('active');
    $(this).addClass('active');

    var filter = $(this).data('filter');

    if(filter === 'all') {
        $('.payment-card').show();
    } else {
        $('.payment-card').hide();
        $('.payment-card[data-status="' + filter + '"]').show();
    }
});

// Record payment
function recordPayment(loanId, amount, penalty) {
    $('#modal_loan_id').val(loanId);
    $('#modal_amount').val(amount);
    $('#modal_penalty').val(penalty);
    $('#paymentModal').modal('show');
}

// Submit payment
function submitPayment() {
    var formData = $('#quick-payment-form').serialize();

    start_load();
    $.ajax({
        url: 'ajax.php?action=save_payment',
        method: 'POST',
        data: formData,
        success: function(resp) {
            if(resp == 1) {
                alert_toast('Payment recorded successfully', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                alert_toast('Error recording payment: ' + resp, 'error');
                end_load();
            }
        },
        error: function() {
            alert_toast('Server error', 'error');
            end_load();
        }
    });
}

// View payment history
$('#view_all_payments').click(function() {
    $('#historyModal').modal('show');
    if(!$.fn.DataTable.isDataTable('#history-table')) {
        $('#history-table').DataTable({
            order: [[0, 'desc']]
        });
    }
});

// Delete payment
function deletePayment(id) {
    if(!confirm('Are you sure you want to delete this payment?')) return;

    start_load();
    $.ajax({
        url: 'ajax.php?action=delete_payment',
        method: 'POST',
        data: {id: id},
        success: function(resp) {
            if(resp == 1) {
                alert_toast('Payment deleted', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                alert_toast('Error deleting payment', 'error');
                end_load();
            }
        }
    });
}
</script>
