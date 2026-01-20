<?php
include('db_connect.php');

// Initialize loan data variables
$loan_data = [];
if(isset($_GET['id'])){
	$id = $_GET['id'];
	$stmt = $conn->prepare("SELECT * FROM loan_list where id = ?");
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$result = $stmt->get_result();
	$loan_data = $result->fetch_assoc();
	$stmt->close();

	// Safely extract individual variables
	if ($loan_data) {
		$id = $loan_data['id'] ?? '';
		$borrower_id = $loan_data['borrower_id'] ?? '';
		$loan_type_id = $loan_data['loan_type_id'] ?? '';
		$plan_id = $loan_data['plan_id'] ?? '';
		$amount = $loan_data['amount'] ?? '';
		$purpose = $loan_data['purpose'] ?? '';
		$status = $loan_data['status'] ?? '';
		$interest_rate = $loan_data['interest_rate'] ?? '';
		$calculation_type = $loan_data['calculation_type'] ?? '';
		$duration_months = $loan_data['duration_months'] ?? '';
		$monthly_installment = $loan_data['monthly_installment'] ?? '';
		$months = $loan_data['months'] ?? '';
	}
}
?>
<style>
/* Modern Form Layout */
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
	margin-bottom: 15px;
	font-size: 0.9rem;
}

.calculation-result {
	background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
	border-radius: 8px;
	padding: 20px;
	margin-top: 15px;
}

.result-row {
	display: flex;
	justify-content: space-between;
	padding: 10px 0;
	border-bottom: 1px solid #cbd5e1;
}

