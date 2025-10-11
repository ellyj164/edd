-- Migration: Link-based Email Verification System
-- Replaces OTP-based verification with secure token links

-- Add is_verified column to users table if it doesn't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `is_verified` TINYINT(1) DEFAULT 0 AFTER `email_verified_at`;

-- Create or replace email_verifications table
DROP TABLE IF EXISTS `email_verifications`;

CREATE TABLE `email_verifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(255) NOT NULL UNIQUE,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `verified_at` DATETIME NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_token` (`token`),
  INDEX `idx_created_at` (`created_at`),
  CONSTRAINT `fk_email_verifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
