<?php
/**
 * Borrower rules
 *
 *  - One account per person: no two borrowers may share an NRC number, phone number or email.
 *  - One loan at a time: a borrower with a pending, approved or released loan can't take another.
 *
 * Comparisons ignore formatting, so "123456/78/1" matches "123456 78 1" and
 * "+260 97 123 4567" matches "0971234567".
 */

/** Loan statuses that block a new loan: 0 = pending, 1 = approved, 2 = released. */
const OPEN_LOAN_STATUSES = [0, 1, 2];

/**
 * NRC in a comparable form: letters and digits only, upper case.
 */
function normalizeNrc(?string $nrc): string {
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $nrc));
}

/**
 * Phone number in a comparable form: the last 9 digits (Zambian number without 0 / +260).
 * Numbers shorter than 9 digits are returned as their digits.
 */
function normalizePhone(?string $phone): string {
    $digits = preg_replace('/\D/', '', (string) $phone);
    return strlen($digits) > 9 ? substr($digits, -9) : $digits;
}

/**
 * Email in a comparable form: trimmed, lower case.
 */
function normalizeEmail(?string $email): string {
    return strtolower(trim((string) $email));
}

/**
 * Which identifying detail of the new person matches an existing borrower.
 *
 * @param array $existing Rows with keys id, email, contact_no, tax_id
 * @param int   $excludeId Borrower being edited (0 for a new borrower)
 * @return string|null 'nrc', 'phone' or 'email', or null when there's no match
 */
function matchDuplicateBorrower(array $existing, string $email, string $phone, string $nrc, int $excludeId = 0): ?string {
    $nrc = normalizeNrc($nrc);
    $phone = normalizePhone($phone);
    $email = normalizeEmail($email);

    foreach ($existing as $row) {
        if ((int) $row['id'] === $excludeId && $excludeId !== 0) {
            continue;
        }
        if ($nrc !== '' && normalizeNrc($row['tax_id'] ?? '') === $nrc) {
            return 'nrc';
        }
        if (strlen($phone) >= 9 && normalizePhone($row['contact_no'] ?? '') === $phone) {
            return 'phone';
        }
        if ($email !== '' && normalizeEmail($row['email'] ?? '') === $email) {
            return 'email';
        }
    }
    return null;
}

/**
 * Database lookup for matchDuplicateBorrower().
 */
function findDuplicateBorrower(mysqli $conn, string $email, string $phone, string $nrc, int $excludeId = 0): ?string {
    $result = $conn->query("SELECT id, email, contact_no, tax_id FROM borrowers");
    $rows = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $rows[] = $row;
    }
    return matchDuplicateBorrower($rows, $email, $phone, $nrc, $excludeId);
}

/**
 * Message shown when a duplicate is found.
 */
function duplicateBorrowerMessage(string $field, bool $forCustomer): string {
    $labels = ['nrc' => 'NRC number', 'phone' => 'phone number', 'email' => 'email address'];
    $label = $labels[$field] ?? 'details';
    return $forCustomer
        ? "An account with this $label already exists. Each person may only have one account - please log in instead, or contact us if you can't access it."
        : "Another borrower already has this $label. Each person may only have one account.";
}

/**
 * The borrower's open loan (pending, approved or released), or null if they have none.
 *
 * @return array|null Keys id, ref_no, status
 */
function findOpenLoan(mysqli $conn, int $borrowerId, int $excludeLoanId = 0): ?array {
    $stmt = $conn->prepare("SELECT id, ref_no, status FROM loan_list
                            WHERE borrower_id = ? AND status IN (0, 1, 2) AND id <> ?
                            ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ii', $borrowerId, $excludeLoanId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Message shown when a borrower already has an open loan.
 */
function openLoanMessage(array $loan, bool $forCustomer): string {
    $states = [0 => 'is waiting for review', 1 => 'has been approved', 2 => 'is still being repaid'];
    $state = $states[(int) $loan['status']] ?? 'is still open';
    $ref = htmlspecialchars((string) ($loan['ref_no'] ?? ''), ENT_QUOTES);
    return $forCustomer
        ? "You can only have one loan at a time. Your loan $ref $state. You can apply again once it is fully repaid."
        : "This borrower already has a loan ($ref) that $state. Only one loan at a time is allowed.";
}
