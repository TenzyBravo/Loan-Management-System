<?php 
// Extract POST data safely
extract($_POST);

// Validate and set defaults for required variables
$amount = isset($amount) && is_numeric($amount) ? floatval($amount) : 0;
$interest = isset($interest) && is_numeric($interest) ? floatval($interest) : 0;
$months = isset($months) && is_numeric($months) && $months > 0 ? intval($months) : 1;
$penalty = isset($penalty) && is_numeric($penalty) ? floatval($penalty) : 0;

// Only calculate if we have valid data
if($amount > 0 && $months > 0) {
	$monthly = ($amount + ($amount * ($interest/100))) / $months;
	$penalty_amount = $monthly * ($penalty/100);
} else {
	$monthly = 0;
	$penalty_amount = 0;
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
		<td class="text-center"><small><?php echo number_format($monthly * $months, 2) ?></small></td>
		<td class="text-center"><small><?php echo number_format($monthly, 2) ?></small></td>
		<td class="text-center"><small><?php echo number_format($penalty_amount, 2) ?></small></td>
	</tr>
</table>
<hr>
<?php if($amount == 0 || $months == 0): ?>
<div class="alert alert-warning text-center">
	<small>Please select a loan plan to calculate payment details</small>
</div>
<?php endif; ?>