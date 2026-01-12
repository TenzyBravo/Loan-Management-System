<?php
session_start();
include('db_connect.php');

if(!isset($_SESSION['customer_id'])){
    header('location: customer_login.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $customer_id = $_SESSION['customer_id'];
    $loan_type_id = $conn->real_escape_string($_POST['loan_type_id']);
    $amount = $conn->real_escape_string($_POST['amount']);
    $purpose = $conn->real_escape_string($_POST['purpose']);
    $plan_id = $conn->real_escape_string($_POST['plan_id']);
    
    // Generate unique reference number
    $ref_no = mt_rand(10000000, 99999999);
    
    // Check if ref_no already exists
    while($conn->query("SELECT id FROM loan_list WHERE ref_no = '$ref_no'")->num_rows > 0) {
        $ref_no = mt_rand(10000000, 99999999);
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert loan application
        $sql = "INSERT INTO loan_list (ref_no, loan_type_id, borrower_id, purpose, amount, plan_id, status, application_source, application_status, date_created) 
                VALUES ('$ref_no', $loan_type_id, $customer_id, '$purpose', $amount, $plan_id, 0, 'customer', 1, NOW())";
        
        if(!$conn->query($sql)) {
            throw new Exception('Error submitting application: ' . $conn->error);
        }
        
        $loan_id = $conn->insert_id;
        
        // Create notification for customer
        $notif_sql = "INSERT INTO customer_notifications (borrower_id, title, message, type) 
                      VALUES ($customer_id, 'Application Submitted', 'Your loan application (Ref: $ref_no) has been submitted successfully. Our team will review it shortly.', 'success')";
        $conn->query($notif_sql);
        
        // Log activity
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_sql = "INSERT INTO activity_log (user_id, user_type, action, description, target_id, target_type, ip_address) 
                    VALUES ($customer_id, 'customer', 'loan_application', 'Submitted loan application Ref: $ref_no for $$amount', $loan_id, 'loan', '$ip')";
        $conn->query($log_sql);
        
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
        
        foreach($checklist_items as $item) {
            $checklist_sql = "INSERT INTO loan_application_checklist (loan_id, item) VALUES ($loan_id, '$item')";
            $conn->query($checklist_sql);
        }
        
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
