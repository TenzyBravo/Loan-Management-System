-- Migration: add loan financial fields
ALTER TABLE loan_list
  ADD COLUMN interest_rate DECIMAL(5,2) NOT NULL DEFAULT 18.00,
  ADD COLUMN calculation_type ENUM('simple','compound') NOT NULL DEFAULT 'simple',
  ADD COLUMN loan_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN duration_months INT NOT NULL DEFAULT 0,
  ADD COLUMN total_interest DECIMAL(12,2) NULL DEFAULT NULL,
  ADD COLUMN total_payable DECIMAL(12,2) NULL DEFAULT NULL,
  ADD COLUMN monthly_installment DECIMAL(12,2) NULL DEFAULT NULL,
  ADD COLUMN outstanding_balance DECIMAL(12,2) NULL DEFAULT NULL;

-- Optional: index for performance
CREATE INDEX idx_loan_status ON loan_list (status);
