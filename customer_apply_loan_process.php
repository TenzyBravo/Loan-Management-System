<?php
session_start();
include('db_connect.php');
include('includes/notifications.php');
require_once __DIR__ . '/includes/finance.php';

if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: customer_apply_loan.php');
    exit;
}

/**
 * Send the customer back to the form with an error message.
 */
function apply_fail(string $message): void {
    $_SESSION['error_msg'] = $message;
    header('Location: customer_apply_loan.php');
    exit;
}

$customer_id  = (int) $_SESSION['customer_id'];
$loan_type_id = (int) ($_POST['loan_type_id'] ?? 0);
$amount       = (float) ($_POST['amount'] ?? 0);
$purpose      = trim($_POST['purpose'] ?? '');
$collateral_description = trim($_POST['collateral_description'] ?? '');
$collateral_value_raw   = trim($_POST['collateral_value'] ?? '');
$collateral_value       = $collateral_value_raw === '' ? null : (float) $collateral_value_raw;

// Predefined plans are no longer chosen by customers; keep the legacy default
$plan_id = (int) ($_POST['plan_id'] ?? 1);

// ---------------------------------------------------------------
// Validate input
// ---------------------------------------------------------------
if ($loan_type_id <= 0) {
    apply_fail('Please select a loan type.');
}
if ($amount < 1000 || $amount > 1000000) {
    apply_fail('Loan amount must be between K 1,000 and K 1,000,000.');
}
if ($purpose === '') {
    apply_fail('Please describe the purpose of the loan.');
}
if ($collateral_description === '') {
    apply_fail('All loans are collateral based. Please describe the collateral you are offering.');
}
if ($collateral_value !== null && $collateral_value < 0) {
    apply_fail('Collateral value cannot be negative.');
}

// Repayment term: "week:1".."week:4", "month:1", or "month:multi" + duration_months
$term_choice = $_POST['term_choice'] ?? '';
if (preg_match('/^week:(\d+)$/', $term_choice, $m)) {
    $term_unit = 'week';
    $duration  = (int) $m[1];
} elseif ($term_choice === 'month:1') {
    $term_unit = 'month';
    $duration  = 1;
} elseif ($term_choice === 'month:multi') {
    $term_unit = 'month';
    $duration  = (int) ($_POST['duration_months'] ?? 0);
    if ($duration < 2 || $duration > 120) {
        apply_fail('Please enter a loan duration between 2 and 120 months.');
    }
} else {
    apply_fail('Please choose a repayment term.');
}

// ---------------------------------------------------------------
// Calculate the loan
// - Weekly loans: fixed rate by length (15/20/25/30%)
// - 1-month loans: fixed 18%
// - 2+ month loans: rate set by staff at review (stored as 0 until then;
//   totals below are an 18% preview and are recalculated on approval)
// ---------------------------------------------------------------
try {
    $calc = calculateLoanForTerm($amount, 0, $duration, $term_unit);
} catch (Exception $e) {
    apply_fail($e->getMessage());
}

$interest_rate       = $calc['rate_not_set'] ? 0.0 : (float) $calc['interest_rate'];
$totalInterest       = $calc['total_interest'];
$totalPayable        = $calc['total_payable'];
$monthlyInstallment  = $calc['monthly_installment'];
$outstanding_balance = $totalPayable;

// Generate unique reference number
do {
    $ref_no = (string) mt_rand(10000000, 99999999);
    $stmt = $conn->prepare("SELECT id FROM loan_list WHERE ref_no = ?");
    $stmt->bind_param("s", $ref_no);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
} while ($exists);

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO loan_list
            (ref_no, loan_type_id, borrower_id, purpose, amount, plan_id, status, application_source, application_status, date_created,
             duration_months, term_unit, interest_rate, total_interest, total_payable, monthly_installment, outstanding_balance,
             collateral_description, collateral_value)
            VALUES (?, ?, ?, ?, ?, ?, 0, 'customer', 1, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("siisdiisdddddsd",
        $ref_no, $loan_type_id, $customer_id, $purpose, $amount, $plan_id,
        $duration, $term_unit, $interest_rate, $totalInterest, $totalPayable, $monthlyInstallment, $outstanding_balance,
        $collateral_description, $collateral_value
    );

    if(!$stmt->execute()) {
        throw new Exception('Error submitting application: ' . $stmt->error);
    }
    $stmt->close();

    $loan_id = $conn->insert_id;

    // Create notification for customer
    $title   = 'Application Submitted';
    $message = "Your loan application (Ref: $ref_no) for a " . formatLoanTerm($duration, $term_unit) . " loan has been submitted successfully. Our team will review it shortly.";
    $type    = 'success';
    $stmt = $conn->prepare("INSERT INTO customer_notifications (borrower_id, title, message, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $customer_id, $title, $message, $type);
    $stmt->execute();
    $stmt->close();

    // Create application checklist for admin review
    $checklist_items = array(
        'Identity Verification - ID Document Checked',
        'Employment Verification - Proof of Employment Verified',
        'Income Verification - Pay Slip Reviewed',
        'Collateral Verified and Valued',
        'Credit History Check',
        'Loan Amount Feasibility Assessment',
        'Purpose of Loan Evaluation',
        'Repayment Capacity Analysis'
    );

    $stmt = $conn->prepare("INSERT INTO loan_application_checklist (loan_id, item) VALUES (?, ?)");
    foreach($checklist_items as $item) {
        $stmt->bind_param("is", $loan_id, $item);
        $stmt->execute();
    }
    $stmt->close();

    $conn->commit();

} catch(Exception $e) {
    $conn->rollback();
    error_log('Loan application failed: ' . $e->getMessage());
    apply_fail('Application submission failed. Please try again or contact us.');
}

// Get customer name for notification
$stmt = $conn->prepare("SELECT firstname, lastname FROM borrowers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();
$customer_name = $customer['firstname'] . ' ' . $customer['lastname'];

// Notify admin of new loan application (email + in-app notification)
notify_admin_new_loan($conn, $loan_id, $customer_name, $amount);

$_SESSION['success_msg'] = 'Your loan application has been submitted successfully! Reference Number: ' . $ref_no;
header('Location: customer_my_loans.php');
exit;
