<?php
/**
 * Secure Action Class
 * Uses prepared statements and password hashing
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/finance.php';
require_once __DIR__ . '/includes/helpers.php';

Security::secureSession();

class SecureAction {
    private $db;
    private $conn;

    public function __construct() {
        ob_start();
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    public function __destruct() {
        ob_end_flush();
    }

    /**
     * Secure Login with password hashing
     */
    public function login() {
        $username = Security::sanitizeString($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Rate limiting
        $ipKey = 'login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!Security::checkRateLimit($ipKey, 5, 15)) {
            Security::logSecurityEvent('login_rate_limited', ['username' => $username], $this->conn);
            return json_encode(['status' => 'error', 'message' => 'Too many login attempts. Please try again in 15 minutes.']);
        }
        
        if (empty($username) || empty($password)) {
            return 3; // Invalid
        }
        
        // Fetch user by username only (check password separately)
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if password is hashed (bcrypt starts with $2)
            $isHashed = strpos($user['password'], '$2') === 0;
            
            $passwordValid = false;
            
            if ($isHashed) {
                // Verify hashed password
                $passwordValid = Security::verifyPassword($password, $user['password']);
            } else {
                // Legacy plain text password check (for migration)
                $passwordValid = ($user['password'] === $password);
                
                // If valid, upgrade to hashed password
                if ($passwordValid) {
                    $this->upgradePassword($user['id'], $password);
                }
            }
            
            if ($passwordValid) {
                // Clear rate limit on successful login
                Security::clearRateLimit($ipKey);

                // Set session variables FIRST before regeneration
                foreach ($user as $key => $value) {
                    if ($key !== 'password' && !is_numeric($key)) {
                        $_SESSION['login_' . $key] = $value;
                    }
                }

                // Regenerate session ID to prevent fixation (after setting session data)
                Security::regenerateSession();

                // Log successful login
                Security::logSecurityEvent('login_success', ['user_id' => $user['id'], 'username' => $username], $this->conn);

                $stmt->close();
                return 1; // Success
            }
        }
        
        // Log failed login
        Security::logSecurityEvent('login_failed', ['username' => $username], $this->conn);
        
        $stmt->close();
        return 3; // Invalid credentials
    }
    
    /**
     * Upgrade plain text password to hashed
     */
    private function upgradePassword($userId, $plainPassword) {
        $hashedPassword = Security::hashPassword($plainPassword);
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        $stmt->execute();
        $stmt->close();
        
        Security::logSecurityEvent('password_upgraded', ['user_id' => $userId], $this->conn);
    }
    
    /**
     * Secure Logout
     */
    public function logout() {
        $userId = $_SESSION['login_id'] ?? null;
        
        Security::logSecurityEvent('logout', ['user_id' => $userId], $this->conn);
        
        // Clear all session data
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        header("Location: login.php");
        exit;
    }

    /**
     * Save User with password hashing
     */
    public function save_user() {
        // CSRF validation
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            return json_encode(['status' => 'error', 'message' => 'Invalid request token']);
        }
        
        $id = Security::sanitizeInt($_POST['id'] ?? '');
        $name = Security::sanitizeString($_POST['name'] ?? '');
        $username = Security::sanitizeString($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $type = Security::sanitizeInt($_POST['type'] ?? 2);
        
        if (empty($id)) {
            // Insert new user
            if (empty($password)) {
                return json_encode(['status' => 'error', 'message' => 'Password is required']);
            }
            
            // Check if username exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $stmt->close();
                return json_encode(['status' => 'error', 'message' => 'Username already exists']);
            }
            $stmt->close();
            
            $hashedPassword = Security::hashPassword($password);
            
            $stmt = $this->conn->prepare("INSERT INTO users (name, username, password, type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $name, $username, $hashedPassword, $type);
            
        } else {
            // Update existing user
            if (!empty($password)) {
                $hashedPassword = Security::hashPassword($password);
                $stmt = $this->conn->prepare("UPDATE users SET name = ?, username = ?, password = ?, type = ? WHERE id = ?");
                $stmt->bind_param("sssii", $name, $username, $hashedPassword, $type, $id);
            } else {
                $stmt = $this->conn->prepare("UPDATE users SET name = ?, username = ?, type = ? WHERE id = ?");
                $stmt->bind_param("ssii", $name, $username, $type, $id);
            }
        }
        
        if ($stmt->execute()) {
            Security::logSecurityEvent('user_saved', ['user_id' => $id ?: $this->conn->insert_id], $this->conn);
            $stmt->close();
            return 1;
        }
        
        $stmt->close();
        return json_encode(['status' => 'error', 'message' => 'Failed to save user']);
    }

    /**
     * Delete User
     */
    public function delete_user() {
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            return json_encode(['status' => 'error', 'message' => 'Invalid request token']);
        }
        
        $id = Security::sanitizeInt($_POST['id'] ?? '');
        
        // Prevent deleting self
        if ($id == ($_SESSION['login_id'] ?? 0)) {
            return json_encode(['status' => 'error', 'message' => 'Cannot delete your own account']);
        }
        
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            Security::logSecurityEvent('user_deleted', ['deleted_user_id' => $id], $this->conn);
            $stmt->close();
            return 1;
        }
        
        $stmt->close();
        return 0;
    }

    /**
     * Save Loan Type
     */
    public function save_loan_type() {
        $id = Security::sanitizeInt($_POST['id'] ?? '');
        $type_name = Security::sanitizeString($_POST['type_name'] ?? '');
        $description = Security::sanitizeString($_POST['description'] ?? '');
        
        if (empty($id)) {
            $stmt = $this->conn->prepare("INSERT INTO loan_types (type_name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $type_name, $description);
        } else {
            $stmt = $this->conn->prepare("UPDATE loan_types SET type_name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $type_name, $description, $id);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        return $result ? 1 : 0;
    }

    /**
     * Delete Loan Type
     */
    public function delete_loan_type() {
        $id = Security::sanitizeInt($_POST['id'] ?? '');
        
        $stmt = $this->conn->prepare("DELETE FROM loan_types WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result ? 1 : 0;
    }

    /**
     * Save Loan Plan
     */
    public function save_plan() {
        $id = Security::sanitizeInt($_POST['id'] ?? '');
        $months = Security::sanitizeInt($_POST['months'] ?? 0);
        $interest_percentage = Security::sanitizeFloat($_POST['interest_percentage'] ?? 0);
        $penalty_rate = Security::sanitizeFloat($_POST['penalty_rate'] ?? 0);
        
        if (empty($id)) {
            $stmt = $this->conn->prepare("INSERT INTO loan_plan (months, interest_percentage, penalty_rate) VALUES (?, ?, ?)");
            $stmt->bind_param("idd", $months, $interest_percentage, $penalty_rate);
        } else {
            $stmt = $this->conn->prepare("UPDATE loan_plan SET months = ?, interest_percentage = ?, penalty_rate = ? WHERE id = ?");
            $stmt->bind_param("iddi", $months, $interest_percentage, $penalty_rate, $id);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        return $result ? 1 : 0;
    }

    /**
     * Delete Loan Plan
     */
    public function delete_plan() {
        $id = Security::sanitizeInt($_POST['id'] ?? '');
        
        $stmt = $this->conn->prepare("DELETE FROM loan_plan WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result ? 1 : 0;
    }

    /**
     * Save Borrower
     */
    public function save_borrower() {
        $id = Security::sanitizeInt($_POST['id'] ?? '');
        $firstname = Security::sanitizeString($_POST['firstname'] ?? '');
        $middlename = Security::sanitizeString($_POST['middlename'] ?? '');
        $lastname = Security::sanitizeString($_POST['lastname'] ?? '');
        $address = Security::sanitizeString($_POST['address'] ?? '');
        $contact_no = Security::sanitizeString($_POST['contact_no'] ?? '');
        $email = Security::sanitizeEmail($_POST['email'] ?? '');
        $tax_id = Security::sanitizeString($_POST['tax_id'] ?? '');
        
        if (empty($id)) {
            $stmt = $this->conn->prepare("INSERT INTO borrowers (firstname, middlename, lastname, address, contact_no, email, tax_id, date_created) VALUES (?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())");
            $stmt->bind_param("sssssss", $firstname, $middlename, $lastname, $address, $contact_no, $email, $tax_id);
        } else {
            $stmt = $this->conn->prepare("UPDATE borrowers SET firstname = ?, middlename = ?, lastname = ?, address = ?, contact_no = ?, email = ?, tax_id = ? WHERE id = ?");
            $stmt->bind_param("sssssssi", $firstname, $middlename, $lastname, $address, $contact_no, $email, $tax_id, $id);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        return $result ? 1 : 0;
    }

    /**
     * Delete Borrower
     */
    public function delete_borrower() {
        $id = Security::sanitizeInt($_POST['id'] ?? '');
        
        $stmt = $this->conn->prepare("DELETE FROM borrowers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result ? 1 : 0;
    }

    /**
     * Save Loan
     */
    public function save_loan() {
        $id = Security::sanitizeInt($_POST['id'] ?? '');
        $borrower_id = Security::sanitizeInt($_POST['borrower_id'] ?? 0);
        $loan_type_id = Security::sanitizeInt($_POST['loan_type_id'] ?? 0);
        $plan_id = Security::sanitizeInt($_POST['plan_id'] ?? 0);
        $amount = Security::sanitizeFloat($_POST['amount'] ?? 0);
        $purpose = Security::sanitizeString($_POST['purpose'] ?? '');
        $status = isset($_POST['status']) ? Security::sanitizeInt($_POST['status']) : null;
        
        // Gather additional loan parameters (interest, calculation type, loan_amount, duration)
        $interest_rate = Security::sanitizeFloat($_POST['interest_rate'] ?? 0);
        $calculation_type = in_array($_POST['calculation_type'] ?? 'simple', ['simple','compound']) ? $_POST['calculation_type'] : 'simple';
        $loan_amount = Security::sanitizeFloat($_POST['loan_amount'] ?? $amount);
        // If a plan is selected and duration not provided, fall back to plan months
        $duration_months = Security::sanitizeInt($_POST['duration_months'] ?? 0);
        if ($duration_months <= 0 && !empty($plan_id)) {
            $pstmt = $this->conn->prepare("SELECT months, interest_percentage FROM loan_plan WHERE id = ?");
            $pstmt->bind_param("i", $plan_id);
            $pstmt->execute();
            $plan_row = $pstmt->get_result()->fetch_assoc();
            $pstmt->close();
            if ($plan_row) {
                if ($duration_months <= 0) $duration_months = intval($plan_row['months']);
                if (empty($interest_rate)) $interest_rate = floatval($plan_row['interest_percentage']);
            }
        }
        if ($duration_months <= 0) $duration_months = 1;

        // Compute loan totals
        try {
            $calc = calculateLoan($loan_amount, $interest_rate ?: 18.0, $duration_months, $calculation_type);
        } catch(Exception $e) {
            // Fallback to simple calculation to avoid blocking save
            // Correctly convert annual interest rate to monthly
            $monthly_rate = $interest_rate / 12 / 100;
            $total_interest = $loan_amount * $monthly_rate * $duration_months;
            $total_payable = $loan_amount + $total_interest;

            $calc = [
                'total_interest' => round($total_interest, 2),
                'total_payable' => round($total_payable, 2),
                'monthly_installment' => round($total_payable / $duration_months, 2)
            ];
            $calc['interest_rate'] = $interest_rate;
            $calc['calculation_type'] = $calculation_type;
            $calc['months'] = $duration_months;
        }

        $total_interest = $calc['total_interest'];
        $total_payable = $calc['total_payable'];
        $monthly_installment = $calc['monthly_installment'];
        $outstanding_balance = $total_payable;

        if (empty($id)) {
            // Generate unique reference number
            $ref_no = mt_rand(10000000, 99999999);
            
            // Ensure uniqueness
            $stmt = $this->conn->prepare("SELECT id FROM loan_list WHERE ref_no = ?");
            $stmt->bind_param("s", $ref_no);
            $stmt->execute();
            while ($stmt->get_result()->num_rows > 0) {
                $ref_no = mt_rand(10000000, 99999999);
                $stmt->execute();
            }
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO loan_list (ref_no, borrower_id, loan_type_id, plan_id, amount, loan_amount, interest_rate, calculation_type, duration_months, total_interest, total_payable, monthly_installment, outstanding_balance, purpose) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siiidddsidddds", $ref_no, $borrower_id, $loan_type_id, $plan_id, $amount, $loan_amount, $interest_rate, $calculation_type, $duration_months, $total_interest, $total_payable, $monthly_installment, $outstanding_balance, $purpose);

        } else {
            if ($status !== null) {
                $stmt = $this->conn->prepare("UPDATE loan_list SET borrower_id = ?, loan_type_id = ?, plan_id = ?, amount = ?, loan_amount = ?, interest_rate = ?, calculation_type = ?, duration_months = ?, total_interest = ?, total_payable = ?, monthly_installment = ?, outstanding_balance = ?, purpose = ?, status = ? WHERE id = ?");
                $stmt->bind_param("iiidddsiddddsii", $borrower_id, $loan_type_id, $plan_id, $amount, $loan_amount, $interest_rate, $calculation_type, $duration_months, $total_interest, $total_payable, $monthly_installment, $outstanding_balance, $purpose, $status, $id);
                // Note: binding using a long type string in mysqli; the types line is split for readability

                // Generate loan schedules if releasing
                if ($status == 2) {
                    // ensure outstanding balance set to total payable
                    $outstanding_balance = $total_payable;
                    $stmt->execute();
                    $this->generateLoanSchedule($id, $plan_id);
                    return 1; // executed successfully above
                }
            } else {
                $stmt = $this->conn->prepare("UPDATE loan_list SET borrower_id = ?, loan_type_id = ?, plan_id = ?, amount = ?, loan_amount = ?, interest_rate = ?, calculation_type = ?, duration_months = ?, total_interest = ?, total_payable = ?, monthly_installment = ?, outstanding_balance = ?, purpose = ? WHERE id = ?");
                $stmt->bind_param("iiidddsiddddsi", $borrower_id, $loan_type_id, $plan_id, $amount, $loan_amount, $interest_rate, $calculation_type, $duration_months, $total_interest, $total_payable, $monthly_installment, $outstanding_balance, $purpose, $id);
            }
        }
        
        $result = $stmt->execute();
        $stmt->close();
        return $result ? 1 : 0;
    }

    /**
     * Generate loan payment schedule
     */
    private function generateLoanSchedule($loanId, $planId) {
        // Get plan months
        $stmt = $this->conn->prepare("SELECT months FROM loan_plan WHERE id = ?");
        $stmt->bind_param("i", $planId);
        $stmt->execute();
        $plan = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$plan) return;
        
        // Delete existing schedules
        $stmt = $this->conn->prepare("DELETE FROM loan_schedules WHERE loan_id = ?");
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        $stmt->close();
        
        // Create new schedules
        $stmt = $this->conn->prepare("INSERT INTO loan_schedules (loan_id, date_due) VALUES (?, ?)");
        
        for ($i = 1; $i <= $plan['months']; $i++) {
            $dueDate = date('Y-m-d', strtotime("+$i months"));
            $stmt->bind_param("is", $loanId, $dueDate);
            $stmt->execute();
        }
        
        $stmt->close();
        
        // Update release date
        $stmt = $this->conn->prepare("UPDATE loan_list SET date_released = NOW() WHERE id = ?");
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Delete Loan
     */
    public function delete_loan() {
        $id = Security::sanitizeInt($_POST['id'] ?? '');
        
        // Delete schedules first
        $stmt = $this->conn->prepare("DELETE FROM loan_schedules WHERE loan_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // Delete loan
        $stmt = $this->conn->prepare("DELETE FROM loan_list WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result ? 1 : 0;
    }

    /**
     * Save Payment
     */
    public function save_payment() {
        $id = Security::sanitizeInt($_POST['id'] ?? '');
        $loan_id = Security::sanitizeInt($_POST['loan_id'] ?? 0);
        $payee = Security::sanitizeString($_POST['payee'] ?? '');
        $amount = Security::sanitizeFloat($_POST['amount'] ?? 0);
        $penalty_amount = Security::sanitizeFloat($_POST['penalty_amount'] ?? 0);
        $overdue = Security::sanitizeInt($_POST['overdue'] ?? 0);
        
        if (empty($id)) {
            $stmt = $this->conn->prepare("INSERT INTO payments (loan_id, payee, amount, penalty_amount, overdue) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isddi", $loan_id, $payee, $amount, $penalty_amount, $overdue);
        } else {
            $stmt = $this->conn->prepare("UPDATE payments SET loan_id = ?, payee = ?, amount = ?, penalty_amount = ?, overdue = ? WHERE id = ?");
            $stmt->bind_param("isddii", $loan_id, $payee, $amount, $penalty_amount, $overdue, $id);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        
        Security::logSecurityEvent('payment_saved', ['loan_id' => $loan_id, 'amount' => $amount], $this->conn);
        
        return $result ? 1 : 0;
    }

    /**
     * Delete Payment
     */
    public function delete_payment() {
        $id = Security::sanitizeInt($_POST['id'] ?? '');
        
        $stmt = $this->conn->prepare("DELETE FROM payments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result ? 1 : 0;
    }

    /**
     * Change Password
     */
    public function change_password() {
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            return json_encode(['status' => 'error', 'message' => 'Invalid request token']);
        }
        
        $userId = $_SESSION['login_id'] ?? 0;
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword)) {
            return json_encode(['status' => 'error', 'message' => 'All fields are required']);
        }
        
        if ($newPassword !== $confirmPassword) {
            return json_encode(['status' => 'error', 'message' => 'New passwords do not match']);
        }
        
        if (strlen($newPassword) < 8) {
            return json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
        }
        
        // Verify current password
        $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$user) {
            return json_encode(['status' => 'error', 'message' => 'User not found']);
        }
        
        $isHashed = strpos($user['password'], '$2') === 0;
        $valid = $isHashed ? Security::verifyPassword($currentPassword, $user['password']) : ($user['password'] === $currentPassword);
        
        if (!$valid) {
            return json_encode(['status' => 'error', 'message' => 'Current password is incorrect']);
        }
        
        // Update password
        $hashedPassword = Security::hashPassword($newPassword);
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        if ($stmt->execute()) {
            Security::logSecurityEvent('password_changed', ['user_id' => $userId], $this->conn);
            $stmt->close();
            return json_encode(['status' => 'success', 'message' => 'Password changed successfully']);
        }
        
        $stmt->close();
        return json_encode(['status' => 'error', 'message' => 'Failed to change password']);
    }

    /**
     * Get loan review details
     */
    public function get_loan_review_details() {
        $loan_id = Security::sanitizeInt($_POST['loan_id'] ?? 0);

        // Get loan details
        $stmt = $this->conn->prepare("SELECT l.*, CONCAT(b.firstname, ' ', b.middlename, ' ', b.lastname) as customer_name,
                                  b.email, b.contact_no, b.address, b.tax_id,
                                  lt.type_name, lt.description as type_desc,
                                  lp.months, lp.interest_percentage, lp.penalty_rate,
                                  u.name as reviewed_by_name
                                  FROM loan_list l
                                  INNER JOIN borrowers b ON l.borrower_id = b.id
                                  LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
                                  LEFT JOIN loan_plan lp ON l.plan_id = lp.id
                                  LEFT JOIN users u ON l.reviewed_by = u.id
                                  WHERE l.id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_array();
        $stmt->close();

        // Get documents
        $stmt = $this->conn->prepare("SELECT * FROM borrower_documents WHERE borrower_id = ?");
        $stmt->bind_param("i", $loan['borrower_id']);
        $stmt->execute();
        $documents = $stmt->get_result();
        $stmt->close();

        // Get checklist
        $stmt = $this->conn->prepare("SELECT * FROM loan_application_checklist WHERE loan_id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $checklist = $stmt->get_result();
        $stmt->close();

        // Calculate loan details
        $principal = $loan['amount'];
        $interest = ($principal * $loan['interest_percentage']) / 100;
        $total = $principal + $interest;
        $monthly = $total / $loan['months'];

        $status_badges = array(
            0 => '<span class="badge badge-secondary">Draft</span>',
            1 => '<span class="badge badge-warning">Submitted</span>',
            2 => '<span class="badge badge-info">Under Review</span>',
            3 => '<span class="badge badge-success">Approved</span>',
            4 => '<span class="badge badge-danger">Denied</span>'
        );

        $doc_type_labels = array(
            'id' => 'Government ID',
            'employment_proof' => 'Employment Proof',
            'payslip' => 'Pay Slip'
        );

        ob_start();
        ?>

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">

                <!-- Application Status -->
                <div class="info-section">
                    <h6><i class="fa fa-info-circle"></i> Application Status</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Reference Number:</label>
                                <p><b><?php echo $loan['ref_no'] ?></b></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Current Status:</label>
                                <p><?php echo $status_badges[$loan['application_status']] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="info-section">
                    <h6><i class="fa fa-user"></i> Customer Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Full Name:</label>
                                <p><?php echo $loan['customer_name'] ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Email:</label>
                                <p><?php echo $loan['email'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loan Details -->
                <div class="info-section">
                    <h6><i class="fa fa-money-bill-wave"></i> Loan Details</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Loan Type:</label>
                                <p><b><?php echo $loan['type_name'] ?></b></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label>Amount:</label>
                                <p><b>K <?php echo number_format($principal, 2) ?></b></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calculation -->
                <div class="info-section" style="background: #fff3e0;">
                    <h6><i class="fa fa-calculator"></i> Payment Plan</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>Principal:</label>
                                <p>K <?php echo number_format($principal, 2) ?></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>Interest:</label>
                                <p>K <?php echo number_format($interest, 2) ?></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>Total:</label>
                                <p><b>K <?php echo number_format($total, 2) ?></b></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item">
                                <label>Monthly:</label>
                                <p><b>K <?php echo number_format($monthly, 2) ?></b></p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column - Checklist -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fa fa-tasks"></i> Review Checklist</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php while($item = $checklist->fetch_assoc()): ?>
                        <div class="checklist-item">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input checklist-checkbox"
                                       id="check_<?php echo $item['id'] ?>"
                                       data-id="<?php echo $item['id'] ?>"
                                       <?php echo $item['checked'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="check_<?php echo $item['id'] ?>">
                                    <?php echo $item['item'] ?>
                                </label>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <div class="text-right">
                <?php if($loan['application_status'] == 1): ?>
                    <button class="btn btn-info update-status-btn"
                            data-loan-id="<?php echo $loan_id ?>"
                            data-status="2"
                            data-status-text="Move to Under Review">
                        <i class="fa fa-search"></i> Move to Under Review
                    </button>
                <?php endif; ?>

                <?php if($loan['application_status'] <= 2): ?>
                    <button class="btn btn-success update-status-btn"
                            data-loan-id="<?php echo $loan_id ?>"
                            data-status="3"
                            data-status-text="Approve">
                        <i class="fa fa-check"></i> Approve Application
                    </button>
                    <button class="btn btn-danger update-status-btn"
                            data-loan-id="<?php echo $loan_id ?>"
                            data-status="4"
                            data-status-text="Deny">
                        <i class="fa fa-times"></i> Deny Application
                    </button>
                <?php endif; ?>

                <button class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Close
                </button>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Update document status
     */
    public function update_document_status() {
        $id = Security::sanitizeInt($_POST['id'] ?? 0);
        $status = Security::sanitizeInt($_POST['status'] ?? 0);

        // Validate status (should be 0 for pending, 1 for verified, 2 for rejected)
        if (!in_array($status, [0, 1, 2])) {
            return json_encode(['status' => 'error', 'message' => 'Invalid status value']);
        }

        $stmt = $this->conn->prepare("UPDATE borrower_documents SET status = ?, verification_date = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $status, $id);

        if ($stmt->execute()) {
            $stmt->close();

            // Get document info for notification
            $stmt = $this->conn->prepare("SELECT borrower_id, document_type FROM borrower_documents WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $doc = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($doc) {
                $borrower_id = $doc['borrower_id'];
                $doc_type = $doc['document_type'];

                $doc_type_labels = array(
                    'id' => 'Government ID',
                    'employment_proof' => 'Employment Proof',
                    'payslip' => 'Pay Slip'
                );

                $type_label = $doc_type_labels[$doc_type] ?? ucfirst(str_replace('_', ' ', $doc_type));

                if ($status == 1) {
                    $title = "Document Verified";
                    $message = "Your $type_label has been verified successfully.";
                    $type = "success";
                } else {
                    $title = "Document Status Updated";
                    $message = "Your $type_label status has been updated.";
                    $type = "info";
                }

                // Insert notification for customer
                $stmt = $this->conn->prepare("INSERT INTO customer_notifications (borrower_id, title, message, type) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $borrower_id, $title, $message, $type);
                $stmt->execute();
                $stmt->close();
            }

            return 1;
        }

        $stmt->close();
        return json_encode(['status' => 'error', 'message' => 'Failed to update document status']);
    }

    /**
     * Approve loan application
     */
    public function approve_loan_application() {
        $loan_id = Security::sanitizeInt($_POST['loan_id'] ?? 0);
        $interest_rate = Security::sanitizeFloat($_POST['interest_rate'] ?? 0);

        if($loan_id == 0 || $interest_rate == 0) {
            return json_encode(['status' => 'error', 'message' => 'Invalid loan ID or interest rate']);
        }

        // Get loan details
        $stmt = $this->conn->prepare("SELECT * FROM loan_list WHERE id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if(!$loan) {
            return json_encode(['status' => 'error', 'message' => 'Loan not found']);
        }

        // Recalculate loan with assigned interest rate
        try {
            $duration_months = $loan['duration_months'] ?? 1;

            $calc = calculateLoan($loan['amount'], $interest_rate, $duration_months, 'simple');

            $total_interest = $calc['total_interest'];
            $total_payable = $calc['total_payable'];
            $monthly_installment = $calc['monthly_installment'];

            // First, try to update with all columns (for newer schema)
            $stmt = $this->conn->prepare("UPDATE loan_list SET
                status = 1,
                interest_rate = ?,
                total_interest = ?,
                total_payable = ?,
                monthly_installment = ?,
                outstanding_balance = ?
                WHERE id = ?
            ");

            if($stmt === false) {
                // Some columns don't exist - use simpler update
                $stmt = $this->conn->prepare("UPDATE loan_list SET status = 1 WHERE id = ?");
                if($stmt === false) {
                    return json_encode(['status' => 'error', 'message' => 'SQL Error: ' . $this->conn->error]);
                }
                $stmt->bind_param("i", $loan_id);
            } else {
                $outstanding_balance = $total_payable;
                $stmt->bind_param("dddddi",
                    $interest_rate,
                    $total_interest,
                    $total_payable,
                    $monthly_installment,
                    $outstanding_balance,
                    $loan_id
                );
            }

            if(!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                return json_encode(['status' => 'error', 'message' => 'Database update failed: ' . $error]);
            }
            $stmt->close();

            // Create notification for customer (if table exists)
            try {
                $message = sprintf(
                    'Your loan application (Ref: %s) has been approved with %.1f%% interest rate. Total amount payable: %s over %d months.',
                    $loan['ref_no'],
                    $interest_rate,
                    formatCurrency($total_payable),
                    $duration_months
                );

                $stmt = $this->conn->prepare("INSERT INTO customer_notifications (borrower_id, title, message, type) VALUES (?, ?, ?, ?)");
                $title = 'Loan Approved';
                $type = 'success';
                $stmt->bind_param("isss",
                    $loan['borrower_id'],
                    $title,
                    $message,
                    $type
                );
                $stmt->execute();
                $stmt->close();
            } catch(Exception $e) {
                // Notification failed but loan was approved - continue
                error_log("Notification creation failed: " . $e->getMessage());
            }

            return 1; // Success

        } catch(Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Deny loan application
     */
    public function deny_loan_application() {
        $loan_id = Security::sanitizeInt($_POST['loan_id'] ?? 0);
        $denial_reason = Security::sanitizeString($_POST['denial_reason'] ?? 'Not specified');

        if($loan_id == 0) {
            return json_encode(['status' => 'error', 'message' => 'Invalid loan ID']);
        }

        // Get loan details
        $stmt = $this->conn->prepare("SELECT * FROM loan_list WHERE id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if(!$loan) {
            return json_encode(['status' => 'error', 'message' => 'Loan not found']);
        }

        // Update loan status to denied
        $stmt = $this->conn->prepare("UPDATE loan_list SET status = 4 WHERE id = ?");
        $stmt->bind_param("i", $loan_id);

        if(!$stmt->execute()) {
            $stmt->close();
            return json_encode(['status' => 'error', 'message' => 'Database update failed']);
        }
        $stmt->close();

        // Create notification for customer
        $stmt = $this->conn->prepare("INSERT INTO customer_notifications (borrower_id, title, message, type)
            VALUES (?, 'Loan Application Denied', 'Your loan application (Ref: ?) has been denied. Reason: ?', 'danger')");
        $ref_no = $loan['ref_no'];
        $stmt->bind_param("iss",
            $loan['borrower_id'],
            $ref_no,
            $denial_reason
        );
        $stmt->execute();
        $stmt->close();

        return 1; // Success
    }
}
