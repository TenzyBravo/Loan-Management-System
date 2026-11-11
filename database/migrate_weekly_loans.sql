-- ============================================================
-- Weekly loans + collateral (effective 1 November 2026)
--
-- Run ONCE in phpMyAdmin (SQL tab) BEFORE deploying the code that uses it.
-- Safe to re-run: every statement checks whether the column already exists.
-- Requires MariaDB 10.0.2+ (Hostinger default) for "ADD COLUMN IF NOT EXISTS".
--
-- Nothing existing is changed: all current loans get term_unit = 'month',
-- so they keep their original terms, rates and due dates.
-- ============================================================

ALTER TABLE `loan_list`
  ADD COLUMN IF NOT EXISTS `term_unit` VARCHAR(5) NOT NULL DEFAULT 'month'
    COMMENT 'month or week; duration_months holds the number of these units',
  ADD COLUMN IF NOT EXISTS `collateral_description` TEXT NULL
    COMMENT 'Collateral offered by the borrower (required for new applications)',
  ADD COLUMN IF NOT EXISTS `collateral_value` DECIMAL(15,2) NULL
    COMMENT 'Estimated collateral value in Kwacha (optional)';

-- Check: every existing loan should now show term_unit = 'month'
SELECT term_unit, COUNT(*) AS loans FROM `loan_list` GROUP BY term_unit;
