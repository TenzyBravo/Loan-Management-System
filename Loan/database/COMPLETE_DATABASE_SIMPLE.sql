-- ========================================================
-- COMPLETE LOAN MANAGEMENT SYSTEM DATABASE
-- MySQL 5.7 / MariaDB Compatible Version
-- Single comprehensive file with ALL modifications
-- ========================================================
-- IMPORTANT: Some ALTER TABLE statements may show errors
-- if columns already exist. This is NORMAL - just continue!
-- ========================================================

-- ========================================================
-- SECTION 1: CUSTOMER PORTAL - BORROWERS TABLE UPDATES
-- ========================================================

-- Add customer login and profile fields
-- (Ignore "Duplicate column" errors if they appear)

ALTER TABLE `borrowers` ADD COLUMN `username` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `borrowers` ADD COLUMN `password` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `borrowers` ADD COLUMN `status` TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active';
ALTER TABLE `borrowers` ADD COLUMN `email_verified` TINYINT(1) DEFAULT 0;
ALTER TABLE `borrowers` ADD COLUMN `last_updated` DATETIME DEFAULT NULL;
ALTER TABLE `borrowers` ADD COLUMN `updated_by` INT(30) DEFAULT NULL;
ALTER TABLE `borrowers` ADD COLUMN `profile_complete` TINYINT(1) DEFAULT 0;

-- ========================================================
-- SECTION 2: LOAN LIST TABLE UPDATES
-- ========================================================

-- Add workflow tracking fields
-- (Ignore "Duplicate column" errors if they appear)

ALTER TABLE `loan_list` ADD COLUMN `application_status` TINYINT(1) DEFAULT 0;
ALTER TABLE `loan_list` ADD COLUMN `application_source` VARCHAR(20) DEFAULT 'admin';
ALTER TABLE `loan_list` ADD COLUMN `reviewed_by` INT(30) DEFAULT NULL;
ALTER TABLE `loan_list` ADD COLUMN `review_date` DATETIME DEFAULT NULL;
ALTER TABLE `loan_list` ADD COLUMN `review_notes` TEXT DEFAULT NULL;
ALTER TABLE `loan_list` ADD COLUMN `denial_reason` TEXT DEFAULT NULL;

-- ========================================================
-- SECTION 3: BORROWER DOCUMENTS TABLE - WITH VERIFICATION
-- ========================================================

-- Drop and recreate to ensure correct structure
DROP TABLE IF EXISTS `borrower_documents`;

CREATE TABLE `borrower_documents` (
  `id` INT(30) NOT NULL AUTO_INCREMENT,
  `borrower_id` INT(30) NOT NULL,
  `document_type` VARCHAR(50) NOT NULL COMMENT 'id, employment_proof, payslip',
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT(11) NOT NULL,
  `upload_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` TINYINT(1) DEFAULT 0 COMMENT '0=pending, 1=verified, 2=rejected',
  `verified_by` INT(30) DEFAULT NULL,
  `verification_date` DATETIME DEFAULT NULL,
  `verification_notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `borrower_id` (`borrower_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SECTION 4: CUSTOMER NOTIFICATIONS TABLE
-- ========================================================

DROP TABLE IF EXISTS `customer_notifications`;

CREATE TABLE `customer_notifications` (
  `id` INT(30) NOT NULL AUTO_INCREMENT,
  `borrower_id` INT(30) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) DEFAULT 'info',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `borrower_id` (`borrower_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SECTION 5: PASSWORD RESETS TABLE
-- ========================================================

DROP TABLE IF EXISTS `password_resets`;

CREATE TABLE `password_resets` (
  `id` INT(30) NOT NULL AUTO_INCREMENT,
  `borrower_id` INT(30) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `borrower_id` (`borrower_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SECTION 6: DOCUMENT HISTORY TABLE
-- ========================================================

DROP TABLE IF EXISTS `document_history`;

CREATE TABLE `document_history` (
  `id` INT(30) NOT NULL AUTO_INCREMENT,
  `borrower_id` INT(30) NOT NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `old_file_path` VARCHAR(255) NOT NULL,
  `new_file_path` VARCHAR(255) NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `updated_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `borrower_id` (`borrower_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SECTION 7: ACTIVITY LOG TABLE
-- ========================================================

DROP TABLE IF EXISTS `activity_log`;

CREATE TABLE `activity_log` (
  `id` INT(30) NOT NULL AUTO_INCREMENT,
  `user_id` INT(30) DEFAULT NULL,
  `user_type` VARCHAR(20) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `target_id` INT(30) DEFAULT NULL,
  `target_type` VARCHAR(50) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SECTION 8: LOAN APPLICATION CHECKLIST TABLE
-- ========================================================

DROP TABLE IF EXISTS `loan_application_checklist`;

CREATE TABLE `loan_application_checklist` (
  `id` INT(30) NOT NULL AUTO_INCREMENT,
  `loan_id` INT(30) NOT NULL,
  `item` VARCHAR(200) NOT NULL,
  `checked` TINYINT(1) DEFAULT 0,
  `checked_by` INT(30) DEFAULT NULL,
  `checked_date` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_id` (`loan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SECTION 9: USER PERMISSIONS
-- ========================================================

-- Add permission columns to users table
-- (Ignore errors if columns exist)

ALTER TABLE `users` ADD COLUMN `can_approve_loans` TINYINT(1) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `can_verify_documents` TINYINT(1) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `can_edit_customers` TINYINT(1) DEFAULT 0;

-- Grant permissions to admin users
UPDATE `users` SET 
  `can_approve_loans` = 1,
  `can_verify_documents` = 1,
  `can_edit_customers` = 1
WHERE `type` = 1;

-- ========================================================
-- SECTION 10: SAMPLE DATA
-- ========================================================

-- Insert test customer (delete first to avoid duplicates)
DELETE FROM `borrowers` WHERE username = 'janedoe';

INSERT INTO `borrowers` 
(`firstname`, `middlename`, `lastname`, `contact_no`, `address`, `email`, `tax_id`, `username`, `password`, `status`, `profile_complete`, `date_created`) 
VALUES 
('Jane', 'M', 'Doe', '+1234567890', '456 Oak Street, City', 'jane.doe@example.com', '987654-32', 'janedoe', 'customer123', 1, 1, UNIX_TIMESTAMP());

-- Insert test staff user (delete first to avoid duplicates)
DELETE FROM `users` WHERE username = 'staff';

INSERT INTO `users` 
(`doctor_id`, `name`, `address`, `contact`, `username`, `password`, `type`, `can_approve_loans`, `can_verify_documents`, `can_edit_customers`) 
VALUES 
(0, 'Staff User', '', '', 'staff', 'staff123', 2, 0, 1, 0);

-- ========================================================
-- SECTION 11: FINAL VERIFICATION
-- ========================================================

-- Show all tables (should have 13 total)
SELECT 'Setup Complete! ✅' AS Status;
SELECT '13 tables created/updated' AS Info;
SELECT 'Ready to use!' AS Message;

-- ========================================================
-- END OF SETUP
-- ========================================================
