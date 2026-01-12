<?php
// Backfill script to compute totals for existing loans
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/finance.php';

$db = getDB();
$stmt = $db->query("SELECT id, amount, plan_id, total_payable, interest_rate, duration_months, calculation_type FROM loan_list");
while($row = $stmt->fetch_assoc()){
    $id = $row['id'];
    $amount = floatval($row['amount']);
    $interest = $row['interest_rate'] !== null ? floatval($row['interest_rate']) : 18.0;
    $months = intval($row['duration_months']) > 0 ? intval($row['duration_months']) : (
        ($row['plan_id']) ? intval($db->query("SELECT months FROM loan_plan WHERE id = " . intval($row['plan_id']))->fetch_assoc()['months'] ?? 0) : 0
    );
    $ctype = $row['calculation_type'] ?? 'simple';

    try{
        $calc = calculateLoan($amount, $interest, max(1,$months), $ctype);
        $db->query("UPDATE loan_list SET loan_amount = " . $db->quote($amount) . ", interest_rate = " . $db->quote($calc['interest_rate']) . ", calculation_type = " . $db->quote($calc['calculation_type']) . ", duration_months = " . intval($calc['months']) . ", total_interest = " . $db->quote($calc['total_interest']) . ", total_payable = " . $db->quote($calc['total_payable']) . ", monthly_installment = " . $db->quote($calc['monthly_installment']) . ", outstanding_balance = " . $db->quote($calc['total_payable']) . " WHERE id = " . intval($id));
        echo "Backfilled loan {$id}\n";
    }catch(Exception $e){
        echo "Skipped loan {$id}: " . $e->getMessage() . "\n";
    }
}
echo "Done.\n";
