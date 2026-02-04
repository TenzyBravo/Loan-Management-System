-- Fix auto-increment issues for free hosting
-- Run this in phpMyAdmin

-- Fix loan_schedules table
ALTER TABLE `loan_schedules` MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

-- Fix payments table
ALTER TABLE `payments` MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

-- Fix loan_list table
ALTER TABLE `loan_list` MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

-- Fix loan_installments table
ALTER TABLE `loan_installments` MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

-- Fix activity_log table
ALTER TABLE `activity_log` MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

-- Fix customer_notifications table
ALTER TABLE `customer_notifications` MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

-- Fix loan_application_checklist table
ALTER TABLE `loan_application_checklist` MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

-- If there's a record with ID 0, delete it
DELETE FROM `loan_schedules` WHERE `id` = 0;
DELETE FROM `payments` WHERE `id` = 0;
