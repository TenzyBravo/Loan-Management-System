<?php
include 'db_connect.php';
require_once 'includes/helpers.php';

$id = $_POST['id'] ?? null;
$loan_id = $_POST['loan_id'] ?? null;

if($id){
	$stmt = $conn->prepare("SELECT * FROM payments where id = ?");
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$qry = $stmt->get_result();
	if($qry->num_rows > 0) {
		foreach($qry->fetch_array() as $k => $val){
			$$k = $val;
		}
	}
	$stmt->close();
}

// Get loan details with borrower info
$stmt = $conn->prepare("
	SELECT l.*,
		CONCAT(b.lastname, ', ', b.firstname, ' ', b.middlename) as name,
		b.contact_no,
		b.address
	FROM loan_list l
	INNER JOIN borrowers b ON b.id = l.borrower_id
	WHERE l.id = ?
");
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$loan = $stmt->get_result();
foreach($loan->fetch_array() as $k => $v){
	$meta[$k] = $v;
}
$stmt->close();

// Calculate totals using the actual loan data
$annual_rate = $meta['interest_rate'];
$months = $meta['duration_months'];
$monthly_rate = $annual_rate / 12 / 100;

// Use simple interest calculation
$total_interest = $meta['amount'] * $monthly_rate * $months;
$total_payable = $meta['amount'] + $total_interest;
$monthly = $total_payable / $months;

// Get penalty rate from loan_plan if exists
$penalty_rate = 5; // Default 5%
if($meta['plan_id']) {
	$stmt = $conn->prepare("SELECT penalty_rate FROM loan_plan WHERE id = ?");
	$stmt->bind_param("i", $meta['plan_id']);
	$stmt->execute();
	$plan_result = $stmt->get_result();
	if($plan_row = $plan_result->fetch_assoc()) {
		$penalty_rate = $plan_row['penalty_rate'];
	}
	$stmt->close();
}

$penalty = $monthly * ($penalty_rate / 100);

// Get existing payments
$stmt = $conn->prepare("SELECT * FROM payments WHERE loan_id = ? ORDER BY date_created DESC");
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$payments = $stmt->get_result();
$paid_count = $payments->num_rows;

// Calculate total paid
$sum_paid = 0;
$payment_history = [];
while($p = $payments->fetch_assoc()){
	$sum_paid += ($p['amount'] - $p['penalty_amount']);
	$payment_history[] = $p;
}
$stmt->close();

// Get next schedule date
$stmt2 = $conn->prepare("SELECT * FROM loan_schedules WHERE loan_id = ? ORDER BY DATE(date_due) ASC LIMIT 1 OFFSET ?");
$offset = $paid_count > 0 ? $paid_count : 0;
$stmt2->bind_param("ii", $loan_id, $offset);
$stmt2->execute();
$next_schedule = $stmt2->get_result()->fetch_assoc();
$next = $next_schedule['date_due'] ?? date('Y-m-d');
$stmt2->close();

// Check if overdue
$is_overdue = (date('Ymd', strtotime($next)) < date("Ymd"));
$penalty_amount = $is_overdue ? $penalty : 0;

// Calculate remaining balance
$remaining_balance = $total_payable - $sum_paid;
$payment_progress = ($sum_paid / $total_payable) * 100;

?>
<style>
.loan-info-card {
	background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 20px;
	border: 2px solid #2563eb;
}

.loan-info-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
}

.loan-info-item {
	padding: 10px 0;
}

.loan-info-label {
	font-size: 0.85rem;
	font-weight: 500;
	color: #6b7280;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	margin-bottom: 4px;
}

.loan-info-value {
	font-size: 1.1rem;
	font-weight: 600;
	color: #1f2937;
}

.payment-progress-card {
	background: white;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 20px;
	border: 1px solid #e5e7eb;
}

.progress {
	height: 30px;
	border-radius: 15px;
	background: #f3f4f6;
	margin: 15px 0;
}

.progress-bar {
	border-radius: 15px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-weight: 600;
	font-size: 0.9rem;
}

.progress-details {
	display: flex;
	justify-content: space-between;
	font-size: 0.9rem;
	color: #6b7280;
	margin-top: 10px;
}

.payment-summary {
	background: white;
	border-radius: 8px;
	padding: 20px;
	border: 2px solid #10b981;
	box-shadow: 0 2px 8px rgba(16, 185, 129, 0.1);
	margin-bottom: 20px;
}

.payment-summary-item {
	display: flex;
	justify-content: space-between;
	padding: 10px 0;
	border-bottom: 1px solid #e5e7eb;
	font-size: 1rem;
}

