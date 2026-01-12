-- Database Modifications for Customer Portal
-- Run these SQL queries in phpMyAdmin after importing the original database

-- 1. Add login credentials to borrowers table
ALTER TABLE `borrowers` 
ADD COLUMN `username` VARCHAR(100) DEFAULT NULL AFTER `tax_id`,
ADD COLUMN `password` VARCHAR(255) DEFAULT NULL AFTER `username`,
ADD COLUMN `status` TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active' AFTER `password`,
ADD COLUMN `email_verified` TINYINT(1) DEFAULT 0 AFTER `status`;

-- 2. Create documents table for uploaded files
CREATE TABLE `borrower_documents` (
  `id` INT(30) NOT NULL AUTO_INCREMENT,
  `borrower_id` INT(30) NOT NULL,
  `document_type` VARCHAR(50) NOT NULL COMMENT 'id, employment_proof, payslip',
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT(11) NOT NULL,
  `upload_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` TINYINT(1) DEFAULT 0 COMMENT '0=pending, 1=verified, 2=rejected',
  PRIMARY KEY (`id`),
  KEY `borrower_id` (`borrower_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Modify loan_list table to track application source
ALTER TABLE `loan_list` 
ADD COLUMN `application_source` VARCHAR(20) DEFAULT 'admin' COMMENT 'admin or customer' AFTER `date_created`;

-- 4. Create customer notifications table
CREATE TABLE `customer_notifications` (
  `id` INT(30) NOT NULL AUTO_INCREMENT,
  `borrower_id` INT(30) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) DEFAULT 'info' COMMENT 'info, success, warning, error',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `borrower_id` (`borrower_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Add unique constraint to username
ALTER TABLE `borrowers` 
ADD UNIQUE KEY `username` (`username`);

-- 6. Sample customer account (for testing)
-- Password: customer123 (You should hash this in production!)
INSERT INTO `borrowers` (`firstname`, `middlename`, `lastname`, `contact_no`, `address`, `email`, `tax_id`, `username`, `password`, `status`, `date_created`) 
VALUES ('Jane', 'M', 'Doe', '+1234567890', '456 Oak Street, City', 'jane.doe@example.com', '987654-32', 'janedoe', 'customer123', 1, UNIX_TIMESTAMP());

-- 7. Create password reset tokens table (for future use)
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

-- 8. Add indexes for better performance
ALTER TABLE `borrower_documents` 
ADD INDEX `idx_borrower_doc` (`borrower_id`, `document_type`);

ALTER TABLE `customer_notifications` 
ADD INDEX `idx_borrower_read` (`borrower_id`, `is_read`);
