-- User Account Management Tables
-- Migration for full user account CRUD functionality

-- User Addresses Table
CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `address_type` ENUM('billing','shipping','both') NOT NULL DEFAULT 'both',
  `full_name` VARCHAR(100),
  `phone` VARCHAR(50),
  `address_line1` VARCHAR(255) NOT NULL,
  `address_line2` VARCHAR(255),
  `city` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(50) NOT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'US',
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Payment Methods Table (Stripe integration)
CREATE TABLE IF NOT EXISTS `user_payment_methods` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `stripe_payment_method_id` VARCHAR(255) NOT NULL,
  `type` ENUM('card','bank_account','mobile_money') NOT NULL DEFAULT 'card',
  `brand` VARCHAR(50),
  `last4` VARCHAR(4),
  `exp_month` INT,
  `exp_year` INT,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_is_default` (`is_default`),
  UNIQUE KEY `unique_payment_method` (`stripe_payment_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Preferences Table
CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `language` VARCHAR(10) DEFAULT 'en',
  `currency` VARCHAR(10) DEFAULT 'USD',
  `timezone` VARCHAR(50) DEFAULT 'UTC',
  `marketing_opt_in` TINYINT(1) DEFAULT 1,
  `email_notifications` TINYINT(1) DEFAULT 1,
  `sms_notifications` TINYINT(1) DEFAULT 0,
  `push_notifications` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_prefs` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wallet Transactions Table (if not exists - enhanced version)
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('credit','debit','transfer_in','transfer_out') NOT NULL,
  `amount` DECIMAL(18,2) NOT NULL,
  `balance_after` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `reference` VARCHAR(100) NULL,
  `description` TEXT NULL,
  `meta` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wallets Table (if not exists)
CREATE TABLE IF NOT EXISTS `wallets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `balance` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_wallet` (`user_id`, `currency`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Tracking Enhancement - Add tracking fields if not exist
ALTER TABLE `orders` 
  ADD COLUMN IF NOT EXISTS `tracking_number` VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS `courier` VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS `expected_delivery` DATE NULL,
  ADD COLUMN IF NOT EXISTS `tracking_url` VARCHAR(500) NULL;

-- Add indexes for better performance
ALTER TABLE `orders`
  ADD INDEX IF NOT EXISTS `idx_tracking_number` (`tracking_number`),
  ADD INDEX IF NOT EXISTS `idx_user_status` (`user_id`, `status`);