.payment-summary-item:last-child {
	border-bottom: none;
	font-size: 1.25rem;
	font-weight: 700;
	color: #10b981;
	padding-top: 15px;
	margin-top: 10px;
	border-top: 2px solid #10b981;
}

.payment-summary-item.has-penalty {
	color: #dc3545;
}

.overdue-badge {
	display: inline-block;
	background: #fee2e2;
	color: #dc3545;
	padding: 4px 12px;
	border-radius: 12px;
	font-size: 0.85rem;
	font-weight: 600;
	margin-left: 10px;
}

.quick-amount-buttons {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
	gap: 10px;
	margin: 15px 0;
}

.quick-amount-btn {
	padding: 12px 16px;
	border: 2px solid #e5e7eb;
	border-radius: 8px;
	background: white;
	cursor: pointer;
	transition: all 0.2s;
	text-align: center;
	font-weight: 500;
}

.quick-amount-btn:hover {
	border-color: #2563eb;
	background: #eff6ff;
	transform: translateY(-2px);
}

.quick-amount-btn .amount-label {
	display: block;
	font-size: 0.85rem;
	color: #6b7280;
	margin-bottom: 5px;
}

.quick-amount-btn .amount-value {
	display: block;
	font-size: 1.1rem;
	font-weight: 700;
	color: #2563eb;
}

.amount-input-wrapper {
	position: relative;
}

.currency-symbol {
	position: absolute;
	left: 15px;
	top: 50%;
	transform: translateY(-50%);
	font-weight: 600;
	color: #6b7280;
	font-size: 1.1rem;
}

.amount-input {
	padding-left: 40px !important;
	font-size: 1.25rem !important;
	font-weight: 600 !important;
	color: #1f2937 !important;
}

.warning-alert {
	background: #fff3cd;
	border-left: 4px solid #f59e0b;
	padding: 12px 15px;
	border-radius: 4px;
	margin-bottom: 15px;
	font-size: 0.9rem;
}

.payment-history-table {
	margin-top: 20px;
}

.payment-history-table table {
	font-size: 0.9rem;
}

/* Mobile responsive */
@media (max-width: 768px) {
	.loan-info-grid {
		grid-template-columns: 1fr;
	}

	.quick-amount-buttons {
		grid-template-columns: 1fr;
	}

	.progress-details {
		flex-direction: column;
		gap: 5px;
	}
}
</style>

<!-- Loan Information Card -->
<div class="form-section">
	<div class="form-section-title">
		<i class="fa fa-info-circle"></i> Loan Information
	</div>

	<div class="loan-info-card">
		<div class="loan-info-grid">
			<div class="loan-info-item">
				<div class="loan-info-label">Borrower</div>
				<div class="loan-info-value"><?php echo $meta['name'] ?></div>
			</div>
			<div class="loan-info-item">
				<div class="loan-info-label">Loan Reference</div>
				<div class="loan-info-value"><?php echo $meta['ref_no'] ?></div>
			</div>
			<div class="loan-info-item">
				<div class="loan-info-label">Principal Amount</div>
				<div class="loan-info-value"><?php echo formatCurrency($meta['amount']) ?></div>
			</div>
			<div class="loan-info-item">
				<div class="loan-info-label">Interest Rate</div>
				<div class="loan-info-value"><?php echo $annual_rate ?>%</div>
			</div>
			<div class="loan-info-item">
				<div class="loan-info-label">Duration</div>
				<div class="loan-info-value"><?php echo $months ?> months</div>
			</div>
			<div class="loan-info-item">
				<div class="loan-info-label">Total Payable</div>
				<div class="loan-info-value"><?php echo formatCurrency($total_payable) ?></div>
			</div>
		</div>
	</div>

	<!-- Payment Progress -->
	<div class="payment-progress-card">
		<h6 style="font-weight: 600; margin-bottom: 10px;">
			<i class="fa fa-chart-line"></i> Payment Progress
		</h6>
		<div class="progress">
			<div class="progress-bar bg-success" style="width: <?php echo min($payment_progress, 100) ?>%">
				<?php echo number_format($payment_progress, 1) ?>%
			</div>
		</div>
		<div class="progress-details">
			<span><strong>Paid:</strong> <?php echo formatCurrency($sum_paid) ?></span>
			<span><strong>Remaining:</strong> <?php echo formatCurrency($remaining_balance) ?></span>
			<span><strong>Payments Made:</strong> <?php echo $paid_count ?> of <?php echo $months ?></span>
		</div>
	</div>
</div>

