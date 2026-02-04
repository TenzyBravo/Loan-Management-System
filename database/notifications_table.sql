-- Admin Notifications Table
-- Run this in phpMyAdmin to add admin notifications

CREATE TABLE IF NOT EXISTS `admin_notifications` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL COMMENT 'loan_application, document_upload, payment, etc.',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_id` int(30) DEFAULT NULL COMMENT 'ID of related record (loan_id, borrower_id, etc.)',
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'loan, borrower, payment, etc.',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Make sure customer_notifications table exists with correct structure
CREATE TABLE IF NOT EXISTS `customer_notifications` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `borrower_id` int(30) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'loan_approved, loan_denied, payment_received, etc.',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_id` int(30) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_borrower_id` (`borrower_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fix auto-increment if needed
ALTER TABLE `admin_notifications` MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;
ALTER TABLE `customer_notifications` MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;
