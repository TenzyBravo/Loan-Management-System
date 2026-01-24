

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

--
-- Table structure for table `borrowers`
--

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

--
-- Dumping data for table `borrowers`
--

INSERT INTO `borrowers` (`id`, `firstname`, `middlename`, `lastname`, `contact_no`, `address`, `email`, `tax_id`, `username`, `password`, `status`, `email_verified`, `date_created`, `last_updated`, `updated_by`, `profile_complete`, `guarantor_name`, `guarantor_contact`, `guarantor_address`) VALUES
(9, 'Temwani', 'Z', 'Zgambo', '+260765731395', 'tr23', 'temwanizgambo@gmail.com', '303488/68/1', 'Temwani Zgambo', '$2y$10$/1GM3FerK1QSLn7aVl39Bu7/YzXwkYoyVv3HDps3aKVerPKrG2.9q', 1, 0, 1768766969, NULL, NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `borrower_documents`
--

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

--
-- Dumping data for table `borrower_documents`
--

INSERT INTO `borrower_documents` (`id`, `borrower_id`, `document_type`, `file_name`, `file_path`, `file_size`, `upload_date`, `status`, `verified_by`, `verification_date`, `verification_notes`) VALUES
(1, 3, 'id', 'id_1765533688_693be7f813381.png', 'assets/uploads/customer_documents/id_1765533688_693be7f813381.png', 555986, '2025-12-12 12:01:28', 1, NULL, NULL, NULL),
(2, 3, 'employment_proof', 'employment_proof_1765533688_693be7f813bec.jpeg', 'assets/uploads/customer_documents/employment_proof_1765533688_693be7f813bec.jpeg', 135147, '2025-12-12 12:01:28', 1, NULL, NULL, NULL),
(3, 3, 'payslip', 'payslip_1765533688_693be7f814241.png', 'assets/uploads/customer_documents/payslip_1765533688_693be7f814241.png', 555986, '2025-12-12 12:01:28', 1, NULL, NULL, NULL),
(4, 5, 'id', 'id_1765794881_693fe441d7b28.png', 'assets/uploads/customer_documents/id_1765794881_693fe441d7b28.png', 555986, '2025-12-15 12:34:41', 0, NULL, NULL, NULL),
(5, 5, 'employment_proof', 'employment_proof_1765794881_693fe441d8782.png', 'assets/uploads/customer_documents/employment_proof_1765794881_693fe441d8782.png', 555986, '2025-12-15 12:34:41', 0, NULL, NULL, NULL),
(6, 5, 'payslip', 'payslip_1765794881_693fe441dd10f.jpeg', 'assets/uploads/customer_documents/payslip_1765794881_693fe441dd10f.jpeg', 69735, '2025-12-15 12:34:41', 0, NULL, NULL, NULL),
(7, 6, 'id', 'id_1765883437_69413e2d8436a.pdf', 'assets/uploads/customer_documents/id_1765883437_69413e2d8436a.pdf', 678714, '2025-12-16 13:10:37', 0, NULL, NULL, NULL),
(8, 6, 'employment_proof', 'employment_proof_1765883437_69413e2d84db2.png', 'assets/uploads/customer_documents/employment_proof_1765883437_69413e2d84db2.png', 555986, '2025-12-16 13:10:37', 0, NULL, NULL, NULL),
(9, 6, 'payslip', 'payslip_1765883437_69413e2d8521b.jpeg', 'assets/uploads/customer_documents/payslip_1765883437_69413e2d8521b.jpeg', 69735, '2025-12-16 13:10:37', 0, NULL, NULL, NULL),
(10, 7, 'id', 'id_1768205046_6964aaf631e62.jpg', 'assets/uploads/customer_documents/id_1768205046_6964aaf631e62.jpg', 113272, '2026-01-12 10:04:06', 0, NULL, NULL, NULL),
(11, 7, 'employment_proof', 'employment_proof_1768205046_6964aaf63361a.pdf', 'assets/uploads/customer_documents/employment_proof_1768205046_6964aaf63361a.pdf', 147961, '2026-01-12 10:04:06', 0, NULL, NULL, NULL),
(12, 7, 'payslip', 'payslip_1768205046_6964aaf634297.pdf', 'assets/uploads/customer_documents/payslip_1768205046_6964aaf634297.pdf', 222915, '2026-01-12 10:04:06', 0, NULL, NULL, NULL),
(13, 8, 'id', 'id_1768423600_696800b0c5e29.pdf', 'assets/uploads/customer_documents/id_1768423600_696800b0c5e29.pdf', 54688, '2026-01-14 22:46:40', 0, NULL, NULL, NULL),
(14, 8, 'employment_proof', 'employment_proof_1768423600_696800b0c678d.pdf', 'assets/uploads/customer_documents/employment_proof_1768423600_696800b0c678d.pdf', 42132, '2026-01-14 22:46:40', 0, NULL, NULL, NULL),
(15, 8, 'payslip', 'payslip_1768423600_696800b0c6ab0.png', 'assets/uploads/customer_documents/payslip_1768423600_696800b0c6ab0.png', 142111, '2026-01-14 22:46:40', 0, NULL, NULL, NULL),
(16, 9, 'id', 'id_1768766969_696d3df9d611f.pdf', 'assets/uploads/customer_documents/id_1768766969_696d3df9d611f.pdf', 54688, '2026-01-18 22:09:29', 0, NULL, NULL, NULL),
(17, 9, 'employment_proof', 'employment_proof_1768766969_696d3df9d6c3b.pdf', 'assets/uploads/customer_documents/employment_proof_1768766969_696d3df9d6c3b.pdf', 42132, '2026-01-18 22:09:29', 0, NULL, NULL, NULL),
(18, 9, 'payslip', 'payslip_1768766969_696d3df9d7512.pdf', 'assets/uploads/customer_documents/payslip_1768766969_696d3df9d7512.pdf', 519478, '2026-01-18 22:09:29', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_notifications`
--

CREATE TABLE `customer_notifications` (
  `id` int(30) NOT NULL,
  `borrower_id` int(30) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'info' COMMENT 'info, success, warning, error',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_notifications`
--

INSERT INTO `customer_notifications` (`id`, `borrower_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 3, 'Welcome!', 'Your account has been created successfully. Your documents are being reviewed by our team.', 'success', 0, '2025-12-12 12:01:28'),
(2, 3, 'Document Verified', 'Your Government ID has been verified successfully.', 'success', 0, '2025-12-12 15:56:44'),
(3, 3, 'Document Verified', 'Your Employment Proof has been verified successfully.', 'success', 0, '2025-12-12 15:56:52'),
(4, 3, 'Document Verified', 'Your Pay Slip has been verified successfully.', 'success', 0, '2025-12-12 15:56:57'),
(5, 3, 'Application Submitted', 'Your loan application (Ref: 74556035) has been submitted successfully. Our team will review it shortly.', 'success', 0, '2025-12-12 21:11:33'),
(6, 3, 'Application Submitted', 'Your loan application (Ref: 23934504) has been submitted successfully. Our team will review it shortly.', 'success', 0, '2025-12-12 21:11:45'),
(7, 3, 'Application Submitted', 'Your loan application (Ref: 51955533) has been submitted successfully. Our team will review it shortly.', 'success', 0, '2025-12-12 21:12:57'),
(8, 3, 'Under Review', 'Your application (Ref: 51955533) is under review.', 'info', 0, '2025-12-12 21:28:06'),
(9, 3, 'Denied', 'Your application (Ref: 23934504) was denied.', 'error', 0, '2025-12-12 21:28:59'),
(10, 3, 'Approved!', 'Your application (Ref: 74556035) has been approved!', 'success', 0, '2025-12-12 21:29:18'),
(11, 5, 'Welcome!', 'Your account has been created successfully. Your documents are being reviewed by our team.', 'success', 0, '2025-12-15 12:34:41'),
(12, 6, 'Welcome!', 'Your account has been created successfully. Your documents are being reviewed by our team.', 'success', 0, '2025-12-16 13:10:37'),
(13, 6, 'Application Submitted', 'Your loan application (Ref: 97671027) has been submitted successfully. Our team will review it shortly.', 'success', 0, '2025-12-16 13:58:04'),
(14, 7, 'Welcome!', 'Your account has been created successfully. Your documents are being reviewed by our team.', 'success', 0, '2026-01-12 10:04:06'),
(15, 7, 'Application Submitted', 'Your loan application (Ref: 35560111) has been submitted successfully. Our team will review it shortly.', 'success', 0, '2026-01-12 10:09:11'),
(16, 8, 'Welcome!', 'Your account has been created successfully. Your documents are being reviewed by our team.', 'success', 0, '2026-01-14 22:46:40'),
(17, 9, 'Welcome!', 'Your account has been created successfully. Your documents are being reviewed by our team.', 'success', 0, '2026-01-18 22:09:29'),
(18, 9, 'Application Submitted', 'Your loan application (Ref: 48572443) has been submitted successfully. Our team will review it shortly.', 'success', 0, '2026-01-18 22:13:22'),
(19, 9, 'Application Submitted', 'Your loan application (Ref: 33243509) has been submitted successfully. Our team will review it shortly.', 'success', 0, '2026-01-21 10:32:34'),
(20, 9, 'Application Submitted', 'Your loan application (Ref: 57480385) has been submitted successfully. Our team will review it shortly.', 'success', 0, '2026-01-21 10:32:34');

-- --------------------------------------------------------

--
-- Table structure for table `document_history`
--

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

--
-- Table structure for table `loan_application_checklist`
--

CREATE TABLE `loan_application_checklist` (
  `id` int(30) NOT NULL,
  `loan_id` int(30) NOT NULL,
  `item` varchar(200) NOT NULL,
  `checked` tinyint(1) DEFAULT 0,
  `checked_by` int(30) DEFAULT NULL,
  `checked_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_application_checklist`
--

INSERT INTO `loan_application_checklist` (`id`, `loan_id`, `item`, `checked`, `checked_by`, `checked_date`, `notes`) VALUES
(36, 10, 'Identity Verification - ID Document Checked', 0, NULL, NULL, NULL),
(37, 10, 'Employment Verification - Proof of Employment Verified', 0, NULL, NULL, NULL),
(38, 10, 'Income Verification - Pay Slip Reviewed', 0, NULL, NULL, NULL),
(39, 10, 'Credit History Check', 0, NULL, NULL, NULL),
(40, 10, 'Loan Amount Feasibility Assessment', 0, NULL, NULL, NULL),
(41, 10, 'Purpose of Loan Evaluation', 0, NULL, NULL, NULL),
(42, 10, 'Repayment Capacity Analysis', 0, NULL, NULL, NULL),
(43, 11, 'Identity Verification - ID Document Checked', 0, NULL, NULL, NULL),
(44, 11, 'Employment Verification - Proof of Employment Verified', 0, NULL, NULL, NULL),
(45, 11, 'Income Verification - Pay Slip Reviewed', 0, NULL, NULL, NULL),
(46, 11, 'Credit History Check', 0, NULL, NULL, NULL),
(47, 11, 'Loan Amount Feasibility Assessment', 0, NULL, NULL, NULL),
(48, 11, 'Purpose of Loan Evaluation', 0, NULL, NULL, NULL),
(49, 11, 'Repayment Capacity Analysis', 0, NULL, NULL, NULL),
(50, 12, 'Identity Verification - ID Document Checked', 0, NULL, NULL, NULL),
(51, 12, 'Employment Verification - Proof of Employment Verified', 0, NULL, NULL, NULL),
(52, 12, 'Income Verification - Pay Slip Reviewed', 0, NULL, NULL, NULL),
(53, 12, 'Credit History Check', 0, NULL, NULL, NULL),
(54, 12, 'Loan Amount Feasibility Assessment', 0, NULL, NULL, NULL),
(55, 12, 'Purpose of Loan Evaluation', 0, NULL, NULL, NULL),
(56, 12, 'Repayment Capacity Analysis', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `loan_installments`
--

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

--
-- Table structure for table `loan_list`
--

--
-- LOAN LIST TABLE
-- Business Rules:
--   - 1-month loans: 18% TOTAL interest (auto-applied)
--   - Multi-month loans: Admin assigns TOTAL interest rate (10-40%)
--   - All interest rates are TOTAL interest, NOT annual rates
--
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

--
-- Dumping data for table `loan_list`
--

-- Sample loan data
-- Note: For 1-month loans, interest_rate=18% is auto-applied
-- Calculations: total_interest = amount * interest_rate%, total_payable = amount + total_interest
INSERT INTO `loan_list` (`id`, `ref_no`, `loan_type_id`, `borrower_id`, `purpose`, `amount`, `plan_id`, `status`, `date_released`, `date_created`, `application_source`, `application_status`, `reviewed_by`, `review_date`, `review_notes`, `denial_reason`, `interest_rate`, `total_interest`, `total_payable`, `monthly_installment`, `outstanding_balance`, `duration_months`) VALUES
(10, '48572443', 4, 9, 'Personal expenses', 1000, 1, 4, NULL, '2026-01-20 21:02:10', 'customer', 4, NULL, NULL, NULL, 'Insufficient documentation', 0.00, 0.00, 0.00, 0.00, 0.00, 1),
(11, '33243509', 4, 9, 'Business capital', 5000, 1, 0, NULL, '2026-01-21 10:32:34', 'customer', 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 1),
(12, '57480385', 4, 9, 'Education fees', 5000, 1, 0, NULL, '2026-01-21 10:32:34', 'customer', 1, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `loan_plan`
--

CREATE TABLE `loan_plan` (
  `id` int(30) NOT NULL,
  `months` int(11) NOT NULL,
  `interest_percentage` float NOT NULL COMMENT 'TOTAL interest percentage (not annual)',
  `penalty_rate` int(11) NOT NULL COMMENT 'Penalty rate for overdue payments',
  `calculation_type` varchar(20) DEFAULT 'simple' COMMENT 'simple = TOTAL interest applied to principal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_plan`
-- Note: interest_percentage is TOTAL interest (not annual)
-- 1-month loans: 18% TOTAL interest (auto-applied)
-- Multi-month loans: Admin assigns TOTAL rate (10-40%)
--

INSERT INTO `loan_plan` (`id`, `months`, `interest_percentage`, `penalty_rate`, `calculation_type`) VALUES
(1, 1, 18, 5, 'simple');

-- --------------------------------------------------------

--
-- Table structure for table `loan_schedules`
--

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

--
-- Stand-in structure for view `loan_summary_view`
-- (See below for the actual view)
-- Note: interest_rate is TOTAL interest percentage (not annual)
--
CREATE TABLE `loan_summary_view` (
`id` int(30)
,`ref_no` varchar(50)
,`client_name` varchar(303)
,`guarantor_name` varchar(255)
,`contact_no` varchar(30)
,`amount_given` double
,`date_created` datetime
,`interest_rate` decimal(5,2) COMMENT 'TOTAL interest rate'
,`cash_interest` decimal(15,2) COMMENT 'Total interest amount'
,`total_payable` decimal(15,2)
,`total_paid` double
,`balance_remaining` double
,`calculation_type` varchar(20)
,`status` tinyint(1)
,`duration_months` int(11)
,`monthly_installment` decimal(15,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `loan_types`
--

CREATE TABLE `loan_types` (
  `id` int(30) NOT NULL,
  `type_name` text NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_types`
--

INSERT INTO `loan_types` (`id`, `type_name`, `description`) VALUES
(1, 'Small Business', 'Small Business Loans'),
(2, 'Mortgages', 'Mortgages'),
(3, 'Personal Loans', 'Personal Loans'),
(4, 'Student Loan', 'School');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(30) NOT NULL,
  `borrower_id` int(30) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

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

--
-- Table structure for table `users`
--

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

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `doctor_id`, `name`, `address`, `contact`, `username`, `password`, `type`, `can_approve_loans`, `can_verify_documents`, `can_edit_customers`) VALUES
(1, 0, 'Administrator', '', '', 'admin', '$2y$12$p1hwL5SW8kS924aL0vIQy.MsfMdnLcUxOs0QvDA4y9DJzxL3jqYI2', 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Structure for view `loan_summary_view`
--
DROP TABLE IF EXISTS `loan_summary_view`;

-- Updated view to use TOTAL interest from loan_list (not from loan_plan)
-- Interest is now stored directly on the loan record after approval
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `loan_summary_view` AS
SELECT
    `l`.`id` AS `id`,
    `l`.`ref_no` AS `ref_no`,
    CONCAT(`b`.`lastname`, ', ', `b`.`firstname`, ' ', `b`.`middlename`) AS `client_name`,
    `b`.`guarantor_name` AS `guarantor_name`,
    `b`.`contact_no` AS `contact_no`,
    `l`.`amount` AS `amount_given`,
    `l`.`date_created` AS `date_created`,
    `l`.`interest_rate` AS `interest_rate`,
    `l`.`total_interest` AS `cash_interest`,
    `l`.`total_payable` AS `total_payable`,
    COALESCE((SELECT SUM(`payments`.`amount`) FROM `payments` WHERE `payments`.`loan_id` = `l`.`id`), 0) AS `total_paid`,
    `l`.`total_payable` - COALESCE((SELECT SUM(`payments`.`amount`) FROM `payments` WHERE `payments`.`loan_id` = `l`.`id`), 0) AS `balance_remaining`,
    'simple' AS `calculation_type`,
    `l`.`status` AS `status`,
    `l`.`duration_months` AS `duration_months`,
    `l`.`monthly_installment` AS `monthly_installment`
FROM `loan_list` `l`
JOIN `borrowers` `b` ON `l`.`borrower_id` = `b`.`id`;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `target_id` (`target_id`),
  ADD KEY `idx_action_date` (`action`,`created_at`);

--
-- Indexes for table `borrowers`
--
ALTER TABLE `borrowers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `borrower_documents`
--
ALTER TABLE `borrower_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrower_id` (`borrower_id`),
  ADD KEY `idx_borrower_doc` (`borrower_id`,`document_type`);

--
-- Indexes for table `customer_notifications`
--
ALTER TABLE `customer_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrower_id` (`borrower_id`),
  ADD KEY `idx_borrower_read` (`borrower_id`,`is_read`);

--
-- Indexes for table `document_history`
--
ALTER TABLE `document_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrower_id` (`borrower_id`);

--
-- Indexes for table `loan_application_checklist`
--
ALTER TABLE `loan_application_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `loan_installments`
--
ALTER TABLE `loan_installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `loan_list`
--
ALTER TABLE `loan_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application_status` (`application_status`,`borrower_id`);

--
-- Indexes for table `loan_plan`
--
ALTER TABLE `loan_plan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loan_schedules`
--
ALTER TABLE `loan_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `idx_loan_due` (`loan_id`, `date_due`);

--
-- Indexes for table `loan_types`
--
ALTER TABLE `loan_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrower_id` (`borrower_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `idx_loan_date` (`loan_id`, `date_created`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `borrowers`
--
ALTER TABLE `borrowers`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `borrower_documents`
--
ALTER TABLE `borrower_documents`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `customer_notifications`
--
ALTER TABLE `customer_notifications`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `document_history`
--
ALTER TABLE `document_history`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_application_checklist`
--
ALTER TABLE `loan_application_checklist`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `loan_installments`
--
ALTER TABLE `loan_installments`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_list`
--
ALTER TABLE `loan_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `loan_plan`
--
ALTER TABLE `loan_plan`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `loan_schedules`
--
ALTER TABLE `loan_schedules`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `loan_types`
--
ALTER TABLE `loan_types`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrower_documents`
--
ALTER TABLE `borrower_documents`
  ADD CONSTRAINT `fk_borrower_documents_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `borrowers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_notifications`
--
ALTER TABLE `customer_notifications`
  ADD CONSTRAINT `fk_notifications_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `borrowers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_list`
--
ALTER TABLE `loan_list`
  ADD CONSTRAINT `fk_loan_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `borrowers` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_loan_type` FOREIGN KEY (`loan_type_id`) REFERENCES `loan_types` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `loan_schedules`
--
ALTER TABLE `loan_schedules`
  ADD CONSTRAINT `fk_schedule_loan` FOREIGN KEY (`loan_id`) REFERENCES `loan_list` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_application_checklist`
--
ALTER TABLE `loan_application_checklist`
  ADD CONSTRAINT `fk_checklist_loan` FOREIGN KEY (`loan_id`) REFERENCES `loan_list` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_installments`
--
ALTER TABLE `loan_installments`
  ADD CONSTRAINT `fk_installment_loan` FOREIGN KEY (`loan_id`) REFERENCES `loan_list` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_loan` FOREIGN KEY (`loan_id`) REFERENCES `loan_list` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_reset_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `borrowers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_history`
--
ALTER TABLE `document_history`
  ADD CONSTRAINT `fk_doc_history_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `borrowers` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
