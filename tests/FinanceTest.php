<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/finance.php';

class FinanceTest extends TestCase {
    public function testCalculateLoanSimple() {
        $r = calculateLoan(1000.0, 10, 1, 'simple');
        $this->assertEquals(100.00, $r['total_interest']);
        $this->assertEquals(1100.00, $r['total_payable']);
        $this->assertEquals(1100.00, $r['monthly_installment']);
    }

    public function testCalculateLoanCompound() {
        $r = calculateLoan(1000.0, 10, 2, 'compound');
        $expectedTotal = round(1000.0 * pow(1 + 0.10, 2), 2);
        $this->assertEquals($expectedTotal, $r['total_payable']);
    }

    public function testInvalidInterestRateThrows() {
        $this->expectException(Exception::class);
        calculateLoan(1000.0, 99, 1, 'simple');
    }

    public function testInvalidCalculationTypeThrows() {
        $this->expectException(Exception::class);
        calculateLoan(1000.0, 10, 1, 'weird');
    }

    public function testApplyPenalty() {
        $this->assertEquals(105.00, applyPenalty(100.00));
    }
}
