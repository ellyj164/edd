-- Migration: AI Features and Digital Products
-- Created: 2025
-- Description: Creates tables for AI recommendations, digital products, and customer downloads

-- Table: user_product_views
-- Tracks user browsing history for AI recommendations
CREATE TABLE IF NOT EXISTS `user_product_views` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `view_duration` int(11) DEFAULT 0 COMMENT 'Time spent viewing in seconds',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: digital_products
-- Stores metadata for digital/downloadable products
CREATE TABLE IF NOT EXISTS `digital_products` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL COMMENT 'Size in bytes',
  `file_type` varchar(100) DEFAULT NULL,
  `version` varchar(50) DEFAULT '1.0',
  `download_limit` int(11) DEFAULT NULL COMMENT 'NULL = unlimited',
  `expiry_days` int(11) DEFAULT NULL COMMENT 'Days after purchase before link expires',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_version` (`product_id`, `version`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: customer_downloads
-- Tracks customer downloads and enforces limits
CREATE TABLE IF NOT EXISTS `customer_downloads` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `digital_product_id` bigint(20) UNSIGNED NOT NULL,
  `download_token` varchar(255) NOT NULL,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `download_limit` int(11) DEFAULT NULL COMMENT 'NULL = unlimited',
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_downloaded_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_download_token` (`download_token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_digital_product_id` (`digital_product_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraints
ALTER TABLE `user_product_views`
  ADD CONSTRAINT `fk_upv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_upv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

ALTER TABLE `digital_products`
  ADD CONSTRAINT `fk_dp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

ALTER TABLE `customer_downloads`
  ADD CONSTRAINT `fk_cd_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cd_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cd_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cd_digital_product` FOREIGN KEY (`digital_product_id`) REFERENCES `digital_products` (`id`) ON DELETE CASCADE;

-- Add is_digital flag to products table if it doesn't exist
ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `is_digital` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_featured`,
  ADD COLUMN IF NOT EXISTS `digital_delivery_info` text DEFAULT NULL AFTER `is_digital`;

-- Add index for is_digital
CREATE INDEX IF NOT EXISTS `idx_is_digital` ON `products` (`is_digital`);
