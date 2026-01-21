<?php
require_once __DIR__ . '/../config/constants.php';

/**
 * Finance helper functions
 */

// Default interest rate
const DEFAULT_INTEREST_RATE = 18.0;

/**
 * Calculate loan with TOTAL interest (not annual)
 *
 * BUSINESS RULES:
 * - 1-month loans: ALWAYS 18% TOTAL interest (auto-applied)
 * - Multi-month loans: Interest rate MUST be set by administrator (TOTAL interest, not annual)
 * - All rates are TOTAL interest applied to principal, regardless of duration
 */
function calculateLoan(float $principal, float $interestRate, int $months, string $calculationType = 'simple'): array {
    $isRateNotSet = false;

    // STRICT BUSINESS RULE: 1-month loans = 18% TOTAL (auto-applied)
    if ($months == 1) {
        $interestRate = 18.0;
        $isRateNotSet = false; // Rate is correctly set by business rule
    }
    // Multi-month loans: Rate MUST be provided by admin
    elseif ($interestRate == 0) {
        // For preview calculations only, use 18% but flag it
        $interestRate = 18.0;
        $isRateNotSet = true; // Flag: Admin must set rate before approval
    }

    // Validate interest rate against allowed values
    $allowedRates = [10.0, 18.0, 25.0, 28.0, 30.0, 35.0, 40.0];
    if (!in_array($interestRate, $allowedRates, true)) {
        throw new Exception("Invalid interest rate selected. Allowed rates: " . implode(', ', $allowedRates));
    }

    // ALL loans use TOTAL interest (not annual)
    // Interest = Principal × Rate%
    $totalInterest = $principal * ($interestRate / 100.0);
    $totalPayable = $principal + $totalInterest;
    $monthlyInstallment = $months > 0 ? $totalPayable / $months : $totalPayable;

    return [
        'principal' => round($principal, 2),
        'interest_rate' => $interestRate,
        'calculation_type' => 'simple',
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
