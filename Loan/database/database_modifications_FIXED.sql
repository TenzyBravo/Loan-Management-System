-- Database Modifications for Customer Portal (FIXED VERSION)
-- This version checks if columns exist before adding them

-- 1. Add login credentials to borrowers table (only if they don't exist)
-- Check and add username
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'borrowers' 
AND COLUMN_NAME = 'username';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE `borrowers` ADD COLUMN `username` VARCHAR(100) DEFAULT NULL AFTER `tax_id`', 
    'SELECT "Column username already exists" AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add password
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'borrowers' 
AND COLUMN_NAME = 'password';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE `borrowers` ADD COLUMN `password` VARCHAR(255) DEFAULT NULL AFTER `username`', 
    'SELECT "Column password already exists" AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add status
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'borrowers' 
AND COLUMN_NAME = 'status';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE `borrowers` ADD COLUMN `status` TINYINT(1) DEFAULT 1 COMMENT "0=inactive, 1=active" AFTER `password`', 
    'SELECT "Column status already exists" AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add email_verified
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'borrowers' 
AND COLUMN_NAME = 'email_verified';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE `borrowers` ADD COLUMN `email_verified` TINYINT(1) DEFAULT 0 AFTER `status`', 
    'SELECT "Column email_verified already exists" AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Create documents table (only if doesn't exist)
CREATE TABLE IF NOT EXISTS `borrower_documents` (
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

-- 3. Create customer notifications table (only if doesn't exist)
CREATE TABLE IF NOT EXISTS `customer_notifications` (
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

-- 4. Create password reset tokens table (only if doesn't exist)
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT(30) NOT NULL AUTO_INCREMENT,
  `borrower_id` INT(30) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `borrower_id` (`borrower_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Add unique constraint to username (only if doesn't exist)
SET @index_exists = (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'borrowers' 
    AND INDEX_NAME = 'username'
);

SET @query = IF(@index_exists = 0, 
    'ALTER TABLE `borrowers` ADD UNIQUE KEY `username` (`username`)', 
    'SELECT "Index username already exists" AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Sample customer account (only if doesn't exist)
INSERT INTO `borrowers` (`firstname`, `middlename`, `lastname`, `contact_no`, `address`, `email`, `tax_id`, `username`, `password`, `status`, `date_created`) 
SELECT 'Jane', 'M', 'Doe', '+1234567890', '456 Oak Street, City', 'jane.doe@example.com', '987654-32', 'janedoe', 'customer123', 1, UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `borrowers` WHERE username = 'janedoe');

-- 7. Add indexes for better performance (only if don't exist)
SET @index_exists = (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'borrower_documents' 
    AND INDEX_NAME = 'idx_borrower_doc'
);

SET @query = IF(@index_exists = 0, 
    'ALTER TABLE `borrower_documents` ADD INDEX `idx_borrower_doc` (`borrower_id`, `document_type`)', 
    'SELECT "Index idx_borrower_doc already exists" AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customer_notifications' 
    AND INDEX_NAME = 'idx_borrower_read'
);

SET @query = IF(@index_exists = 0, 
    'ALTER TABLE `customer_notifications` ADD INDEX `idx_borrower_read` (`borrower_id`, `is_read`)', 
    'SELECT "Index idx_borrower_read already exists" AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
