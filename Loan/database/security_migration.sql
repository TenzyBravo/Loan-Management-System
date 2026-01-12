-- =====================================================
-- SECURITY ENHANCEMENTS MIGRATION
-- Run this script to add security features to your database
-- =====================================================

-- 1. Create audit log table for tracking security events
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `user_agent` text,
    `details` text,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create login attempts table for rate limiting
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ip_address` varchar(45) NOT NULL,
    `username` varchar(100) DEFAULT NULL,
    `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `success` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_ip_address` (`ip_address`),
    KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create password reset tokens table
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `token` varchar(100) NOT NULL,
    `expires_at` datetime NOT NULL,
    `used_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_token` (`token`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Add last_login column to users table if not exists
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `last_login` datetime DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `login_count` int(11) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `password_changed_at` datetime DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) DEFAULT 1;

-- 5. Create system settings table for security configurations
CREATE TABLE IF NOT EXISTS `security_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text,
    `description` text,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default security settings
INSERT INTO `security_settings` (`setting_key`, `setting_value`, `description`) VALUES
('max_login_attempts', '5', 'Maximum failed login attempts before lockout'),
('lockout_duration', '15', 'Lockout duration in minutes'),
('session_timeout', '30', 'Session timeout in minutes'),
('password_min_length', '8', 'Minimum password length'),
('require_special_char', '0', 'Require special characters in password'),
('require_uppercase', '0', 'Require uppercase letters in password'),
('require_number', '1', 'Require numbers in password')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- 6. Create a trigger to log password changes
DELIMITER //
DROP TRIGGER IF EXISTS `log_password_change`//
CREATE TRIGGER `log_password_change` 
AFTER UPDATE ON `users`
FOR EACH ROW
BEGIN
    IF OLD.password != NEW.password THEN
        INSERT INTO `audit_log` (`user_id`, `action`, `ip_address`, `details`)
        VALUES (NEW.id, 'password_changed', 'system', JSON_OBJECT('user_id', NEW.id));
        
        UPDATE `users` SET `password_changed_at` = NOW() WHERE id = NEW.id;
    END IF;
END//
DELIMITER ;

-- 7. Add indexes for better performance
ALTER TABLE `loan_list` 
ADD INDEX IF NOT EXISTS `idx_status` (`status`),
ADD INDEX IF NOT EXISTS `idx_borrower_id` (`borrower_id`),
ADD INDEX IF NOT EXISTS `idx_date_created` (`date_created`);

ALTER TABLE `payments`
ADD INDEX IF NOT EXISTS `idx_loan_id` (`loan_id`),
ADD INDEX IF NOT EXISTS `idx_date_created` (`date_created`);

ALTER TABLE `borrowers`
ADD INDEX IF NOT EXISTS `idx_email` (`email`);

-- =====================================================
-- PASSWORD MIGRATION SCRIPT
-- This updates existing plain-text passwords to bcrypt hashes
-- Run this AFTER updating to the new secure login system
-- =====================================================

-- Note: This needs to be run from PHP since bcrypt is not available in MySQL
-- See the password_migration.php script

-- =====================================================
-- VERIFICATION QUERIES
-- Run these to verify the migration was successful
-- =====================================================

-- Check if audit_log table exists
-- SELECT COUNT(*) as audit_log_exists FROM information_schema.tables WHERE table_name = 'audit_log';

-- Check if security_settings are in place
-- SELECT * FROM security_settings;

-- Check users table has new columns
-- DESCRIBE users;
