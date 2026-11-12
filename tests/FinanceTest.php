<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/finance.php';

/**
 * Loan rate rules.
 * Weekly rates must match the public notice "Loan Upward Percentage Adjustment",
 * effective 1 November 2026: 1 week 15%, 2 weeks 20%, 3 weeks 25%, 4 weeks 30%.
 */
final class FinanceTest extends TestCase
{
    public function testWeeklyRateTableMatchesPublishedNotice(): void
    {
        $this->assertSame([1 => 15.0, 2 => 20.0, 3 => 25.0, 4 => 30.0], AppConfig::WEEKLY_RATES);
    }

    /**
     * @dataProvider weeklyLoans
     */
    public function testWeeklyLoanTotals(int $weeks, float $rate, float $interest, float $total): void
    {
        $calc = calculateWeeklyLoan(1000.0, $weeks);

        $this->assertSame($rate, $calc['interest_rate']);
        $this->assertSame($interest, $calc['total_interest']);
        $this->assertSame($total, $calc['total_payable']);
        // One repayment at the end of the term
        $this->assertSame($total, $calc['monthly_installment']);
        $this->assertSame(1, $calc['installment_count']);
        $this->assertSame('week', $calc['term_unit']);
        $this->assertTrue($calc['fixed_rate']);
        $this->assertFalse($calc['rate_not_set']);
    }

    public function weeklyLoans(): array
    {
        return [
            '1 week'  => [1, 15.0, 150.0, 1150.0],
            '2 weeks' => [2, 20.0, 200.0, 1200.0],
            '3 weeks' => [3, 25.0, 250.0, 1250.0],
            '4 weeks' => [4, 30.0, 300.0, 1300.0],
        ];
    }

    public function testWeeklyLoanRoundsToNgwee(): void
    {
        $calc = calculateWeeklyLoan(1234.56, 2); // 20% of 1234.56 = 246.912
        $this->assertSame(246.91, $calc['total_interest']);
        $this->assertSame(1481.47, $calc['total_payable']);
    }

    /**
     * @dataProvider unsupportedWeeks
     */
    public function testUnsupportedWeekCountsAreRejected(int $weeks): void
    {
        $this->expectException(Exception::class);
        calculateWeeklyLoan(1000.0, $weeks);
    }

    public function unsupportedWeeks(): array
    {
        return ['0 weeks' => [0], '5 weeks' => [5], '-1 weeks' => [-1], '52 weeks' => [52]];
    }

    public function testWeeklyRateCannotBeOverriddenByStaff(): void
    {
        // A rate posted by staff (40%) must be ignored for weekly loans
        $calc = calculateLoanForTerm(1000.0, 40.0, 1, 'week');
        $this->assertSame(15.0, $calc['interest_rate']);
        $this->assertSame(1150.0, $calc['total_payable']);
    }

    public function testOneMonthLoanUsesTheFourWeekRate(): void
    {
        // 4 weeks is a month: 1-month loans are fixed at 30%, whatever rate is passed in
        $calc = calculateLoanForTerm(1000.0, 18.0, 1, 'month');
        $this->assertSame(30.0, $calc['interest_rate']);
        $this->assertSame(1300.0, $calc['total_payable']);
        $this->assertSame('month', $calc['term_unit']);
    }

    public function testOneMonthRateMatchesFourWeekRate(): void
    {
        $this->assertSame(AppConfig::WEEKLY_RATES[4], AppConfig::ONE_MONTH_RATE);
    }

    public function testMultiMonthLoanUsesStaffRateAsTotalInterest(): void
    {
        $calc = calculateLoanForTerm(1000.0, 25.0, 3, 'month');
        $this->assertSame(25.0, $calc['interest_rate']);
        $this->assertSame(1250.0, $calc['total_payable']);
        $this->assertSame(416.67, $calc['monthly_installment']);
        $this->assertSame(3, $calc['installment_count']);
    }

    public function testMultiMonthLoanWithoutRateIsFlaggedAsNotSet(): void
    {
        $calc = calculateLoanForTerm(1000.0, 0.0, 3, 'month');
        $this->assertTrue($calc['rate_not_set']);
    }

    public function testMonthlyLoanRejectsRatesOutsideTheAllowedList(): void
    {
        // 15% is a weekly rate, not an allowed monthly rate
        $this->expectException(Exception::class);
        calculateLoanForTerm(1000.0, 15.0, 3, 'month');
    }

    public function testUnknownTermUnitFallsBackToMonthly(): void
    {
        $this->assertSame('month', normalizeTermUnit(null));
        $this->assertSame('month', normalizeTermUnit('day'));
        $this->assertSame('month', normalizeTermUnit(''));
        $this->assertSame('week', normalizeTermUnit('week'));
        $this->assertSame('week', normalizeTermUnit(' WEEK '));
    }

    public function testDueDates(): void
    {
        $this->assertSame('2026-11-08', loanDueDate('2026-11-01', 1, 'week'));
        $this->assertSame('2026-11-29', loanDueDate('2026-11-01', 4, 'week'));
        $this->assertSame('2026-12-01', loanDueDate('2026-11-01', 1, 'month'));
        $this->assertSame('2027-02-01', loanDueDate('2026-11-01', 3, 'month'));
        // Release time of day must not move the date
        $this->assertSame('2026-11-15', loanDueDate('2026-11-01 16:45:00', 2, 'week'));
    }

    public function testScheduleHasOneDateForWeeklyLoans(): void
    {
        $this->assertSame(['2026-11-22'], loanScheduleDates('2026-11-01', 3, 'week'));
    }

    public function testScheduleHasOneDatePerMonthForMonthlyLoans(): void
    {
        $this->assertSame(
            ['2026-12-01', '2027-01-01', '2027-02-01'],
            loanScheduleDates('2026-11-01', 3, 'month')
        );
    }

    public function testTermLabels(): void
    {
        $this->assertSame('1 week', formatLoanTerm(1, 'week'));
        $this->assertSame('3 weeks', formatLoanTerm(3, 'week'));
        $this->assertSame('1 month', formatLoanTerm(1, 'month'));
        $this->assertSame('6 months', formatLoanTerm('6', null));
        $this->assertSame('Repayment Due', installmentLabel('week'));
        $this->assertSame('Monthly Installment', installmentLabel('month'));
    }
}
