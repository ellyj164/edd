-- API Subscriptions Migration
-- This migration adds support for tiered API subscription model
-- - Sandbox: Free for all developers
-- - Live API: $150/month subscription required
-- - Admin accounts: Free live access

-- Create api_subscriptions table
CREATE TABLE IF NOT EXISTS `api_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subscription_type` enum('sandbox','live','government','enterprise') NOT NULL DEFAULT 'sandbox',
  `status` enum('active','cancelled','expired','suspended','trial') NOT NULL DEFAULT 'trial',
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `billing_cycle` enum('monthly','yearly','lifetime') NOT NULL DEFAULT 'monthly',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `current_period_start` timestamp NULL DEFAULT NULL,
  `current_period_end` timestamp NULL DEFAULT NULL,
  `next_billing_date` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_subscription` (`user_id`, `subscription_type`),
  KEY `idx_subscription_status` (`status`, `subscription_type`),
  KEY `idx_next_billing` (`next_billing_date`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_api_subscription_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create api_subscription_invoices table
CREATE TABLE IF NOT EXISTS `api_subscription_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `invoice_number` varchar(50) NOT NULL,
  `billing_period_start` timestamp NULL DEFAULT NULL,
  `billing_period_end` timestamp NULL DEFAULT NULL,
  `due_date` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `invoice_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_invoice_number` (`invoice_number`),
  KEY `subscription_id` (`subscription_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_invoice_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  CONSTRAINT `fk_invoice_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `api_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add subscription_id to api_keys to link them to subscriptions
ALTER TABLE `api_keys`
ADD COLUMN IF NOT EXISTS `subscription_id` int(11) DEFAULT NULL AFTER `user_id`,
ADD COLUMN IF NOT EXISTS `environment` enum('sandbox','live','government') NOT NULL DEFAULT 'sandbox' AFTER `subscription_id`,
ADD INDEX IF NOT EXISTS `idx_api_keys_subscription` (`subscription_id`),
ADD CONSTRAINT `fk_api_key_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `api_subscriptions` (`id`) ON DELETE SET NULL;

-- Create government_api_access table for special government endpoints
CREATE TABLE IF NOT EXISTS `government_api_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_name` varchar(255) NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `api_key_id` int(11) NOT NULL,
  `access_level` enum('read_only','analytics_only','full') NOT NULL DEFAULT 'read_only',
  `allowed_endpoints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_endpoints`)),
  `ip_whitelist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ip_whitelist`)),
  `status` enum('pending','approved','active','suspended','revoked') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `api_key_id` (`api_key_id`),
  KEY `idx_gov_status` (`status`),
  CONSTRAINT `fk_gov_api_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gov_api_key` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create api_usage_metrics table for tracking API usage per subscription
CREATE TABLE IF NOT EXISTS `api_usage_metrics` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `api_key_id` int(11) NOT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `status_code` int(11) NOT NULL,
  `response_time_ms` int(11) DEFAULT NULL,
  `request_size_bytes` int(11) DEFAULT NULL,
  `response_size_bytes` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `api_key_id` (`api_key_id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `idx_usage_date` (`created_at`),
  KEY `idx_usage_endpoint` (`endpoint`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add default sandbox subscription for all existing users with API keys
INSERT INTO `api_subscriptions` (`user_id`, `subscription_type`, `status`, `monthly_fee`, `current_period_start`, `current_period_end`)
SELECT DISTINCT `user_id`, 'sandbox', 'active', 0.00, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR)
FROM `api_keys`
WHERE `user_id` IS NOT NULL
AND NOT EXISTS (
  SELECT 1 FROM `api_subscriptions` 
  WHERE `api_subscriptions`.`user_id` = `api_keys`.`user_id` 
  AND `api_subscriptions`.`subscription_type` = 'sandbox'
);

-- Give admin users free live API access
INSERT INTO `api_subscriptions` (`user_id`, `subscription_type`, `status`, `monthly_fee`, `current_period_start`, `current_period_end`)
SELECT `id`, 'live', 'active', 0.00, NOW(), DATE_ADD(NOW(), INTERVAL 10 YEAR)
FROM `users`
WHERE `role` = 'admin'
AND NOT EXISTS (
  SELECT 1 FROM `api_subscriptions` 
  WHERE `api_subscriptions`.`user_id` = `users`.`id` 
  AND `api_subscriptions`.`subscription_type` = 'live'
);

-- Add comments for documentation
ALTER TABLE `api_subscriptions` 
COMMENT = 'API subscription plans - sandbox (free), live ($150/month), government (special access)';

ALTER TABLE `api_subscription_invoices` 
COMMENT = 'Invoices for API subscription payments';

ALTER TABLE `government_api_access` 
COMMENT = 'Special government API access with restricted permissions';

ALTER TABLE `api_usage_metrics` 
COMMENT = 'API usage tracking for analytics and billing';