<!-- Payment Amount Section -->
<div class="form-section">
	<div class="form-section-title">
		<i class="fa fa-money-bill-wave"></i> Payment Amount
	</div>

	<?php if($is_overdue): ?>
	<div class="warning-alert">
		<i class="fa fa-exclamation-triangle"></i>
		<strong>Overdue Payment!</strong> This payment is overdue. A penalty of <?php echo formatCurrency($penalty_amount) ?> will be added.
	</div>
	<?php endif; ?>

	<!-- Quick Amount Buttons -->
	<div class="quick-amount-buttons">
		<button type="button" class="quick-amount-btn" data-amount="<?php echo $monthly ?>">
			<span class="amount-label">Monthly Payment</span>
			<span class="amount-value"><?php echo formatCurrency($monthly) ?></span>
		</button>

		<?php if($is_overdue): ?>
		<button type="button" class="quick-amount-btn" data-amount="<?php echo $monthly + $penalty_amount ?>">
			<span class="amount-label">With Penalty</span>
			<span class="amount-value"><?php echo formatCurrency($monthly + $penalty_amount) ?></span>
		</button>
		<?php endif; ?>

		<button type="button" class="quick-amount-btn" data-amount="<?php echo $remaining_balance ?>">
			<span class="amount-label">Full Balance</span>
			<span class="amount-value"><?php echo formatCurrency($remaining_balance) ?></span>
		</button>

		<button type="button" class="quick-amount-btn" data-amount="<?php echo $monthly * 2 ?>">
			<span class="amount-label">Double Payment</span>
			<span class="amount-value"><?php echo formatCurrency($monthly * 2) ?></span>
		</button>
	</div>

	<!-- Payment Summary -->
	<div class="payment-summary">
		<div class="payment-summary-item">
			<span>Monthly Payment:</span>
			<strong><?php echo formatCurrency($monthly) ?></strong>
		</div>
		<?php if($is_overdue): ?>
		<div class="payment-summary-item has-penalty">
			<span>Penalty (Overdue):</span>
			<strong><?php echo formatCurrency($penalty_amount) ?></strong>
		</div>
		<?php endif; ?>
		<div class="payment-summary-item">
			<span>Payable Amount:</span>
			<strong><?php echo formatCurrency($monthly + $penalty_amount) ?></strong>
		</div>
	</div>

	<!-- Payment Form Fields -->
	<div class="row">
		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">Payee Name</label>
				<input type="text" name="payee" class="form-control" required value="<?php echo isset($payee) ? $payee : $meta['name'] ?>">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">Payment Amount <span class="text-danger">*</span></label>
				<div class="amount-input-wrapper">
					<span class="currency-symbol">K</span>
					<input type="number" name="amount" id="payment-amount" step="0.01" min="0" class="form-control text-right amount-input" required value="<?php echo isset($amount) ? $amount : '' ?>" placeholder="0.00">
				</div>
				<small class="text-muted">Enter the payment amount</small>
			</div>
		</div>
	</div>

	<!-- Hidden Fields -->
	<input type="hidden" name="penalty_amount" value="<?php echo $penalty_amount ?>">
	<input type="hidden" name="loan_id" value="<?php echo $loan_id ?>">
	<input type="hidden" name="overdue" value="<?php echo $is_overdue ? 1 : 0 ?>">

	<!-- Submit Button -->
	<div class="text-right mt-3">
		<button class="btn btn-secondary mr-2" type="button" onclick="$('.modal').modal('hide'); $('.slide-over').removeClass('active');">
			<i class="fa fa-times"></i> Cancel
		</button>
		<button class="btn btn-success btn-lg" type="submit">
			<i class="fa fa-check-circle"></i> Record Payment
		</button>
	</div>
</div>

<!-- Payment History -->
<?php if(count($payment_history) > 0): ?>
<div class="form-section">
	<div class="form-section-title">
		<i class="fa fa-history"></i> Recent Payments
	</div>

	<div class="payment-history-table">
		<table class="table table-sm table-hover">
			<thead class="thead-light">
				<tr>
					<th>Date</th>
					<th>Payee</th>
					<th>Amount</th>
					<th>Penalty</th>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach(array_slice($payment_history, 0, 5) as $p): ?>
				<tr>
					<td><?php echo date('M d, Y', strtotime($p['date_created'])) ?></td>
					<td><?php echo $p['payee'] ?? '-' ?></td>
					<td><?php echo formatCurrency($p['amount'] - $p['penalty_amount']) ?></td>
					<td><?php echo $p['penalty_amount'] > 0 ? formatCurrency($p['penalty_amount']) : '-' ?></td>
					<td><strong><?php echo formatCurrency($p['amount']) ?></strong></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php endif; ?>
