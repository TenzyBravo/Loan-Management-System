<?php 
// Extract POST data safely
extract($_POST);

// Validate and set defaults for required variables
$amount = isset($amount) && is_numeric($amount) ? floatval($amount) : 0;
$interest_rate = isset($_POST['interest_rate']) && is_numeric($_POST['interest_rate']) ? floatval($_POST['interest_rate']) : (isset($interest) && is_numeric($interest) ? floatval($interest) : 0);
$months = isset($_POST['duration_months']) && is_numeric($_POST['duration_months']) && $_POST['duration_months'] > 0 ? intval($_POST['duration_months']) : (isset($months) && is_numeric($months) && $months > 0 ? intval($months) : 1);
$calculation_type = isset($_POST['calculation_type']) && in_array($_POST['calculation_type'], ['simple','compound']) ? $_POST['calculation_type'] : 'simple';
$penalty = isset($penalty) && is_numeric($penalty) ? floatval($penalty) : 0;

// Only calculate if we have valid data
if($amount > 0 && $months > 0) {
	// Use centralized calculation
	require_once __DIR__ . '/../includes/finance.php';
	require_once __DIR__ . '/../includes/helpers.php';
	try {
		$calc = calculateLoan($amount, $interest_rate, $months, $calculation_type);
		$monthly = $calc['monthly_installment'];
		$total_payable = $calc['total_payable'];
		$penalty_amount = $monthly * ($penalty/100);
	} catch(Exception $e) {
		$monthly = ($amount + ($amount * ($interest_rate/100))) / $months;
		$penalty_amount = $monthly * ($penalty/100);
		$total_payable = $monthly * $months;
	}
} else {
	$monthly = 0;
	$penalty_amount = 0;
	$total_payable = 0;
}

?>
<hr>
<table width="100%">
	<tr>
		<th class="text-center" width="33.33%">Total Payable Amount</th>
		<th class="text-center" width="33.33%">Monthly Payable Amount</th>
		<th class="text-center" width="33.33%">Penalty Amount</th>
	</tr>
	<tr>
		<td class="text-center"><small><?php echo formatKwacha($total_payable) ?></small></td>
		<td class="text-center"><small><?php echo formatKwacha($monthly) ?></small></td>
		<td class="text-center"><small><?php echo formatKwacha($penalty_amount) ?></small></td>
	</tr>
</table>
<hr>
<?php if($amount == 0 || $months == 0): ?>
<div class="alert alert-warning text-center">
	<small>Please select a loan plan to calculate payment details</small>
</div>
<?php endif; ?>