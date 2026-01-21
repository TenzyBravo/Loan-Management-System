-- Add missing columns to loan_list table for storing calculated loan values
ALTER TABLE loan_list 
ADD COLUMN IF NOT EXISTS total_interest DECIMAL(15,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS total_payable DECIMAL(15,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS monthly_installment DECIMAL(15,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS outstanding_balance DECIMAL(15,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS interest_rate DECIMAL(5,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS duration_months INT DEFAULT 1;
