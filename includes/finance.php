<?php
require_once __DIR__ . '/../config/constants.php';

/**
 * Finance helper functions
 */
function calculateLoan(float $principal, float $interestRate, int $months, string $calculationType): array {
    if (!in_array($interestRate, AppConfig::ALLOWED_INTEREST_RATES, true)) {
        throw new Exception("Invalid interest rate selected.");
    }
    if (!in_array($calculationType, ['simple','compound'], true)) {
        throw new Exception("Invalid calculation type.");
    }

    $rate = $interestRate / 100.0;

    if ($calculationType === 'simple') {
        // simple interest per month (rate applied per month)
        $totalInterest = $principal * $rate * $months;
        $totalPayable = $principal + $totalInterest;
    } else {
        // compound monthly for n months (compounded monthly)
        $totalPayable = $principal * pow(1 + $rate, $months);
        $totalInterest = $totalPayable - $principal;
    }

    $monthlyInstallment = $months > 0 ? $totalPayable / $months : $totalPayable;

    return [
        'principal' => round($principal, 2),
        'interest_rate' => $interestRate,
        'calculation_type' => $calculationType,
        'months' => $months,
        'total_interest' => round($totalInterest, 2),
        'total_payable' => round($totalPayable, 2),
        'monthly_installment' => round($monthlyInstallment, 2),
        'currency' => AppConfig::CURRENCY_SYMBOL,
    ];
}

function applyPenalty(float $outstandingBalance): float {
    $penalty = $outstandingBalance * 0.05; // 5% penalty
    return round($outstandingBalance + $penalty, 2);
}
