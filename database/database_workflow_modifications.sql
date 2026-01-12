-- Additional Database Modifications for Loan Application Workflow
-- Run this AFTER the first database_modifications.sql

-- 1. Add application tracking fields to loan_list table
ALTER TABLE `loan_list` 
ADD COLUMN `application_status` TINYINT(1) DEFAULT 0 COMMENT '0=draft, 1=submitted, 2=under_review, 3=approved, 4=denied' AFTER `application_source`,
ADD COLUMN `reviewed_by` INT(30) DEFAULT NULL COMMENT 'User ID of admin/staff who reviewed' AFTER `application_status`,
ADD COLUMN `review_date` DATETIME DEFAULT NULL AFTER `reviewed_by`,
ADD COLUMN `review_notes` TEXT DEFAULT NULL AFTER `review_date`,
ADD COLUMN `denial_reason` TEXT DEFAULT NULL AFTER `review_notes`;

-- 2. Add edit tracking to borrowers table
ALTER TABLE `borrowers`
ADD COLUMN `last_updated` DATETIME DEFAULT NULL AFTER `date_created`,
ADD COLUMN `updated_by` INT(30) DEFAULT NULL COMMENT 'User or self' AFTER `last_updated`,
ADD COLUMN `profile_complete` TINYINT(1) DEFAULT 0 COMMENT '0=incomplete, 1=complete' AFTER `updated_by`;

-- 3. Create document history table for tracking updates
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

-- 4. Create activity log table for tracking all actions
CREATE TABLE `activity_log` (
  `id` INT(30) NOT NULL AUTO_INCREMENT,
  `user_id` INT(30) DEFAULT NULL,
  `user_type` VARCHAR(20) NOT NULL COMMENT 'admin, staff, customer',
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `target_id` INT(30) DEFAULT NULL COMMENT 'ID of affected record',
  `target_type` VARCHAR(50) DEFAULT NULL COMMENT 'loan, document, borrower, etc',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `target_id` (`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Add document verification notes
ALTER TABLE `borrower_documents`
ADD COLUMN `verified_by` INT(30) DEFAULT NULL COMMENT 'Admin/staff user ID' AFTER `status`,
ADD COLUMN `verification_date` DATETIME DEFAULT NULL AFTER `verified_by`,
ADD COLUMN `verification_notes` TEXT DEFAULT NULL AFTER `verification_date`;

-- 6. Create loan application checklist table
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

-- 7. Update users table to differentiate roles better
ALTER TABLE `users`
ADD COLUMN `can_approve_loans` TINYINT(1) DEFAULT 0 COMMENT 'Permission to approve/deny loans' AFTER `type`,
ADD COLUMN `can_verify_documents` TINYINT(1) DEFAULT 0 COMMENT 'Permission to verify documents' AFTER `can_approve_loans`,
ADD COLUMN `can_edit_customers` TINYINT(1) DEFAULT 0 COMMENT 'Permission to edit customer info' AFTER `can_verify_documents`;

-- 8. Set admin permissions
UPDATE `users` SET 
  `can_approve_loans` = 1,
  `can_verify_documents` = 1,
  `can_edit_customers` = 1
WHERE `type` = 1;

-- 9. Create indexes for better performance
ALTER TABLE `activity_log` 
ADD INDEX `idx_action_date` (`action`, `created_at`);

ALTER TABLE `loan_list`
ADD INDEX `idx_application_status` (`application_status`, `borrower_id`);

-- 10. Sample staff user with limited permissions
INSERT INTO `users` (`doctor_id`, `name`, `address`, `contact`, `username`, `password`, `type`, `can_approve_loans`, `can_verify_documents`, `can_edit_customers`) 
VALUES (0, 'Staff User', '', '', 'staff', 'staff123', 2, 0, 1, 0);

-- Sample: Staff can verify documents but cannot approve loans
