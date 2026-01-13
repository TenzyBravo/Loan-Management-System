<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';

class HelpersTest extends TestCase {
    public function testFormatCurrency() {
        $amount = 1234.56;
        $expected = AppConfig::CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
        $this->assertSame($expected, formatCurrency($amount));
    }
}
