<?php
/**
 * Notification Helper Functions
 * Handles admin and customer notifications with email support
 */

/**
 * Create an admin notification
 */
function create_admin_notification($conn, $type, $title, $message, $reference_id = null, $reference_type = null) {
    $stmt = $conn->prepare("INSERT INTO admin_notifications (type, title, message, reference_id, reference_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssis", $type, $title, $message, $reference_id, $reference_type);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Create a customer notification
 */
function create_customer_notification($conn, $borrower_id, $type, $title, $message, $reference_id = null, $reference_type = null) {
    $stmt = $conn->prepare("INSERT INTO customer_notifications (borrower_id, type, title, message, reference_id, reference_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssis", $borrower_id, $type, $title, $message, $reference_id, $reference_type);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Get unread admin notification count
 */
function get_admin_notification_count($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

/**
 * Get unread customer notification count
 */
function get_customer_notification_count($conn, $borrower_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM customer_notifications WHERE borrower_id = ? AND is_read = 0");
    $stmt->bind_param("i", $borrower_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['count'] ?? 0;
}

/**
 * Get recent admin notifications
 */
function get_admin_notifications($conn, $limit = 10) {
    $stmt = $conn->prepare("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    return $notifications;
}

/**
 * Get recent customer notifications
 */
function get_customer_notifications($conn, $borrower_id, $limit = 10) {
    $stmt = $conn->prepare("SELECT * FROM customer_notifications WHERE borrower_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $borrower_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    return $notifications;
}

/**
 * Mark admin notification as read
 */
function mark_admin_notification_read($conn, $notification_id) {
    $stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $notification_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Mark all admin notifications as read
 */
function mark_all_admin_notifications_read($conn) {
    return $conn->query("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
}

/**
 * Mark customer notification as read
 */
function mark_customer_notification_read($conn, $notification_id, $borrower_id) {
    $stmt = $conn->prepare("UPDATE customer_notifications SET is_read = 1 WHERE id = ? AND borrower_id = ?");
    $stmt->bind_param("ii", $notification_id, $borrower_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Mark all customer notifications as read
 */
function mark_all_customer_notifications_read($conn, $borrower_id) {
    $stmt = $conn->prepare("UPDATE customer_notifications SET is_read = 1 WHERE borrower_id = ? AND is_read = 0");
    $stmt->bind_param("i", $borrower_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Send email notification
 * Uses PHP mail() function - works on most hosting
 */
function send_email_notification($to_email, $to_name, $subject, $message, $from_name = 'Brian Investments') {
    // Email configuration
    $from_email = 'noreply@brianinvestment.yzz.me'; // Change this to your domain

    // Build email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$from_name} <{$from_email}>\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Build HTML email
    $html_message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #e0e0e0; }
            .footer { background: #333; color: #999; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 10px 10px; }
            .btn { display: inline-block; background: #1a5f2a; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            .highlight { background: #fff3cd; padding: 15px; border-left: 4px solid #d4af37; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0;'>Brian Investments</h1>
                <p style='margin:5px 0 0 0; opacity:0.9;'>Loan Management System</p>
            </div>
            <div class='content'>
                <p>Dear {$to_name},</p>
                {$message}
                <p style='margin-top:20px;'>Best regards,<br><strong>Brian Investments Team</strong></p>
            </div>
            <div class='footer'>
                <p>This is an automated message from Brian Investments Loan Management System.</p>
                <p>Please do not reply directly to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Try to send email
    $result = @mail($to_email, $subject, $html_message, $headers);

    // Log email attempt
    error_log("Email notification sent to {$to_email}: " . ($result ? "Success" : "Failed"));

    return $result;
}

/**
 * Notify admin of new loan application
 */
function notify_admin_new_loan($conn, $loan_id, $borrower_name, $amount) {
    // Create in-app notification
    $title = "New Loan Application";
    $message = "{$borrower_name} has applied for a loan of K " . number_format($amount, 2);
    create_admin_notification($conn, 'loan_application', $title, $message, $loan_id, 'loan');

    // Get admin email (first admin user)
    $admin_result = $conn->query("SELECT email, name FROM users WHERE type = 1 LIMIT 1");
    if ($admin_row = $admin_result->fetch_assoc()) {
        $email_message = "
            <p>A new loan application has been submitted and requires your review.</p>
            <div class='highlight'>
                <strong>Applicant:</strong> {$borrower_name}<br>
                <strong>Amount Requested:</strong> K " . number_format($amount, 2) . "
            </div>
            <p>Please log in to the admin panel to review this application.</p>
            <a href='https://brianinvestment.yzz.me/admin.php?page=loan_applications_review' class='btn'>Review Application</a>
        ";
        send_email_notification($admin_row['email'], $admin_row['name'], "New Loan Application - {$borrower_name}", $email_message);
    }
}

/**
 * Notify customer of loan status change
 */
function notify_customer_loan_status($conn, $borrower_id, $loan_id, $status, $amount, $remarks = '') {
    // Get customer info
    $stmt = $conn->prepare("SELECT firstname, lastname, email FROM borrowers WHERE id = ?");
    $stmt->bind_param("i", $borrower_id);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$customer) return false;

    $customer_name = $customer['firstname'] . ' ' . $customer['lastname'];
    $customer_email = $customer['email'];

    $status_labels = [
        1 => ['text' => 'Approved', 'type' => 'loan_approved', 'color' => '#28a745'],
        2 => ['text' => 'Released', 'type' => 'loan_released', 'color' => '#17a2b8'],
        3 => ['text' => 'Completed', 'type' => 'loan_completed', 'color' => '#6c757d'],
        4 => ['text' => 'Denied', 'type' => 'loan_denied', 'color' => '#dc3545']
    ];

    $status_info = $status_labels[$status] ?? ['text' => 'Updated', 'type' => 'loan_updated', 'color' => '#6c757d'];

    // Create in-app notification
    $title = "Loan Application {$status_info['text']}";
    $message = "Your loan application for K " . number_format($amount, 2) . " has been {$status_info['text']}.";
    if ($remarks) {
        $message .= " Remarks: {$remarks}";
    }
    create_customer_notification($conn, $borrower_id, $status_info['type'], $title, $message, $loan_id, 'loan');

    // Send email
    if ($status == 1) { // Approved
        $email_message = "
            <p>Great news! Your loan application has been <strong style='color:{$status_info['color']};'>APPROVED</strong>.</p>
            <div class='highlight'>
                <strong>Loan Amount:</strong> K " . number_format($amount, 2) . "<br>
                <strong>Status:</strong> Approved - Pending Release
            </div>
            <p>The funds will be released to your account shortly. You will receive another notification once the funds have been disbursed.</p>
            <a href='https://brianinvestment.yzz.me/customer_my_loans.php' class='btn'>View My Loans</a>
        ";
    } elseif ($status == 2) { // Released
        $email_message = "
            <p>Your loan has been <strong style='color:{$status_info['color']};'>RELEASED</strong>.</p>
            <div class='highlight'>
                <strong>Loan Amount:</strong> K " . number_format($amount, 2) . "<br>
                <strong>Status:</strong> Funds Released
            </div>
            <p>The funds have been disbursed to your account. Please check your bank account for the deposit.</p>
            <p>Remember to make your payments on time to maintain a good credit standing with us.</p>
            <a href='https://brianinvestment.yzz.me/customer_my_loans.php' class='btn'>View Payment Schedule</a>
        ";
    } elseif ($status == 4) { // Denied
        $email_message = "
            <p>We regret to inform you that your loan application has been <strong style='color:{$status_info['color']};'>DENIED</strong>.</p>
            <div class='highlight'>
                <strong>Loan Amount Requested:</strong> K " . number_format($amount, 2) . "<br>
                <strong>Status:</strong> Application Denied
                " . ($remarks ? "<br><strong>Reason:</strong> {$remarks}" : "") . "
            </div>
            <p>If you have any questions about this decision, please contact our office.</p>
            <p>You may apply again after addressing any issues mentioned above.</p>
        ";
    } else {
        $email_message = "
            <p>Your loan application status has been updated to <strong style='color:{$status_info['color']};'>{$status_info['text']}</strong>.</p>
            <div class='highlight'>
                <strong>Loan Amount:</strong> K " . number_format($amount, 2) . "
            </div>
            <a href='https://brianinvestment.yzz.me/customer_my_loans.php' class='btn'>View My Loans</a>
        ";
    }

    send_email_notification($customer_email, $customer_name, "Loan Application {$status_info['text']} - Brian Investments", $email_message);

    return true;
}

/**
 * Notify customer of payment received
 */
function notify_customer_payment($conn, $borrower_id, $loan_id, $amount, $remaining) {
    // Get customer info
    $stmt = $conn->prepare("SELECT firstname, lastname, email FROM borrowers WHERE id = ?");
    $stmt->bind_param("i", $borrower_id);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$customer) return false;

    $customer_name = $customer['firstname'] . ' ' . $customer['lastname'];
    $customer_email = $customer['email'];

    // Create in-app notification
    $title = "Payment Received";
    $message = "Your payment of K " . number_format($amount, 2) . " has been received. Remaining balance: K " . number_format($remaining, 2);
    create_customer_notification($conn, $borrower_id, 'payment_received', $title, $message, $loan_id, 'loan');

    // Send email
    $email_message = "
        <p>Thank you! We have received your loan payment.</p>
        <div class='highlight'>
            <strong>Amount Paid:</strong> K " . number_format($amount, 2) . "<br>
            <strong>Remaining Balance:</strong> K " . number_format($remaining, 2) . "
        </div>
        <p>Thank you for your timely payment. Please continue to make payments on schedule.</p>
        <a href='https://brianinvestment.yzz.me/customer_my_loans.php' class='btn'>View Payment History</a>
    ";

    send_email_notification($customer_email, $customer_name, "Payment Received - Brian Investments", $email_message);

    return true;
}

/**
 * Get notification icon based on type
 */
function get_notification_icon($type) {
    $icons = [
        'loan_application' => 'fa-file-alt text-primary',
        'loan_approved' => 'fa-check-circle text-success',
        'loan_denied' => 'fa-times-circle text-danger',
        'loan_released' => 'fa-money-bill-wave text-info',
        'loan_completed' => 'fa-flag-checkered text-secondary',
        'payment_received' => 'fa-hand-holding-usd text-success',
        'payment_due' => 'fa-clock text-warning',
        'payment_overdue' => 'fa-exclamation-triangle text-danger',
        'document_upload' => 'fa-file-upload text-primary',
        'document_verified' => 'fa-check text-success',
        'document_rejected' => 'fa-times text-danger'
    ];
    return $icons[$type] ?? 'fa-bell text-secondary';
}

/**
 * Format notification time ago
 */
function time_ago($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
