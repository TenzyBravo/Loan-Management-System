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
 * - 1-month loans: ALWAYS AppConfig::ONE_MONTH_RATE TOTAL interest (same as 4 weeks, auto-applied)
 * - Multi-month loans: Interest rate MUST be set by administrator (TOTAL interest, not annual)
 * - All rates are TOTAL interest applied to principal, regardless of duration
 */
function calculateLoan(float $principal, float $interestRate, int $months, string $calculationType = 'simple'): array {
    $isRateNotSet = false;

    // STRICT BUSINESS RULE: 1-month loans = the 4-week rate (auto-applied)
    if ($months == 1) {
        $interestRate = (float) AppConfig::ONE_MONTH_RATE;
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
        'is_one_month' => ($months == 1), // Flag: Auto-applied fixed 1-month rate
        'term_unit' => 'month',
        'installment_count' => $months,
        'fixed_rate' => ($months == 1),
    ];
}

/* ------------------------------------------------------------------
 * Weekly loans (effective 1 November 2026)
 *
 * BUSINESS RULES:
 * - Terms of 1, 2, 3 or 4 weeks only
 * - Rate is fixed by length (AppConfig::WEEKLY_RATES) - TOTAL interest on principal
 * - Repaid in ONE payment due at the end of the term
 * ------------------------------------------------------------------ */

/**
 * Normalise a term unit coming from user input. Anything other than 'week' is 'month'
 * so that every existing (pre-weekly) loan keeps its monthly behaviour.
 */
function normalizeTermUnit($unit): string {
    return (is_string($unit) && strtolower(trim($unit)) === 'week') ? 'week' : 'month';
}

/**
 * Fixed TOTAL interest rate for a weekly loan.
 * @throws Exception if the number of weeks is not offered
 */
function weeklyRateFor(int $weeks): float {
    if (!isset(AppConfig::WEEKLY_RATES[$weeks])) {
        throw new Exception('Weekly loans are available for 1 to ' . max(array_keys(AppConfig::WEEKLY_RATES)) . ' weeks only.');
    }
    return (float) AppConfig::WEEKLY_RATES[$weeks];
}

/**
 * Calculate a weekly loan. Returns the same keys as calculateLoan() so callers can treat
 * both the same way; 'monthly_installment' holds the single repayment amount.
 */
function calculateWeeklyLoan(float $principal, int $weeks): array {
    $rate = weeklyRateFor($weeks);
    $totalInterest = round($principal * ($rate / 100.0), 2);
    $totalPayable = round($principal + $totalInterest, 2);

    return [
        'principal' => round($principal, 2),
        'interest_rate' => $rate,
        'calculation_type' => 'simple',
        'months' => $weeks, // count of term units (kept for compatibility with calculateLoan callers)
        'weeks' => $weeks,
        'total_interest' => $totalInterest,
        'total_payable' => $totalPayable,
        'monthly_installment' => $totalPayable, // single repayment at end of term
        'currency' => 'K',
        'rate_not_set' => false,
        'is_one_month' => false,
        'term_unit' => 'week',
        'installment_count' => 1,
        'fixed_rate' => true,
    ];
}

/**
 * Calculate any loan from its term unit. For weekly loans the supplied rate is ignored
 * (the rate is fixed by length); for monthly loans the existing calculateLoan() rules apply.
 */
function calculateLoanForTerm(float $principal, float $interestRate, int $count, string $unit): array {
    if (normalizeTermUnit($unit) === 'week') {
        return calculateWeeklyLoan($principal, $count);
    }
    return calculateLoan($principal, $interestRate, $count, 'simple');
}

/**
 * Final due date of a loan (Y-m-d) from its start date.
 */
function loanDueDate(string $startDate, int $count, string $unit): string {
    $count = max(1, $count);
    $interval = normalizeTermUnit($unit) === 'week' ? ($count * 7) . ' days' : $count . ' months';
    return date('Y-m-d', strtotime($startDate . ' +' . $interval));
}

/**
 * Repayment due dates for a loan: one date for weekly loans, one per month for monthly loans.
 * @return string[] Y-m-d dates in order
 */
function loanScheduleDates(string $startDate, int $count, string $unit): array {
    $count = max(1, $count);
    if (normalizeTermUnit($unit) === 'week') {
        return [loanDueDate($startDate, $count, 'week')];
    }
    $dates = [];
    for ($i = 1; $i <= $count; $i++) {
        $dates[] = date('Y-m-d', strtotime($startDate . ' +' . $i . ' months'));
    }
    return $dates;
}

/**
 * Human-readable term, e.g. "1 week", "3 weeks", "1 month", "6 months".
 */
function formatLoanTerm($count, $unit): string {
    $count = max(1, (int) $count);
    $word = normalizeTermUnit($unit) === 'week' ? 'week' : 'month';
    return $count . ' ' . $word . ($count === 1 ? '' : 's');
}

/**
 * Label for the per-installment amount: weekly loans have a single repayment.
 */
function installmentLabel($unit): string {
    return normalizeTermUnit($unit) === 'week' ? 'Repayment Due' : 'Monthly Installment';
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