.result-row:last-child {
	border-bottom: none;
	font-weight: 700;
	font-size: 1.1rem;
	color: var(--primary-blue, #2563eb);
}

.result-label {
	font-weight: 500;
	color: var(--gray-700, #374151);
}

.result-value {
	font-weight: 600;
	color: var(--gray-900, #111827);
}

.btn-calculate {
	background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
	border: none;
	padding: 12px 30px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-calculate:hover {
	transform: translateY(-2px);
	box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
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

#uni_modal .modal-footer{
	display: none
}
</style>

<div class="container-fluid">
	<form action="" id="loan-application">
		<input type="hidden" name="id" value="<?php echo isset($_GET['id']) ? $_GET['id'] : '' ?>">

		<!-- Section 1: Basic Information -->
		<div class="form-section">
			<div class="form-section-title">
				<i class="fa fa-user-tie"></i> Basic Loan Information
			</div>

			<div class="row">
				<div class="col-md-6 form-group">
					<label class="control-label">Borrower <span class="text-danger">*</span></label>
					<?php
					$borrower = $conn->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM borrowers order by concat(lastname,', ',firstname,' ',middlename) asc ");
					?>
					<select name="borrower_id" id="borrower_id" class="custom-select browser-default select2" required>
						<option value="">Select Borrower</option>
						<?php while($row = $borrower->fetch_assoc()): ?>
							<option value="<?php echo $row['id'] ?>" <?php echo isset($borrower_id) && $borrower_id == $row['id'] ? "selected" : '' ?>>
								<?php echo $row['name'] . ' | Tax ID:'.$row['tax_id'] ?>
							</option>
						<?php endwhile; ?>
					</select>
				</div>

				<div class="col-md-6 form-group">
					<label class="control-label">Loan Type <span class="text-danger">*</span></label>
					<?php
					$type = $conn->query("SELECT * FROM loan_types order by `type_name` desc ");
					?>
					<select name="loan_type_id" id="loan_type_id" class="custom-select browser-default select2" required>
						<option value="">Select Loan Type</option>
						<?php while($row = $type->fetch_assoc()): ?>
							<option value="<?php echo $row['id'] ?>" <?php echo isset($loan_type_id) && $loan_type_id == $row['id'] ? "selected" : '' ?>>
								<?php echo $row['type_name'] ?>
							</option>
						<?php endwhile; ?>
					</select>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6 form-group">
					<label class="control-label">Loan Amount (K) <span class="text-danger">*</span></label>
					<input type="number" name="amount" class="form-control text-right" step="any" id="loan_amount" value="<?php echo isset($amount) ? $amount : '' ?>" placeholder="Enter amount" required>
					<small class="text-muted">Enter the principal loan amount</small>
				</div>

				<div class="col-md-6 form-group">
					<label class="control-label">Loan Plan</label>
					<?php
					$plan = $conn->query("SELECT * FROM loan_plan order by `months` desc ");
					?>
					<select name="plan_id" id="plan_id" class="custom-select browser-default select2" onchange="updateDurationFromPlan()">
						<option value="">Select Loan Plan (Optional)</option>
						<?php while($row = $plan->fetch_assoc()): ?>
							<option value="<?php echo $row['id'] ?>"
									<?php echo isset($plan_id) && $plan_id == $row['id'] ? "selected" : '' ?>
									data-months="<?php echo $row['months'] ?>"
									data-interest_percentage="<?php echo $row['interest_percentage'] ?>"
									data-penalty_rate="<?php echo $row['penalty_rate'] ?>">
								<?php echo $row['months'] . ' month/s [ '.$row['interest_percentage'].'%, '.$row['penalty_rate'].'% penalty ]' ?>
							</option>
						<?php endwhile; ?>
					</select>
					<small class="text-muted">Optional: Select a predefined plan to auto-fill interest and duration</small>
				</div>
			</div>

			<div class="form-group">
				<label class="control-label">Purpose of Loan</label>
				<textarea name="purpose" cols="30" rows="3" class="form-control" placeholder="Enter the purpose of this loan..."><?php echo isset($purpose) ? $purpose : '' ?></textarea>
			</div>
		</div>

		<!-- Section 2: Loan Terms -->
		<div class="form-section">
			<div class="form-section-title">
				<i class="fa fa-percent"></i> Loan Terms & Interest
			</div>

			<div class="info-alert">
				<i class="fa fa-info-circle"></i>
				<strong>Interest Rate Policy:</strong> Loans ≤ K5,000 are automatically assigned 18% interest rate. For loans > K5,000, select the appropriate rate based on risk assessment.
			</div>

			<div class="row">
				<div class="col-md-6 form-group">
					<label class="control-label">Annual Interest Rate <span class="text-danger">*</span></label>
					<select name="interest_rate" id="interest_rate" class="custom-select browser-default" required>
						<option value="0" <?php echo (!isset($interest_rate) || $interest_rate == 0) ? "selected" : '' ?>>Not Set (Pending Review)</option>
						<option value="10.0" <?php echo isset($interest_rate) && $interest_rate == 10.0 ? "selected" : '' ?>>10% - Low Risk</option>
						<option value="18.0" <?php echo isset($interest_rate) && $interest_rate == 18.0 ? "selected" : '' ?>>18% - Standard (Auto for ≤K5,000)</option>
						<option value="25.0" <?php echo isset($interest_rate) && $interest_rate == 25.0 ? "selected" : '' ?>>25% - Moderate Risk</option>
						<option value="28.0" <?php echo isset($interest_rate) && $interest_rate == 28.0 ? "selected" : '' ?>>28% - Medium Risk</option>
						<option value="30.0" <?php echo isset($interest_rate) && $interest_rate == 30.0 ? "selected" : '' ?>>30% - Higher Risk</option>
						<option value="35.0" <?php echo isset($interest_rate) && $interest_rate == 35.0 ? "selected" : '' ?>>35% - High Risk</option>
						<option value="40.0" <?php echo isset($interest_rate) && $interest_rate == 40.0 ? "selected" : '' ?>>40% - Very High Risk</option>
					</select>
				</div>

				<div class="col-md-6 form-group">
					<label class="control-label">Calculation Type</label>
					<select name="calculation_type" id="calculation_type" class="custom-select browser-default">
						<option value="simple" <?php echo (isset($calculation_type) && $calculation_type == 'simple') ? "selected" : '' ?>>Simple Interest</option>
						<option value="compound" <?php echo (isset($calculation_type) && $calculation_type == 'compound') ? "selected" : '' ?>>Compound Interest</option>
					</select>
					<small class="text-muted">Simple interest is recommended for most loans</small>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6 form-group">
					<label class="control-label">Duration (Months) <span class="text-danger">*</span></label>
					<input type="number" name="duration_months" class="form-control text-right" step="1" id="duration_months" value="<?php echo isset($duration_months) ? $duration_months : (isset($months) ? $months : '') ?>" placeholder="Enter duration" required min="1">
					<small class="text-muted">Loan repayment period in months</small>
				</div>

				<div class="col-md-6 form-group d-flex align-items-end">
					<button class="btn btn-primary btn-calculate btn-block" type="button" id="calculate">
						<i class="fa fa-calculator"></i> Calculate Loan Terms
					</button>
				</div>
			</div>
		</div>

		<!-- Section 3: Calculation Results -->
		<div id="calculation_table"></div>

		<!-- Section 4: Status (Only for existing loans) -->
		<?php if(isset($status)): ?>
		<div class="form-section">
			<div class="form-section-title">
				<i class="fa fa-tasks"></i> Loan Status
			</div>

			<div class="row">
				<div class="col-md-6 form-group">
					<label class="control-label">Application Status</label>
					<select class="custom-select browser-default" name="status">
						<option value="0" <?php echo $status == 0 ? "selected" : '' ?>>
							<i class="fa fa-clock"></i> For Approval
						</option>
						<option value="1" <?php echo $status == 1 ? "selected" : '' ?>>
							<i class="fa fa-check"></i> Approved
						</option>
						<?php if($status !='4' ): ?>
						<option value="2" <?php echo $status == 2 ? "selected" : '' ?>>
							<i class="fa fa-money-bill-wave"></i> Released
						</option>
						<?php endif ?>
						<?php if($status =='2' ): ?>
						<option value="3" <?php echo $status == 3 ? "selected" : '' ?>>
							<i class="fa fa-check-circle"></i> Complete
						</option>
						<?php endif ?>
						<?php if($status !='2' ): ?>
						<option value="4" <?php echo $status == 4 ? "selected" : '' ?>>
							<i class="fa fa-ban"></i> Denied
						</option>
						<?php endif ?>
					</select>
				</div>
			</div>
		</div>
		<?php endif ?>

		<!-- Action Buttons -->
		<div class="form-section" style="background: white; border: 1px solid #e5e7eb;">
			<div class="row">
				<div class="col-md-12 text-right">
					<button class="btn btn-secondary" type="button" data-dismiss="modal">
						<i class="fa fa-times"></i> Cancel
					</button>
					<button class="btn btn-primary btn-lg" type="submit">
						<i class="fa fa-save"></i> Save Loan Application
					</button>
				</div>
			</div>
		</div>
	</form>
</div>

<script>
	$('.select2').select2({
		placeholder:"Please select here",
		width:"100%"
	})

	// Function to update duration from selected plan
	function updateDurationFromPlan() {
		var plan = $("#plan_id option[value='"+$("#plan_id").val()+"']");
		var months = plan.attr('data-months');
		var interest = plan.attr('data-interest_percentage');

		if(months) {
			$('#duration_months').val(months);
			checkDurationAndSetRate(); // Check if 1-month to auto-set rate
		}
		if(interest && $('#interest_rate').val() == '0') {
			$('#interest_rate').val(interest);
		}
	}

	// BUSINESS RULE: Auto-set 18% for 1-month loans
	function checkDurationAndSetRate() {
		var duration = parseInt($('#duration_months').val());

		if(duration === 1) {
			// 1-month loan: automatically set to 18%
			$('#interest_rate').val('18.0');
			$('#interest_rate').prop('disabled', true);
			$('#interest_rate').after('<small class="text-success d-block mt-1" id="auto-rate-notice"><i class="fa fa-check-circle"></i> Auto-set: 1-month loans = 18%</small>');
		} else {
			// Multi-month loan: admin must select rate
			$('#interest_rate').prop('disabled', false);
			$('#auto-rate-notice').remove();
		}
	}

	// Monitor duration changes
	$('#duration_months').on('change keyup', function(){
		checkDurationAndSetRate();
	});

	$('#calculate').click(function(){
		calculate()
	})

	function calculate(){
		start_load()

		var amount = $('[name="amount"]').val();
		var interest_rate = $('#interest_rate').val();
		var duration_months = parseInt($('#duration_months').val());
		var calculation_type = $('#calculation_type').val();

		if(amount == '' || amount <= 0){
			alert_toast("Enter loan amount first.","warning");
			end_load();
			return false;
		}

		if(duration_months == '' || duration_months <= 0){
			alert_toast("Enter duration in months first.","warning");
			end_load();
			return false;
		}

		// BUSINESS RULE: 1-month loans auto-set to 18%
		if(duration_months === 1) {
			interest_rate = '18.0';
			$('#interest_rate').val('18.0');
		}
		// Multi-month loans: require admin to set rate
		else if(interest_rate == '' || interest_rate == '0'){
			// Allow preview calculation but with warning
			// The calculation_table.php will show the warning
		}

		$.ajax({
			url:"calculation_table.php",
			method:"POST",
			data:{
				amount: amount,
				interest_rate: interest_rate,
				duration_months: duration_months,
				calculation_type: calculation_type
			},
			success:function(resp){
				if(resp){
					$('#calculation_table').html(resp)
					end_load()
					// Scroll to results
					$('#calculation_table').get(0).scrollIntoView({ behavior: 'smooth' });
				}
			},
			error: function(){
				alert_toast("Error calculating loan terms.","error");
				end_load();
			}
		})
	}

	$('#loan-application').submit(function(e){
		e.preventDefault()
		start_load()
		$.ajax({
			url:'ajax.php?action=save_loan',
			method:"POST",
			data:$(this).serialize(),
			success:function(resp){
				if(resp ==1 ){
					$('.modal').modal('hide')
					$('.slide-over').removeClass('active')
					alert_toast("Loan Data successfully saved.","success")
					setTimeout(function(){
						location.reload();
					},1500)
				}
			},
			error: function(){
				alert_toast("Error saving loan data.","error");
				end_load();
			}
		})
	})

	$(document).ready(function(){
		if('<?php echo isset($_GET['id']) ?>' == 1)
			calculate()
	})
</script>
