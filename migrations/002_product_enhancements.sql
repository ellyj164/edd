-- Product Enhancements Migration
-- This migration adds support for:
-- 1. Product view tracking (for Popular section)
-- 2. AI-recommended products flag
-- 3. Sponsored products system

-- Add is_ai_recommended column to products table if it doesn't exist
ALTER TABLE `products`
ADD COLUMN IF NOT EXISTS `is_ai_recommended` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_featured`,
ADD INDEX IF NOT EXISTS `idx_is_ai_recommended` (`is_ai_recommended`, `status`);

-- Add view_count column to products table for tracking popularity
ALTER TABLE `products`
ADD COLUMN IF NOT EXISTS `view_count` int(11) NOT NULL DEFAULT 0 AFTER `is_ai_recommended`,
ADD INDEX IF NOT EXISTS `idx_view_count` (`view_count` DESC);

-- Add sales_count column to products table for best sellers tracking
ALTER TABLE `products`
ADD COLUMN IF NOT EXISTS `sales_count` int(11) NOT NULL DEFAULT 0 AFTER `view_count`,
ADD INDEX IF NOT EXISTS `idx_sales_count` (`sales_count` DESC);

-- Create sponsored_products table for product sponsorship management
CREATE TABLE IF NOT EXISTS `sponsored_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `sponsorship_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `daily_cost` decimal(10,2) NOT NULL DEFAULT 5.00,
  `total_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` timestamp NULL DEFAULT NULL,
  `status` enum('active','paused','completed','cancelled') NOT NULL DEFAULT 'active',
  `position` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  `views` int(11) NOT NULL DEFAULT 0,
  `orders` int(11) NOT NULL DEFAULT 0,
  `revenue_generated` decimal(10,2) NOT NULL DEFAULT 0.00,
  `admin_approved` tinyint(1) NOT NULL DEFAULT 0,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `seller_id` (`seller_id`),
  KEY `status` (`status`),
  KEY `idx_sponsored_active` (`status`, `start_date`, `end_date`),
  KEY `idx_sponsored_position` (`position`, `status`),
  CONSTRAINT `fk_sponsored_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sponsored_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create product_views_detailed table for detailed view tracking (optional, for analytics)
CREATE TABLE IF NOT EXISTS `product_views_detailed` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referrer` text DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_product_views_date` (`product_id`, `viewed_at`),
  KEY `idx_views_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create sponsored_product_transactions table for tracking sponsorship payments
CREATE TABLE IF NOT EXISTS `sponsored_product_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sponsored_product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('payment','refund','adjustment') NOT NULL DEFAULT 'payment',
  `payment_method` varchar(50) DEFAULT 'wallet',
  `description` text DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sponsored_product_id` (`sponsored_product_id`),
  KEY `seller_id` (`seller_id`),
  KEY `idx_transaction_date` (`created_at`),
  CONSTRAINT `fk_sponsor_transaction` FOREIGN KEY (`sponsored_product_id`) REFERENCES `sponsored_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add comment for documentation
ALTER TABLE `products` 
COMMENT = 'Products table with AI recommendations, view tracking, and sales tracking';

ALTER TABLE `sponsored_products` 
COMMENT = 'Sponsored products for paid promotions on homepage';

ALTER TABLE `product_views_detailed` 
COMMENT = 'Detailed product view tracking for analytics';

ALTER TABLE `sponsored_product_transactions` 
COMMENT = 'Financial transactions for product sponsorships';
