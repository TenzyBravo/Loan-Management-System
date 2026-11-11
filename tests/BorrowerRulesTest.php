<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/borrower_rules.php';

/**
 * One account per person, one loan at a time.
 */
final class BorrowerRulesTest extends TestCase
{
    private array $existing = [
        ['id' => 1, 'email' => 'Jane@Example.com', 'contact_no' => '+260 97 123 4567', 'tax_id' => '123456/78/1'],
        ['id' => 2, 'email' => 'john@example.com', 'contact_no' => '0961112223', 'tax_id' => ''],
    ];

    public function testNrcMatchesIgnoringFormatting(): void
    {
        $this->assertSame('nrc', matchDuplicateBorrower($this->existing, 'new@x.com', '0770000000', '123456 78 1'));
    }

    public function testPhoneMatchesAcrossLocalAndInternationalFormats(): void
    {
        $this->assertSame('phone', matchDuplicateBorrower($this->existing, 'new@x.com', '0971234567', '999999/99/9'));
        $this->assertSame('phone', matchDuplicateBorrower($this->existing, 'new@x.com', '+260961112223', ''));
    }

    public function testEmailMatchesIgnoringCase(): void
    {
        $this->assertSame('email', matchDuplicateBorrower($this->existing, ' JANE@example.com ', '0770000000', ''));
    }

    public function testNewPersonIsNotADuplicate(): void
    {
        $this->assertNull(matchDuplicateBorrower($this->existing, 'new@x.com', '0770000000', '555555/55/5'));
    }

    public function testBlankNrcNeverMatchesOtherBlankNrc(): void
    {
        $this->assertNull(matchDuplicateBorrower($this->existing, 'new@x.com', '0770000000', ''));
    }

    public function testEditingABorrowerDoesNotMatchItself(): void
    {
        $this->assertNull(matchDuplicateBorrower($this->existing, 'jane@example.com', '0971234567', '123456/78/1', 1));
        // ...but still can't take another borrower's details
        $this->assertSame('email', matchDuplicateBorrower($this->existing, 'john@example.com', '0971234567', '123456/78/1', 1));
    }

    public function testOpenLoanStatusesArePendingApprovedReleased(): void
    {
        $this->assertSame([0, 1, 2], OPEN_LOAN_STATUSES);
    }

    public function testOpenLoanMessageNamesTheLoan(): void
    {
        $msg = openLoanMessage(['ref_no' => 'REF123', 'status' => 2], true);
        $this->assertStringContainsString('REF123', $msg);
        $this->assertStringContainsString('one loan at a time', $msg);
    }
}
