-- ========================================
-- Seller Settings Tables Migration
-- Creates tables for managing seller settings
-- ========================================

-- Seller Payment Information
CREATE TABLE IF NOT EXISTS `seller_payment_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `payment_method` enum('bank_transfer','paypal','mobile_money','other') NOT NULL DEFAULT 'bank_transfer',
  `bank_name` varchar(100) DEFAULT NULL,
  `account_holder_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `routing_number` varchar(50) DEFAULT NULL,
  `swift_code` varchar(20) DEFAULT NULL,
  `paypal_email` varchar(100) DEFAULT NULL,
  `mobile_money_provider` varchar(50) DEFAULT NULL,
  `mobile_money_number` varchar(50) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  CONSTRAINT `fk_payment_info_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seller Shipping Settings
CREATE TABLE IF NOT EXISTS `seller_shipping_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `carrier_name` varchar(100) NOT NULL,
  `shipping_zone` varchar(100) DEFAULT 'Domestic',
  `base_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `per_item_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `free_shipping_threshold` decimal(10,2) DEFAULT NULL,
  `estimated_delivery_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_shipping_settings_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seller Tax Settings
CREATE TABLE IF NOT EXISTS `seller_tax_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `tax_type` enum('VAT','GST','Sales Tax','Other') NOT NULL DEFAULT 'VAT',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_region` varchar(100) DEFAULT NULL,
  `tax_id_number` varchar(50) DEFAULT NULL,
  `apply_to_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `is_inclusive` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_tax_settings_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Store Appearance Settings
CREATE TABLE IF NOT EXISTS `store_appearance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `store_logo` varchar(255) DEFAULT NULL,
  `store_banner` varchar(255) DEFAULT NULL,
  `theme_color` varchar(7) DEFAULT '#3b82f6',
  `theme_name` varchar(50) DEFAULT 'default',
  `custom_css` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vendor` (`vendor_id`),
  CONSTRAINT `fk_store_appearance_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Store Policies
CREATE TABLE IF NOT EXISTS `store_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `return_policy` text DEFAULT NULL,
  `refund_policy` text DEFAULT NULL,
  `exchange_policy` text DEFAULT NULL,
  `shipping_policy` text DEFAULT NULL,
  `privacy_policy` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vendor` (`vendor_id`),
  CONSTRAINT `fk_store_policies_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notification Settings
CREATE TABLE IF NOT EXISTS `notification_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `email_new_order` tinyint(1) NOT NULL DEFAULT 1,
  `email_order_shipped` tinyint(1) NOT NULL DEFAULT 1,
  `email_order_delivered` tinyint(1) NOT NULL DEFAULT 1,
  `email_customer_message` tinyint(1) NOT NULL DEFAULT 1,
  `email_product_review` tinyint(1) NOT NULL DEFAULT 1,
  `email_low_stock` tinyint(1) NOT NULL DEFAULT 1,
  `email_payout_completed` tinyint(1) NOT NULL DEFAULT 1,
  `email_weekly_summary` tinyint(1) NOT NULL DEFAULT 0,
  `email_monthly_report` tinyint(1) NOT NULL DEFAULT 0,
  `sms_new_order` tinyint(1) NOT NULL DEFAULT 0,
  `sms_urgent_alerts` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vendor` (`vendor_id`),
  CONSTRAINT `fk_notification_settings_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Account Closure Requests
CREATE TABLE IF NOT EXISTS `account_closure_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `additional_comments` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_closure_request_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
