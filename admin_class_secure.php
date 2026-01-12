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
                
                // Regenerate session ID to prevent fixation
                Security::regenerateSession();
                
                // Set session variables
                foreach ($user as $key => $value) {
                    if ($key !== 'password' && !is_numeric($key)) {
                        $_SESSION['login_' . $key] = $value;
                    }
                }
                
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
            $calc = [
                'total_interest' => round($loan_amount * ($interest_rate / 100) * $duration_months, 2),
                'total_payable' => round($loan_amount + ($loan_amount * ($interest_rate / 100) * $duration_months), 2),
                'monthly_installment' => round(($loan_amount + ($loan_amount * ($interest_rate / 100) * $duration_months)) / $duration_months, 2)
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
}
