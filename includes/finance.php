<?php
require_once __DIR__ . '/../config/constants.php';

/**
 * Finance helper functions
 */

// Default interest rate (18% annually, approximately 1.5% monthly)
const DEFAULT_ANNUAL_INTEREST_RATE = 18.0;

/**
 * Calculate loan with interest applied to outstanding balance
 */
function calculateLoan(float $principal, float $annualInterestRate, int $months, string $calculationType = 'compound'): array {
    // Handle "Not Set" case (0) - use default for calculations but flag it
    $isRateNotSet = false;
    if ($annualInterestRate == 0) {
        $annualInterestRate = DEFAULT_ANNUAL_INTEREST_RATE; // Use 18% as default
        $isRateNotSet = true;
    }

    // Validate interest rate against allowed values
    $allowedRates = [10.0, 18.0, 25.0, 28.0, 30.0, 35.0, 40.0];
    if (!in_array($annualInterestRate, $allowedRates, true)) {
        throw new Exception("Invalid interest rate selected. Allowed rates: " . implode(', ', $allowedRates));
    }

    if (!in_array($calculationType, ['simple', 'compound'], true)) {
        throw new Exception("Invalid calculation type. Use 'simple' or 'compound'.");
    }

    // Convert annual interest rate to monthly
    $monthlyRate = $annualInterestRate / 12.0 / 100.0;

    if ($calculationType === 'simple') {
        // Simple interest on principal for the entire period
        $totalInterest = $principal * $monthlyRate * $months;
        $totalPayable = $principal + $totalInterest;
    } else {
        // Compound interest on outstanding balance
        $totalPayable = $principal * pow(1 + $monthlyRate, $months);
        $totalInterest = $totalPayable - $principal;
    }

    $monthlyInstallment = $months > 0 ? $totalPayable / $months : $totalPayable;

    return [
        'principal' => round($principal, 2),
        'annual_interest_rate' => $annualInterestRate,
        'monthly_interest_rate' => round($monthlyRate * 100, 2),
        'calculation_type' => $calculationType,
        'months' => $months,
        'total_interest' => round($totalInterest, 2),
        'total_payable' => round($totalPayable, 2),
        'monthly_installment' => round($monthlyInstallment, 2),
        'currency' => 'K', // Zambian Kwacha
        'rate_not_set' => $isRateNotSet, // Flag if rate was defaulted
    ];
}

/**
 * Apply penalty to outstanding balance
 * Penalty is applied for late payments or defaults
 */
function applyPenalty(float $outstandingBalance, string $penaltyReason = 'late_payment'): float {
    $penaltyRate = 0.05; // 5% penalty
    $penaltyAmount = $outstandingBalance * $penaltyRate;
    $totalWithPenalty = $outstandingBalance + $penaltyAmount;

    return round($totalWithPenalty, 2);
}

/**
 * Calculate remaining balance with interest applied to outstanding amount
 */
function calculateOutstandingBalance(float $initialPrincipal, float $annualInterestRate, int $monthsElapsed, int $paymentsMade, float $monthlyPayment): array {
    $monthlyRate = $annualInterestRate / 12.0 / 100.0;
    $currentBalance = $initialPrincipal;

    // Calculate balance after each month, applying interest to outstanding balance
    for ($month = 1; $month <= $monthsElapsed; $month++) {
        // Apply monthly interest to current balance
        $currentBalance += $currentBalance * $monthlyRate;

        // Deduct payment if made
        if ($month <= $paymentsMade) {
            $currentBalance -= $monthlyPayment;
            if ($currentBalance < 0) {
                $currentBalance = 0;
            }
        }
    }

    return [
        'outstanding_balance' => round($currentBalance, 2),
        'currency' => 'K'
    ];
}

/**
 * Calculate amortization schedule
 */
function calculateAmortizationSchedule(float $principal, float $annualInterestRate, int $months): array {
    $monthlyRate = $annualInterestRate / 12.0 / 100.0;

    // Calculate fixed monthly payment using standard formula
    if ($monthlyRate > 0) {
        $monthlyPayment = $principal * $monthlyRate * pow(1 + $monthlyRate, $months) / (pow(1 + $monthlyRate, $months) - 1);
    } else {
        $monthlyPayment = $principal / $months; // Handle 0% interest case
    }

    $schedule = [];
    $remainingBalance = $principal;

    for ($month = 1; $month <= $months; $month++) {
        $interestPayment = $remainingBalance * $monthlyRate;
        $principalPayment = min($monthlyPayment - $interestPayment, $remainingBalance); // Last payment may be smaller
        $remainingBalance -= $principalPayment;

        if ($remainingBalance < 0.01) {
            $remainingBalance = 0;
        }

        $schedule[] = [
            'month' => $month,
            'payment' => round($monthlyPayment, 2),
            'principal' => round($principalPayment, 2),
            'interest' => round($interestPayment, 2),
            'balance' => round($remainingBalance, 2)
        ];

        if ($remainingBalance == 0) {
            break; // Loan paid off early
        }
    }

    return [
        'schedule' => $schedule,
        'monthly_payment' => round($monthlyPayment, 2),
        'total_payment' => round($monthlyPayment * count($schedule), 2),
        'total_interest' => round($monthlyPayment * count($schedule) - $principal, 2),
        'currency' => 'K'
    ];
}
