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
<div class="container-fluid">
	<div class="col-lg-12">
	<form action="" id="loan-application">
		<input type="hidden" name="id" value="<?php echo isset($_GET['id']) ? $_GET['id'] : '' ?>">
		<div class="row">
			<div class="col-md-6">
				<label class="control-label">Borrower</label>
				<?php
				$borrower = $conn->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM borrowers order by concat(lastname,', ',firstname,' ',middlename) asc ");
				?>
				<select name="borrower_id" id="borrower_id" class="custom-select browser-default select2">
					<option value=""></option>
						<?php while($row = $borrower->fetch_assoc()): ?>
							<option value="<?php echo $row['id'] ?>" <?php echo isset($borrower_id) && $borrower_id == $row['id'] ? "selected" : '' ?>><?php echo $row['name'] . ' | Tax ID:'.$row['tax_id'] ?></option>
						<?php endwhile; ?>
				</select>
			</div>
			<div class="col-md-6">
				<label class="control-label">Loan Type</label>
				<?php
				$type = $conn->query("SELECT * FROM loan_types order by `type_name` desc ");
				?>
				<select name="loan_type_id" id="loan_type_id" class="custom-select browser-default select2">
					<option value=""></option>
						<?php while($row = $type->fetch_assoc()): ?>
							<option value="<?php echo $row['id'] ?>" <?php echo isset($loan_type_id) && $loan_type_id == $row['id'] ? "selected" : '' ?>><?php echo $row['type_name'] ?></option>
						<?php endwhile; ?>
				</select>
			</div>
			
		</div>

		<div class="row">
			<div class="col-md-6">
				<label class="control-label">Loan Plan</label>
				<?php
				$plan = $conn->query("SELECT * FROM loan_plan order by `months` desc ");
				?>
				<select name="plan_id" id="plan_id" class="custom-select browser-default select2" onchange="updateDurationFromPlan()">
					<option value=""></option>
						<?php while($row = $plan->fetch_assoc()): ?>
							<option value="<?php echo $row['id'] ?>" <?php echo isset($plan_id) && $plan_id == $row['id'] ? "selected" : '' ?> data-months="<?php echo $row['months'] ?>" data-interest_percentage="<?php echo $row['interest_percentage'] ?>" data-penalty_rate="<?php echo $row['penalty_rate'] ?>"><?php echo $row['months'] . ' month/s [ '.$row['interest_percentage'].'%, '.$row['penalty_rate'].'% ]' ?></option>
						<?php endwhile; ?>
				</select>
				<small>months [ interest%,penalty% ]</small>
			</div>
			<div class="form-group col-md-6">
				<label class="control-label">Loan Amount</label>
				<input type="number" name="amount" class="form-control text-right" step="any" id="loan_amount" value="<?php echo isset($amount) ? $amount : '' ?>">
			</div>
		</div>
		<div class="row">
			<div class="form-group col-md-6">
				<label class="control-label">Annual Interest Rate</label>
				<select name="interest_rate" id="interest_rate" class="custom-select browser-default">
					<option value="0" <?php echo (!isset($interest_rate) || $interest_rate == 0) ? "selected" : '' ?>>Not Set (Pending Review)</option>
					<option value="10.0" <?php echo isset($interest_rate) && $interest_rate == 10.0 ? "selected" : '' ?>>10%</option>
					<option value="18.0" <?php echo isset($interest_rate) && $interest_rate == 18.0 ? "selected" : '' ?>>18%</option>
					<option value="25.0" <?php echo isset($interest_rate) && $interest_rate == 25.0 ? "selected" : '' ?>>25%</option>
					<option value="28.0" <?php echo isset($interest_rate) && $interest_rate == 28.0 ? "selected" : '' ?>>28%</option>
					<option value="30.0" <?php echo isset($interest_rate) && $interest_rate == 30.0 ? "selected" : '' ?>>30%</option>
					<option value="35.0" <?php echo isset($interest_rate) && $interest_rate == 35.0 ? "selected" : '' ?>>35%</option>
					<option value="40.0" <?php echo isset($interest_rate) && $interest_rate == 40.0 ? "selected" : '' ?>>40%</option>
				</select>
				<small class="text-muted">Loans ≤ K5,000 auto-assigned 18%. For loans > K5,000, select appropriate rate.</small>
			</div>
			<div class="form-group col-md-6">
				<label class="control-label">Calculation Type</label>
				<select name="calculation_type" id="calculation_type" class="custom-select browser-default">
					<option value="simple" <?php echo (isset($calculation_type) && $calculation_type == 'simple') ? "selected" : '' ?>>Simple Interest</option>
					<option value="compound" <?php echo (isset($calculation_type) && $calculation_type == 'compound') ? "selected" : '' ?>>Compound Interest</option>
				</select>
			</div>
		</div>
		<div class="row">
			<div class="form-group col-md-6">
				<label class="control-label">Purpose</label>
				<textarea name="purpose" id="" cols="30" rows="2" class="form-control"><?php echo isset($purpose) ? $purpose : '' ?></textarea>
			</div>
			<div class="form-group col-md-6">
				<label class="control-label">Duration (Months)</label>
				<input type="number" name="duration_months" class="form-control text-right" step="1" id="duration_months" value="<?php echo isset($duration_months) ? $duration_months : (isset($months) ? $months : '') ?>">
			</div>
		</div>
		<div class="row">
			<div class="form-group col-md-2 offset-md-8 .justify-content-center">
				<label class="control-label">&nbsp;</label>
				<button class="btn btn-primary btn-sm btn-block align-self-end" type="button" id="calculate">Calculate</button>
			</div>
		</div>
		<div id="calculation_table">
			
		</div>
		<?php if(isset($status)): ?>
		<div class="row">
			<div class="form-group col-md-6">
				<label class="control-label">&nbsp;</label>
				<select class="custom-select browser-default" name="status">
					<option value="0" <?php echo $status == 0 ? "selected" : '' ?>>For Approval</option>
					<option value="1" <?php echo $status == 1 ? "selected" : '' ?>>Approved</option>
					<?php if($status !='4' ): ?>
					<option value="2" <?php echo $status == 2 ? "selected" : '' ?>>Released</option>
					<?php endif ?>
					<?php if($status =='2' ): ?>
					<option value="3" <?php echo $status == 3 ? "selected" : '' ?>>Complete</option>
					<?php endif ?>
					<?php if($status !='2' ): ?>
					<option value="4" <?php echo $status == 4 ? "selected" : '' ?>>Denied</option>
					<?php endif ?>
				</select>
			</div>
		</div>
		<hr>
	<?php endif ?>
		<div id="row-field">
			<div class="row ">
				<div class="col-md-12 text-center">
					<button class="btn btn-primary btn-sm " >Save</button>
					<button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
				</div>
			</div>
		</div>
		
	</form>
	</div>
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
		if(months) {
			$('#duration_months').val(months);
		}
	}

	$('#calculate').click(function(){
		calculate()
	})

	function calculate(){
		start_load()

		var amount = $('[name="amount"]').val();
		var interest_rate = $('#interest_rate').val();
		var duration_months = $('#duration_months').val();
		var calculation_type = $('#calculation_type').val();

		if(amount == '' || amount <= 0){
			alert_toast("Enter loan amount first.","warning");
			return false;
		}

		if(interest_rate == ''){
			alert_toast("Select interest rate first.","warning");
			return false;
		}

		if(duration_months == '' || duration_months <= 0){
			alert_toast("Enter duration in months first.","warning");
			return false;
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
				}
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
					alert_toast("Loan Data successfully saved.","success")
					setTimeout(function(){
						location.reload();
					},1500)
				}
			}
		})
	})

	$(document).ready(function(){
		if('<?php echo isset($_GET['id']) ?>' == 1)
			calculate()
	})
</script>
<style>
	#uni_modal .modal-footer{
		display: none
	}
</style>