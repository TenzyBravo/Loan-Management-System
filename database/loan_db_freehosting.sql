

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `activity_log` (
  `id` int(30) NOT NULL,
  `user_id` int(30) DEFAULT NULL,
  `user_type` varchar(20) NOT NULL COMMENT 'admin, staff, customer',
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `target_id` int(30) DEFAULT NULL COMMENT 'ID of affected record',
  `target_type` varchar(50) DEFAULT NULL COMMENT 'loan, document, borrower, etc',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `borrowers` (
  `id` int(30) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `contact_no` varchar(30) NOT NULL,
  `address` text NOT NULL,
  `email` varchar(50) NOT NULL,
  `tax_id` varchar(50) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
  `email_verified` tinyint(1) DEFAULT 0,
  `date_created` int(11) NOT NULL,
  `last_updated` datetime DEFAULT NULL,
  `updated_by` int(30) DEFAULT NULL COMMENT 'User or self',
  `profile_complete` tinyint(1) DEFAULT 0 COMMENT '0=incomplete, 1=complete',
  `guarantor_name` varchar(255) DEFAULT NULL COMMENT 'Name of guarantor',
  `guarantor_contact` varchar(100) DEFAULT NULL COMMENT 'Guarantor contact number',
  `guarantor_address` text DEFAULT NULL COMMENT 'Guarantor address'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `borrower_documents` (
  `id` int(30) NOT NULL,
  `borrower_id` int(30) NOT NULL,
  `document_type` varchar(50) NOT NULL COMMENT 'id, employment_proof, payslip',
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `upload_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` tinyint(1) DEFAULT 0 COMMENT '0=pending, 1=verified, 2=rejected',
  `verified_by` int(30) DEFAULT NULL COMMENT 'Admin/staff user ID',
  `verification_date` datetime DEFAULT NULL,
  `verification_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `customer_notifications` (
  `id` int(30) NOT NULL,
  `borrower_id` int(30) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'info' COMMENT 'info, success, warning, error',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `document_history` (
  `id` int(30) NOT NULL,
  `borrower_id` int(30) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `old_file_path` varchar(255) NOT NULL,
  `new_file_path` varchar(255) NOT NULL,
  `reason` text DEFAULT NULL,
  `updated_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `loan_application_checklist` (
  `id` int(30) NOT NULL,
  `loan_id` int(30) NOT NULL,
  `item` varchar(200) NOT NULL,
  `checked` tinyint(1) DEFAULT 0,
  `checked_by` int(30) DEFAULT NULL,
  `checked_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `loan_installments` (
  `id` int(30) NOT NULL,
  `loan_id` int(30) NOT NULL,
  `installment_number` int(11) NOT NULL COMMENT '1, 2, 3, 4...',
  `amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0 COMMENT '0=pending, 1=paid',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `loan_list` (
  `id` int(30) NOT NULL,
  `ref_no` varchar(50) NOT NULL,
  `loan_type_id` int(30) NOT NULL,
  `borrower_id` int(30) NOT NULL,
  `purpose` text NOT NULL,
  `amount` double NOT NULL COMMENT 'Principal loan amount',
  `plan_id` int(30) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=pending, 1=approved, 2=released, 3=completed, 4=denied',
  `date_released` datetime DEFAULT NULL COMMENT 'Date loan was disbursed',
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `application_source` varchar(20) DEFAULT 'admin' COMMENT 'admin or customer',
  `application_status` tinyint(1) DEFAULT 0 COMMENT '0=draft, 1=submitted, 2=under_review, 3=approved, 4=denied',
  `reviewed_by` int(30) DEFAULT NULL COMMENT 'User ID of admin/staff who reviewed',
  `review_date` datetime DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `denial_reason` text DEFAULT NULL,
  `interest_rate` decimal(5,2) DEFAULT 0.00 COMMENT 'TOTAL interest rate (not annual) - set by admin or 18% for 1-month',
  `total_interest` decimal(15,2) DEFAULT 0.00 COMMENT 'Principal * interest_rate%',
  `total_payable` decimal(15,2) DEFAULT 0.00 COMMENT 'Principal + Total Interest',
  `monthly_installment` decimal(15,2) DEFAULT 0.00 COMMENT 'Total Payable / Duration Months',
  `outstanding_balance` decimal(15,2) DEFAULT 0.00 COMMENT 'Remaining balance to be paid',
  `duration_months` int(11) DEFAULT 1 COMMENT 'Loan duration in months'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `loan_plan` (
  `id` int(30) NOT NULL,
  `months` int(11) NOT NULL,
  `interest_percentage` float NOT NULL COMMENT 'TOTAL interest percentage (not annual)',
  `penalty_rate` int(11) NOT NULL COMMENT 'Penalty rate for overdue payments',
  `calculation_type` varchar(20) DEFAULT 'simple' COMMENT 'simple = TOTAL interest applied to principal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `loan_plan` (`id`, `months`, `interest_percentage`, `penalty_rate`, `calculation_type`) VALUES
(1, 1, 18, 5, 'simple');

-- --------------------------------------------------------

CREATE TABLE `loan_schedules` (
  `id` int(30) NOT NULL,
  `loan_id` int(30) NOT NULL,
  `installment_no` int(11) NOT NULL DEFAULT 1 COMMENT 'Installment number (1, 2, 3...)',
  `amount_due` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Monthly installment amount',
  `date_due` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=pending, 1=paid, 2=overdue',
  `paid_date` date DEFAULT NULL,
  `paid_amount` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `loan_types` (
  `id` int(30) NOT NULL,
  `type_name` text NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `loan_types` (`id`, `type_name`, `description`) VALUES
(1, 'Small Business', 'Small Business Loans'),
(2, 'Mortgages', 'Mortgages'),
(3, 'Personal Loans', 'Personal Loans'),
(4, 'Student Loan', 'School');

-- --------------------------------------------------------

CREATE TABLE `password_resets` (
  `id` int(30) NOT NULL,
  `borrower_id` int(30) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `payments` (
  `id` int(30) NOT NULL,
  `loan_id` int(30) NOT NULL,
  `payee` text NOT NULL,
  `amount` float NOT NULL DEFAULT 0,
  `penalty_amount` float NOT NULL DEFAULT 0,
  `overdue` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=no , 1 = yes',
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(30) NOT NULL,
  `doctor_id` int(30) NOT NULL,
  `name` varchar(200) NOT NULL,
  `address` text NOT NULL,
  `contact` text NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(200) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 2 COMMENT '1=admin , 2 = staff',
  `can_approve_loans` tinyint(1) DEFAULT 0 COMMENT 'Permission to approve/deny loans',
  `can_verify_documents` tinyint(1) DEFAULT 0 COMMENT 'Permission to verify documents',
  `can_edit_customers` tinyint(1) DEFAULT 0 COMMENT 'Permission to edit customer info'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default admin user (password: admin123)
INSERT INTO `users` (`id`, `doctor_id`, `name`, `address`, `contact`, `username`, `password`, `type`, `can_approve_loans`, `can_verify_documents`, `can_edit_customers`) VALUES
(1, 0, 'Administrator', '', '', 'admin', '$2y$12$p1hwL5SW8kS924aL0vIQy.MsfMdnLcUxOs0QvDA4y9DJzxL3jqYI2', 1, 1, 1, 1);

-- --------------------------------------------------------
-- INDEXES
-- --------------------------------------------------------

ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `target_id` (`target_id`),
  ADD KEY `idx_action_date` (`action`,`created_at`);

ALTER TABLE `borrowers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

ALTER TABLE `borrower_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrower_id` (`borrower_id`),
  ADD KEY `idx_borrower_doc` (`borrower_id`,`document_type`);

ALTER TABLE `customer_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrower_id` (`borrower_id`),
  ADD KEY `idx_borrower_read` (`borrower_id`,`is_read`);

ALTER TABLE `document_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrower_id` (`borrower_id`);

ALTER TABLE `loan_application_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

ALTER TABLE `loan_installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

ALTER TABLE `loan_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_status` (`application_status`,`borrower_id`);

ALTER TABLE `loan_plan`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `loan_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `idx_loan_due` (`loan_id`, `date_due`);

ALTER TABLE `loan_types`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrower_id` (`borrower_id`);

ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `idx_loan_date` (`loan_id`, `date_created`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

-- --------------------------------------------------------
-- AUTO_INCREMENT
-- --------------------------------------------------------

ALTER TABLE `activity_log`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

ALTER TABLE `borrowers`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

ALTER TABLE `borrower_documents`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

ALTER TABLE `customer_notifications`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

ALTER TABLE `document_history`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

ALTER TABLE `loan_application_checklist`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

ALTER TABLE `loan_installments`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

ALTER TABLE `loan_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

ALTER TABLE `loan_plan`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `loan_schedules`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

ALTER TABLE `loan_types`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `password_resets`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

ALTER TABLE `payments`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

COMMIT;
