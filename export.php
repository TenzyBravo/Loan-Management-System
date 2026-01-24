<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['login_id']) || !isset($_SESSION['login_type']) || $_SESSION['login_type'] != 1) {
    die('Access denied');
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

if(empty($type)) {
    die('Export type not specified');
}

// Helper function to output CSV
function outputCSV($filename, $headers, $data) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Write headers
    fputcsv($output, $headers);

    // Write data
    foreach($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

// Database backup
if($type == 'database_backup') {
    $tables = array();
    $result = $conn->query("SHOW TABLES");
    while($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $sql_content = "-- Brian Investments Database Backup\n";
    $sql_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql_content .= "-- Database: loan_db\n\n";
    $sql_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach($tables as $table) {
        // Get CREATE TABLE statement
        $create = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $create->fetch_row();

        $sql_content .= "-- Table structure for `$table`\n";
        $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_content .= $row[1] . ";\n\n";

        // Get table data
        $data = $conn->query("SELECT * FROM `$table`");
        $num_fields = $data->field_count;

        if($data->num_rows > 0) {
            $sql_content .= "-- Data for `$table`\n";

            while($row = $data->fetch_row()) {
                $sql_content .= "INSERT INTO `$table` VALUES(";
                $values = array();
                for($i = 0; $i < $num_fields; $i++) {
                    if(is_null($row[$i])) {
                        $values[] = "NULL";
                    } else {
                        $values[] = "'" . $conn->real_escape_string($row[$i]) . "'";
                    }
                }
                $sql_content .= implode(',', $values) . ");\n";
            }
            $sql_content .= "\n";
        }
    }

    $sql_content .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // Output SQL file
    $filename = 'brian_investments_backup_' . date('Y-m-d_His') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql_content));
    header('Pragma: no-cache');
    header('Expires: 0');

    echo $sql_content;
    exit;
}

// Export Borrowers
if($type == 'borrowers') {
    $headers = ['ID', 'Firstname', 'Middlename', 'Lastname', 'Contact', 'Address', 'Email', 'Date Registered'];

    $result = $conn->query("SELECT id, firstname, middlename, lastname, contact_no, address, email, date_created FROM borrowers ORDER BY id");
    if(!$result) {
        die('Query error: ' . $conn->error);
    }
    $data = array();
    while($row = $result->fetch_assoc()) {
        $data[] = [
            $row['id'],
            $row['firstname'],
            $row['middlename'],
            $row['lastname'],
            $row['contact_no'],
            $row['address'],
            $row['email'],
            $row['date_created']
        ];
    }

    outputCSV('borrowers_export_' . date('Y-m-d') . '.csv', $headers, $data);
}

// Export Loans
if($type == 'loans') {
    $headers = ['Loan ID', 'Reference No', 'Borrower ID', 'Loan Type', 'Plan (Months)', 'Principal Amount', 'Interest Rate %', 'Interest Amount', 'Total Payable', 'Duration Months', 'Purpose', 'Status', 'Date Created', 'Date Released'];

    $statuses = [0 => 'Pending', 1 => 'Approved', 2 => 'Released', 3 => 'Completed', 4 => 'Denied'];

    $result = $conn->query("
        SELECT l.*, lt.type_name, lp.months as plan_months
        FROM loan_list l
        LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
        LEFT JOIN loan_plan lp ON l.plan_id = lp.id
        ORDER BY l.id
    ");

    if(!$result) {
        die('Query error: ' . $conn->error);
    }

    $data = array();
    while($row = $result->fetch_assoc()) {
        $principal = floatval($row['amount']);
        $interest_rate = floatval($row['interest_rate'] ?? 0);
        $interest_amount = $principal * ($interest_rate / 100);
        $total_payable = $principal + $interest_amount;

        $data[] = [
            $row['id'],
            $row['ref_no'],
            $row['borrower_id'],
            $row['type_name'] ?? 'N/A',
            $row['plan_months'] ?? 'N/A',
            number_format($principal, 2),
            $interest_rate,
            number_format($interest_amount, 2),
            number_format($total_payable, 2),
            $row['duration_months'] ?? 1,
            $row['purpose'],
            $statuses[$row['status']] ?? 'Unknown',
            $row['date_created'],
            $row['date_released']
        ];
    }

    outputCSV('loans_export_' . date('Y-m-d') . '.csv', $headers, $data);
}

// Export Payments
if($type == 'payments') {
    $headers = ['Payment ID', 'Loan ID', 'Reference No', 'Borrower Name', 'Amount Paid', 'Penalty Amount', 'Payment Date'];

    $result = $conn->query("
        SELECT p.*, l.ref_no,
               CONCAT(b.firstname, ' ', b.lastname) as borrower_name
        FROM payments p
        LEFT JOIN loan_list l ON p.loan_id = l.id
        LEFT JOIN borrowers b ON l.borrower_id = b.id
        ORDER BY p.id DESC
    ");

    if(!$result) {
        die('Query error: ' . $conn->error);
    }

    $data = array();
    while($row = $result->fetch_assoc()) {
        $data[] = [
            $row['id'],
            $row['loan_id'],
            $row['ref_no'] ?? 'N/A',
            $row['borrower_name'] ?? 'N/A',
            number_format(floatval($row['amount']), 2),
            number_format(floatval($row['penalty_amount'] ?? 0), 2),
            $row['date_created']
        ];
    }

    outputCSV('payments_export_' . date('Y-m-d') . '.csv', $headers, $data);
}

// Export Loan Types
if($type == 'loan_types') {
    $headers = ['ID', 'Type Name', 'Description'];

    $result = $conn->query("SELECT id, type_name, description FROM loan_types ORDER BY id");
    if(!$result) {
        die('Query error: ' . $conn->error);
    }
    $data = array();
    while($row = $result->fetch_assoc()) {
        $data[] = [
            $row['id'],
            $row['type_name'],
            $row['description']
        ];
    }

    outputCSV('loan_types_export_' . date('Y-m-d') . '.csv', $headers, $data);
}

// Export Loan Plans
if($type == 'loan_plans') {
    $headers = ['ID', 'Months', 'Interest Percentage %', 'Penalty Rate %', 'Calculation Type'];

    $result = $conn->query("SELECT id, months, interest_percentage, penalty_rate, calculation_type FROM loan_plan ORDER BY id");
    if(!$result) {
        die('Query error: ' . $conn->error);
    }
    $data = array();
    while($row = $result->fetch_assoc()) {
        $data[] = [
            $row['id'],
            $row['months'],
            $row['interest_percentage'] ?? 0,
            $row['penalty_rate'] ?? 5,
            $row['calculation_type'] ?? 'simple'
        ];
    }

    outputCSV('loan_plans_export_' . date('Y-m-d') . '.csv', $headers, $data);
}

// Export Full Report
if($type == 'full_report') {
    $headers = [
        'Loan ID', 'Reference No', 'Borrower Name', 'Contact', 'Email',
        'Loan Type', 'Plan (Months)', 'Principal (K)', 'Interest Rate %', 'Total Payable (K)',
        'Amount Paid (K)', 'Outstanding (K)', 'Status', 'Date Applied', 'Date Released'
    ];

    $statuses = [0 => 'Pending', 1 => 'Approved', 2 => 'Released', 3 => 'Completed', 4 => 'Denied'];

    $result = $conn->query("
        SELECT l.*,
               CONCAT(b.firstname, ' ', b.lastname) as borrower_name,
               b.contact_no, b.email,
               lt.type_name, lp.months as plan_months,
               COALESCE(paid.total_paid, 0) as total_paid
        FROM loan_list l
        LEFT JOIN borrowers b ON l.borrower_id = b.id
        LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
        LEFT JOIN loan_plan lp ON l.plan_id = lp.id
        LEFT JOIN (
            SELECT loan_id, SUM(amount) as total_paid
            FROM payments
            GROUP BY loan_id
        ) paid ON l.id = paid.loan_id
        ORDER BY l.id DESC
    ");

    if(!$result) {
        die('Query error: ' . $conn->error);
    }

    $data = array();
    while($row = $result->fetch_assoc()) {
        $principal = floatval($row['amount']);
        $interest_rate = floatval($row['interest_rate'] ?? 0);
        $total_payable = $principal + ($principal * $interest_rate / 100);
        $total_paid = floatval($row['total_paid']);
        $outstanding = $total_payable - $total_paid;

        $data[] = [
            $row['id'],
            $row['ref_no'],
            $row['borrower_name'] ?? 'N/A',
            $row['contact_no'] ?? '',
            $row['email'] ?? '',
            $row['type_name'] ?? 'N/A',
            $row['plan_months'] ?? 'N/A',
            number_format($principal, 2),
            $interest_rate,
            number_format($total_payable, 2),
            number_format($total_paid, 2),
            number_format($outstanding, 2),
            $statuses[$row['status']] ?? 'Unknown',
            $row['date_created'],
            $row['date_released'] ?? ''
        ];
    }

    outputCSV('full_loan_report_' . date('Y-m-d') . '.csv', $headers, $data);
}

// If no matching export type
die('Invalid export type');
?>
