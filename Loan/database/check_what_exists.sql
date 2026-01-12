-- Quick Check: Run this in phpMyAdmin SQL tab to see what already exists
-- This will tell you what you need to add

-- Check 1: Check borrowers table columns
SELECT 
    COLUMN_NAME,
    IF(COLUMN_NAME IN ('username', 'password', 'status', 'email_verified'), 'EXISTS ✓', 'MISSING ✗') AS Status
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'borrowers'
AND COLUMN_NAME IN ('firstname', 'lastname', 'email', 'tax_id', 'username', 'password', 'status', 'email_verified')
ORDER BY COLUMN_NAME;

-- Check 2: Check if new tables exist
SELECT 
    'borrower_documents' AS Table_Name,
    IF(COUNT(*) > 0, 'EXISTS ✓', 'MISSING ✗') AS Status
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'borrower_documents'
UNION ALL
SELECT 
    'customer_notifications' AS Table_Name,
    IF(COUNT(*) > 0, 'EXISTS ✓', 'MISSING ✗') AS Status
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'customer_notifications'
UNION ALL
SELECT 
    'password_resets' AS Table_Name,
    IF(COUNT(*) > 0, 'EXISTS ✓', 'MISSING ✗') AS Status
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'password_resets';

-- Check 3: Show all tables in database
SHOW TABLES;
