-- Add 'deleted' status to users table
-- Fix SQLSTATE data truncation error when deleting users

ALTER TABLE `users` 
MODIFY COLUMN `status` enum('active','inactive','pending','suspended','deleted') NOT NULL DEFAULT 'pending';
