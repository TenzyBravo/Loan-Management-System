<?php
require_once __DIR__ . '/includes/finance.php';

// Extract POST data safely
extract($_POST);

// Validate and set defaults for required variables
$amount = isset($amount) && is_numeric($amount) ? floatval($amount) : 0;
$interest_rate = isset($interest_rate) && is_numeric($interest_rate) ? floatval($interest_rate) : 18.0;
$duration_months = isset($duration_months) && is_numeric($duration_months) && $duration_months > 0 ? intval($duration_months) : 1;
$calculation_type = isset($calculation_type) && in_array($calculation_type, ['simple', 'compound']) ? $calculation_type : 'compound';

// Track if we have valid data and calculation succeeded
$calculationSuccess = false;
$calculationError = null;
$rateNotSet = false;

// Only calculate if we have valid data
if($amount > 0 && $duration_months > 0) {
    try {
        $loanDetails = calculateLoan($amount, $interest_rate, $duration_months, $calculation_type);
        $totalPayable = $loanDetails['total_payable'];
        $monthlyInstallment = $loanDetails['monthly_installment'];
        $totalInterest = $loanDetails['total_interest'];
        $currency = $loanDetails['currency'];
        $rateNotSet = $loanDetails['rate_not_set'] ?? false;
        $isOneMonth = $loanDetails['is_one_month'] ?? false;
        $calculationSuccess = true;
    } catch (Exception $e) {
        $totalPayable = 0;
        $monthlyInstallment = 0;
        $totalInterest = 0;
        $currency = 'K';
        $calculationError = $e->getMessage();
    }
} else {
    $totalPayable = 0;
    $monthlyInstallment = 0;
    $totalInterest = 0;
    $currency = 'K';
}

?>

<!-- Info: 1-Month Loan Auto-Rate -->
<?php if($isOneMonth): ?>
<div class="alert alert-success" style="background: #d1fae5; border-left: 4px solid #10b981; padding: 12px 15px; border-radius: 4px; margin-bottom: 15px;">
    <i class="fa fa-check-circle"></i>
    <strong>Business Rule Applied:</strong> 1-month loans automatically receive <strong>18% interest rate</strong>.
    <br><small>This is a fixed rate for all single-month loans.</small>
</div>
<?php endif; ?>

<!-- Warning: Multi-Month Loan - Rate Not Set -->
<?php if($rateNotSet && !$isOneMonth): ?>
<div class="alert alert-warning" style="background: #fff3cd; border-left: 4px solid #f59e0b; padding: 12px 15px; border-radius: 4px; margin-bottom: 15px;">
    <i class="fa fa-exclamation-triangle"></i>
    <strong>Warning:</strong> Interest rate must be set by an administrator for loans longer than 1 month.
    <br><small>Using 18% for preview only. Please select the appropriate interest rate based on risk assessment before saving.</small>
</div>
<?php endif; ?>

<!-- Error: Calculation Failed -->
<?php if($calculationError): ?>
<div class="alert alert-danger" style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 12px 15px; border-radius: 4px; margin-bottom: 15px;">
    <i class="fa fa-exclamation-circle"></i>
    <strong>Calculation Error:</strong> <?php echo htmlspecialchars($calculationError) ?>
</div>
<?php endif; ?>

<!-- Warning: Missing Data -->
<?php if($amount == 0 || $duration_months == 0): ?>
<div class="alert alert-info" style="background: #dbeafe; border-left: 4px solid #2563eb; padding: 12px 15px; border-radius: 4px; margin-bottom: 15px;">
    <i class="fa fa-info-circle"></i>
    Please enter <strong>loan amount</strong> and <strong>duration</strong> to calculate payment details.
</div>
<?php endif; ?>

<!-- Calculation Results -->
<?php if($calculationSuccess): ?>
<div class="form-section calculation-result">
    <div class="form-section-title">
        <i class="fa fa-calculator"></i> Loan Calculation Summary
    </div>

    <!-- Main Metrics -->
    <div class="row">
        <div class="col-md-3 col-6 mb-3">
            <div class="result-card">
                <div class="result-label">Principal Amount</div>
                <div class="result-value"><?php echo $currency . ' ' . number_format($amount, 2) ?></div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="result-card">
                <div class="result-label">Interest Rate</div>
                <div class="result-value"><?php echo $interest_rate ?>%</div>
                <small class="text-muted"><?php echo $calculation_type == 'compound' ? 'Compounded' : 'Simple' ?></small>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="result-card">
                <div class="result-label">Total Interest</div>
                <div class="result-value text-warning"><?php echo $currency . ' ' . number_format($totalInterest, 2) ?></div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="result-card result-card-primary">
                <div class="result-label">Total Payable</div>
                <div class="result-value"><?php echo $currency . ' ' . number_format($totalPayable, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Payment Details -->
    <div class="row mt-3">
        <div class="col-md-6 col-12 mb-3">
            <div class="result-card">
                <div class="result-label">Duration</div>
                <div class="result-value"><?php echo $duration_months ?> months</div>
            </div>
        </div>

        <div class="col-md-6 col-12 mb-3">
            <div class="result-card result-card-highlight">
                <div class="result-label">Monthly Installment</div>
                <div class="result-value"><?php echo $currency . ' ' . number_format($monthlyInstallment, 2) ?></div>
                <small class="text-muted">Due every month for <?php echo $duration_months ?> months</small>
            </div>
        </div>
    </div>
</div>

<style>
.result-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    transition: all 0.2s ease;
    height: 100%;
}

.result-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}

.result-card-primary {
    background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
    border: 2px solid #2563eb;
}

.result-card-highlight {
    background: linear-gradient(135deg, #fef3c7 0%, #fef9e7 100%);
    border: 2px solid #f59e0b;
}

.result-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.result-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.result-card-primary .result-value {
    color: #2563eb;
}

.result-card-highlight .result-value {
    color: #f59e0b;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .result-value {
        font-size: 1.25rem;
    }

    .result-label {
        font-size: 0.8rem;
    }
}
</style>
<?php endif; ?>
