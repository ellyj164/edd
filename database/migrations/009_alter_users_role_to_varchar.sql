-- Migration to change users.role from ENUM to VARCHAR(50)
-- This fixes the "Data truncated for column 'role'" warning

ALTER TABLE `users` 
MODIFY COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'customer';

-- Update existing values to ensure compatibility
UPDATE `users` SET `role` = 'customer' WHERE `role` NOT IN ('customer', 'vendor', 'admin');
