<?php
include 'db_connect.php';
require_once 'includes/helpers.php';

if(isset($_GET['id'])){
	$id = $_GET['id'];
	$stmt = $conn->prepare("SELECT * FROM payments where id = ?");
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$qry = $stmt->get_result();
	foreach($qry->fetch_array() as $k => $val){
		$$k = $val;
	}
	$stmt->close();
}

?>
<style>
/* Modern Payment Form Styling */
.payment-form-container {
	max-width: 1200px;
	margin: 0 auto;
}

.form-section {
	background: var(--gray-50, #f9fafb);
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 20px;
}

.form-section-title {
	font-size: 1.1rem;
	font-weight: 600;
	color: var(--gray-800, #1f2937);
	margin-bottom: 15px;
	padding-bottom: 10px;
	border-bottom: 2px solid var(--primary-blue, #2563eb);
	display: flex;
	align-items: center;
}

.form-section-title i {
	margin-right: 10px;
	color: var(--primary-blue, #2563eb);
}

.info-alert {
	background: #dbeafe;
	border-left: 4px solid #2563eb;
	padding: 12px 15px;
	border-radius: 4px;
	font-size: 0.9rem;
}

/* Mobile responsive */
@media (max-width: 768px) {
	.form-section {
		padding: 15px;
	}

	.form-section-title {
		font-size: 1rem;
	}
}

#uni_modal .modal-footer,
.slide-over .modal-footer {
	display: none;
}
</style>

<div class="container-fluid payment-form-container">
	<form id="manage-payment">
		<input type="hidden" name="id" value="<?php echo isset($_GET['id']) ? $_GET['id'] : '' ?>">

		<!-- Section 1: Select Loan -->
		<div class="form-section">
			<div class="form-section-title">
				<i class="fa fa-file-invoice-dollar"></i> Select Loan
			</div>

			<div class="row">
				<div class="col-md-8">
					<div class="form-group">
						<label class="control-label">Loan Reference Number <span class="text-danger">*</span></label>
						<?php
						$status = 2; // Released loans only
						$stmt = $conn->prepare("
							SELECT l.*,
								CONCAT(b.lastname, ', ', b.firstname, ' ', b.middlename) as borrower_name,
								b.contact_no
							FROM loan_list l
							INNER JOIN borrowers b ON l.borrower_id = b.id
							WHERE l.status = ?
							ORDER BY l.date_created DESC
						");
						$stmt->bind_param("i", $status);
						$stmt->execute();
						$loan = $stmt->get_result();
						$stmt->close();
						?>
						<select name="loan_id" id="loan_id" class="custom-select browser-default select2" required>
							<option value="">Select a loan to make payment</option>
							<?php
							while($row=$loan->fetch_assoc()):
							?>
							<option value="<?php echo $row['id'] ?>"
								<?php echo isset($loan_id) && $loan_id == $row['id'] ? "selected" : '' ?>
								data-borrower="<?php echo htmlspecialchars($row['borrower_name']) ?>"
								data-amount="<?php echo $row['amount'] ?>">
								<?php echo $row['ref_no'] ?> - <?php echo $row['borrower_name'] ?> (<?php echo formatCurrency($row['amount']) ?>)
							</option>
							<?php endwhile; ?>
						</select>
						<small class="text-muted">Select the loan you want to make a payment for</small>
					</div>
				</div>

				<div class="col-md-4">
					<div class="info-alert">
						<i class="fa fa-info-circle"></i>
						<strong>Note:</strong> Only released loans are available for payment.
					</div>
				</div>
			</div>
		</div>

		<!-- Section 2: Loan Details & Payment Form (loaded dynamically) -->
		<div id="payment-fields"></div>
	</form>
</div>

<script>
$('#loan_id').change(function(){
	loadPaymentFields();
});

$('.select2').select2({
	placeholder: "Select a loan",
	width: "100%"
});

function loadPaymentFields(){
	var loanId = $('#loan_id').val();

	if(!loanId) {
		$('#payment-fields').html('');
		return;
	}

	start_load();

	$.ajax({
		url: 'load_payment_fields.php',
		method: "POST",
		data: {
			id: '<?php echo isset($id) ? $id : "" ?>',
			loan_id: loanId
		},
		success: function(resp){
			if(resp){
				$('#payment-fields').html(resp);
				end_load();
				initializePaymentForm();
			}
		},
		error: function(){
			alert_toast("Error loading payment details.", "error");
			end_load();
		}
	});
}

function initializePaymentForm(){
	// Quick amount buttons
	$('.quick-amount-btn').click(function(){
		var amount = $(this).data('amount');
		$('#payment-amount').val(amount);
	});
}

$('#manage-payment').submit(function(e){
	e.preventDefault();

	var loanId = $('#loan_id').val();
	if(!loanId) {
		alert_toast("Please select a loan first.", "warning");
		return false;
	}

	var amount = $('#payment-amount').val();
	if(!amount || amount <= 0) {
		alert_toast("Please enter a valid payment amount.", "warning");
		return false;
	}

	start_load();

	$.ajax({
		url: 'ajax.php?action=save_payment',
		method: 'POST',
		data: $(this).serialize(),
		success: function(resp){
			if(resp == 1){
				alert_toast("Payment successfully recorded!", "success");
				setTimeout(function(){
					$('.modal').modal('hide');
					$('.slide-over').removeClass('active');
					location.reload();
				}, 1500);
			} else {
				alert_toast("Error saving payment. Please try again.", "error");
				end_load();
			}
		},
		error: function(){
			alert_toast("Error processing payment.", "error");
			end_load();
		}
	});
});

$(document).ready(function(){
	if('<?php echo isset($_GET['id']) ?>' == 1) {
		loadPaymentFields();
	}
});
</script>
