<?php
session_start();
include('db_connect.php');

if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $customer_id = $_SESSION['customer_id'];
    $loan_type_id = $_POST['loan_type_id'];
    $amount = $_POST['amount'];
    $purpose = $_POST['purpose'];
    $duration_months = $_POST['duration_months'] ?? 12; // Default to 12 months if not provided

    // Use a dummy plan_id since we're not using predefined plans anymore
    $plan_id = $_POST['plan_id'] ?? 1; // Default to first plan if not provided
    
    // Generate unique reference number
    $ref_no = mt_rand(10000000, 99999999);
    
    // Check if ref_no already exists
    do {
        $stmt = $conn->prepare("SELECT id FROM loan_list WHERE ref_no = ?");
        $stmt->bind_param("s", $ref_no);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $ref_no = mt_rand(10000000, 99999999);
        }
    } while ($exists);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Business Logic: Auto-assign 18% for loans <= K5,000
        // For loans > K5,000, admin will assign custom rate
        if($amount <= 5000) {
            $interest_rate = 18.0; // Auto-assign 18% for small loans
            $calculation_type = 'simple'; // Use simple interest
        } else {
            $interest_rate = 0; // Will be set by admin during review
            $calculation_type = 'simple'; // Default, admin can change
        }

        // Calculate loan values (for small loans with assigned rate, or estimate for large loans)
        if($interest_rate > 0) {
            // Calculate actual values
            $monthlyRate = $interest_rate / 100 / 12;
            $totalInterest = $amount * $monthlyRate * $duration_months;
            $totalPayable = $amount + $totalInterest;
            $monthlyInstallment = $totalPayable / $duration_months;
        } else {
            // Estimate for large loans (will be recalculated by admin)
            $estimated_interest_rate = 28.0; // Conservative estimate
            $monthlyRate = $estimated_interest_rate / 100 / 12;
            $totalInterest = $amount * $monthlyRate * $duration_months;
            $totalPayable = $amount + $totalInterest;
            $monthlyInstallment = $totalPayable / $duration_months;
        }

        // Insert loan application with original fields (compatible with original database)
        $stmt = $conn->prepare("INSERT INTO loan_list (ref_no, loan_type_id, borrower_id, purpose, amount, plan_id, status, application_source, application_status, date_created)
                VALUES (?, ?, ?, ?, ?, ?, 0, 'customer', 1, NOW())");
        $stmt->bind_param("siiisi", $ref_no, $loan_type_id, $customer_id, $purpose, $amount, $plan_id);

        if(!$stmt->execute()) {
            throw new Exception('Error submitting application: ' . $conn->error);
        }
        $stmt->close();

        // Get the inserted loan ID for additional processing
        $loan_id = $conn->insert_id;

        // If enhanced fields exist in the database, update with all loan details
        $check_columns = $conn->query("SHOW COLUMNS FROM loan_list LIKE 'duration_months'");
        if($check_columns->num_rows > 0) {
            // Update with complete loan information
            $update_stmt = $conn->prepare("UPDATE loan_list SET
                duration_months=?,
                loan_amount=?,
                interest_rate=?,
                calculation_type=?,
                total_interest=?,
                total_payable=?,
                monthly_installment=?,
                outstanding_balance=?
                WHERE id=?");
            $outstanding_balance = $totalPayable; // Initial outstanding balance
            $update_stmt->bind_param("idssddddi",
                $duration_months,
                $amount,
                $interest_rate,
                $calculation_type,
                $totalInterest,
                $totalPayable,
                $monthlyInstallment,
                $outstanding_balance,
                $loan_id
            );
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        // Create notification for customer
        $stmt = $conn->prepare("INSERT INTO customer_notifications (borrower_id, title, message, type)
                      VALUES (?, 'Application Submitted', 'Your loan application (Ref: $ref_no) has been submitted successfully. Our team will review it shortly.', 'success')");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $stmt->close();
        
        // Skip activity logging if activity_log table doesn't exist
        // Uncomment the following code if you have the activity_log table
        /*
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_type, action, description, target_id, target_type, ip_address)
                    VALUES (?, 'customer', 'loan_application', 'Submitted loan application Ref: $ref_no for K $amount', ?, 'loan', ?)");
        $stmt->bind_param("iis", $customer_id, $loan_id, $ip);
        $stmt->execute();
        $stmt->close();
        */
        
        // Create application checklist for admin review
        $checklist_items = array(
            'Identity Verification - ID Document Checked',
            'Employment Verification - Proof of Employment Verified',
            'Income Verification - Pay Slip Reviewed',
            'Credit History Check',
            'Loan Amount Feasibility Assessment',
            'Purpose of Loan Evaluation',
            'Repayment Capacity Analysis'
        );
        
        // Prepare statement for checklist items
        $stmt = $conn->prepare("INSERT INTO loan_application_checklist (loan_id, item) VALUES (?, ?)");
        foreach($checklist_items as $item) {
            $stmt->bind_param("is", $loan_id, $item);
            $stmt->execute();
        }
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success_msg'] = 'Your loan application has been submitted successfully! Reference Number: ' . $ref_no;
        header('Location: customer_my_loans.php');
        exit;
        
    } catch(Exception $e) {
        // Rollback on error
        $conn->rollback();
        
        $_SESSION['error_msg'] = 'Application submission failed: ' . $e->getMessage();
        header('Location: customer_apply_loan.php');
        exit;
    }
    
} else {
    header('Location: customer_apply_loan.php');
    exit;
}
?>
