<?php
require_once __DIR__ . '/../config/constants.php';

/**
 * Finance helper functions
 */

// Default interest rate (18% for 1-month loans, annual rates for multi-month)
const DEFAULT_ANNUAL_INTEREST_RATE = 18.0;

/**
 * Calculate loan with interest applied to outstanding balance
 *
 * BUSINESS RULES:
 * - 1-month loans: ALWAYS 18% TOTAL interest (auto-applied)
 * - Multi-month loans: Interest rate MUST be set by administrator (annual rates)
 */
function calculateLoan(float $principal, float $annualInterestRate, int $months, string $calculationType = 'compound'): array {
    $isRateNotSet = false;

    // STRICT BUSINESS RULE: 1-month loans = 18% TOTAL (not annual)
    if ($months == 1) {
        $annualInterestRate = 18.0;
        $isRateNotSet = false; // Rate is correctly set by business rule
    }
    // Multi-month loans: Rate MUST be provided by admin
    elseif ($annualInterestRate == 0) {
        // For preview calculations only, use 18% but flag it
        $annualInterestRate = 18.0;
        $isRateNotSet = true; // Flag: Admin must set rate before approval
    }

    // Validate interest rate against allowed values
    $allowedRates = [10.0, 18.0, 25.0, 28.0, 30.0, 35.0, 40.0];
    if (!in_array($annualInterestRate, $allowedRates, true)) {
        throw new Exception("Invalid interest rate selected. Allowed rates: " . implode(', ', $allowedRates));
    }

    if (!in_array($calculationType, ['simple', 'compound'], true)) {
        throw new Exception("Invalid calculation type. Use 'simple' or 'compound'.");
    }

    // SPECIAL CASE: 1-month loans use the rate as TOTAL interest, not annual
    if ($months == 1) {
        // For 1-month loans: apply the full 18% as total interest
        $totalInterest = $principal * ($annualInterestRate / 100.0);
        $totalPayable = $principal + $totalInterest;
    } else {
        // For multi-month loans: treat as annual rate, convert to monthly
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
    }

    $monthlyInstallment = $months > 0 ? $totalPayable / $months : $totalPayable;

    // Calculate the effective monthly rate for display
    $displayMonthlyRate = ($months == 1)
        ? $annualInterestRate  // For 1-month: show as total rate
        : round(($annualInterestRate / 12.0), 2);  // For multi-month: show monthly from annual

    return [
        'principal' => round($principal, 2),
        'annual_interest_rate' => $annualInterestRate,
        'monthly_interest_rate' => $displayMonthlyRate,
        'calculation_type' => $calculationType,
        'months' => $months,
        'total_interest' => round($totalInterest, 2),
        'total_payable' => round($totalPayable, 2),
        'monthly_installment' => round($monthlyInstallment, 2),
        'currency' => 'K', // Zambian Kwacha
        'rate_not_set' => $isRateNotSet, // Flag: Admin must set rate for multi-month loans
        'is_one_month' => ($months == 1), // Flag: Auto-applied 18% TOTAL rate
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
