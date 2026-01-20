<?php
require_once __DIR__ . '/includes/finance.php';

// Extract POST data safely
extract($_POST);

// Validate and set defaults for required variables
$amount = isset($amount) && is_numeric($amount) ? floatval($amount) : 0;
$interest_rate = isset($interest_rate) && is_numeric($interest_rate) ? floatval($interest_rate) : 18.0; // Default to 18%
$duration_months = isset($duration_months) && is_numeric($duration_months) && $duration_months > 0 ? intval($duration_months) : 1;
$calculation_type = isset($calculation_type) && in_array($calculation_type, ['simple', 'compound']) ? $calculation_type : 'compound';

// Only calculate if we have valid data
if($amount > 0 && $duration_months > 0) {
    try {
        $loanDetails = calculateLoan($amount, $interest_rate, $duration_months, $calculation_type);
        $totalPayable = $loanDetails['total_payable'];
        $monthlyInstallment = $loanDetails['monthly_installment'];
        $totalInterest = $loanDetails['total_interest'];
        $currency = $loanDetails['currency'];
    } catch (Exception $e) {
        $totalPayable = 0;
        $monthlyInstallment = 0;
        $totalInterest = 0;
        $currency = 'K';
    }
} else {
    $totalPayable = 0;
    $monthlyInstallment = 0;
    $totalInterest = 0;
    $currency = 'K';
}

?>
<hr>
<div class="row">
    <div class="col-md-12">
        <h5>Loan Calculation Summary</h5>
    </div>
</div>
<table width="100%" class="table table-bordered">
    <tr>
        <th class="text-center" width="25%">Principal Amount</th>
        <th class="text-center" width="25%">Interest Rate</th>
        <th class="text-center" width="25%">Total Interest</th>
        <th class="text-center" width="25%">Total Payable</th>
    </tr>
    <tr>
        <td class="text-center"><small><?php echo $currency . ' ' . number_format($amount, 2) ?></small></td>
        <td class="text-center"><small><?php echo $interest_rate . '%' . ($calculation_type == 'compound' ? ' (Compounded)' : ' (Simple)') ?></small></td>
        <td class="text-center"><small><?php echo $currency . ' ' . number_format($totalInterest, 2) ?></small></td>
        <td class="text-center"><small><?php echo $currency . ' ' . number_format($totalPayable, 2) ?></small></td>
    </tr>
</table>
<table width="100%" class="table table-bordered mt-2">
    <tr>
        <th class="text-center" width="50%">Duration (Months)</th>
        <th class="text-center" width="50%">Monthly Installment</th>
    </tr>
    <tr>
        <td class="text-center"><small><?php echo $duration_months . ' months' ?></small></td>
        <td class="text-center"><small><?php echo $currency . ' ' . number_format($monthlyInstallment, 2) ?></small></td>
    </tr>
</table>
<hr>
<?php if($amount == 0 || $duration_months == 0): ?>
<div class="alert alert-warning text-center">
    <small>Please enter loan amount and duration to calculate payment details</small>
</div>
<?php endif; ?>