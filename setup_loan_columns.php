<?php
/**
 * Setup script to add missing columns to loan_list table
 * Run this once: http://localhost/loan/setup_loan_columns.php
 */

include 'db_connect.php';

echo "<h2>Adding Missing Columns to loan_list Table</h2>";
echo "<pre>";

$columns_to_add = [
    'total_interest' => "DECIMAL(15,2) DEFAULT 0",
    'total_payable' => "DECIMAL(15,2) DEFAULT 0",
    'monthly_installment' => "DECIMAL(15,2) DEFAULT 0",
    'outstanding_balance' => "DECIMAL(15,2) DEFAULT 0",
    'interest_rate' => "DECIMAL(5,2) DEFAULT 0",
    'duration_months' => "INT DEFAULT 1"
];

foreach($columns_to_add as $column => $definition) {
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM loan_list LIKE '$column'");

    if($check->num_rows == 0) {
        // Column doesn't exist, add it
        $sql = "ALTER TABLE loan_list ADD COLUMN $column $definition";
        if($conn->query($sql)) {
            echo "✓ Added column: $column\n";
        } else {
            echo "✗ Error adding $column: " . $conn->error . "\n";
        }
    } else {
        echo "- Column already exists: $column\n";
    }
}

echo "\n<strong>Done!</strong>\n";
echo "</pre>";

echo "<p><a href='admin.php?page=home'>Go to Admin Dashboard</a></p>";
?>
