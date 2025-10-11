-- Migration: Enhanced Orders and Stripe Payment Tracking Tables
-- Date: 2025-10-08
-- Description: Creates tables for Stripe Payment Intents integration with order tracking
-- Database: MariaDB 10.2+ compatible with JSON column support

-- =======================
-- 1. Enhanced Orders Table
-- =======================
-- Note: This alters the existing orders table to add Stripe-specific fields
-- If the table doesn't exist yet, it will be created with all fields

ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `order_reference` VARCHAR(50) NULL UNIQUE COMMENT 'Human-readable order reference (ORD-YYYYMMDD-######)',
ADD COLUMN IF NOT EXISTS `currency` VARCHAR(3) NOT NULL DEFAULT 'usd' AFTER `total`,
ADD COLUMN IF NOT EXISTS `amount_minor` INT NOT NULL DEFAULT 0 COMMENT 'Total amount in minor units (cents)',
ADD COLUMN IF NOT EXISTS `tax_minor` INT NOT NULL DEFAULT 0 COMMENT 'Tax amount in minor units',
ADD COLUMN IF NOT EXISTS `shipping_minor` INT NOT NULL DEFAULT 0 COMMENT 'Shipping amount in minor units',
ADD COLUMN IF NOT EXISTS `stripe_payment_intent_id` VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS `stripe_customer_id` VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS `customer_email` VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS `customer_name` VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS `placed_at` TIMESTAMP NULL COMMENT 'When order was placed/paid',
ADD INDEX IF NOT EXISTS `idx_order_reference` (`order_reference`),
ADD INDEX IF NOT EXISTS `idx_stripe_payment_intent` (`stripe_payment_intent_id`),
ADD INDEX IF NOT EXISTS `idx_stripe_customer` (`stripe_customer_id`),
ADD INDEX IF NOT EXISTS `idx_customer_email` (`customer_email`);

-- Update status enum to include payment-specific states
ALTER TABLE `orders` 
MODIFY COLUMN `status` ENUM(
    'pending_payment',
    'pending',
    'processing',
    'shipped',
    'delivered',
    'cancelled',
    'refunded',
    'failed'
) NOT NULL DEFAULT 'pending_payment';

-- =======================
-- 2. Order Items Table
-- =======================
-- Enhanced order_items to ensure cascade delete
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `vendor_id` INT(11) NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100) NULL,
  `qty` INT(11) NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL COMMENT 'Unit price in decimal',
  `price_minor` INT NOT NULL DEFAULT 0 COMMENT 'Unit price in minor units',
  `subtotal` DECIMAL(10,2) NOT NULL COMMENT 'Line subtotal in decimal',
  `subtotal_minor` INT NOT NULL DEFAULT 0 COMMENT 'Line subtotal in minor units',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================
-- 3. Stripe Payment Intents Table
-- =======================
CREATE TABLE IF NOT EXISTS `stripe_payment_intents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `payment_intent_id` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Stripe PaymentIntent ID (pi_...)',
  `order_id` INT(11) NULL COMMENT 'Internal order ID',
  `order_reference` VARCHAR(50) NULL COMMENT 'Human-readable order reference',
  `amount_minor` INT NOT NULL COMMENT 'Amount in minor units (cents)',
  `currency` VARCHAR(3) NOT NULL DEFAULT 'usd',
  `status` VARCHAR(50) NOT NULL COMMENT 'Stripe PI status: requires_payment_method, requires_confirmation, requires_action, processing, requires_capture, canceled, succeeded',
  `client_secret` VARCHAR(255) NULL COMMENT 'Client secret for frontend confirmation',
  `payment_method` VARCHAR(255) NULL COMMENT 'Stripe payment method ID',
  `customer_id` VARCHAR(255) NULL COMMENT 'Stripe customer ID',
  `metadata` JSON NULL COMMENT 'Additional metadata from Stripe',
  `last_payload` JSON NULL COMMENT 'Last webhook payload received',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_intent_id` (`payment_intent_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_order_reference` (`order_reference`),
  KEY `idx_status` (`status`),
  KEY `idx_customer_id` (`customer_id`),
  CONSTRAINT `fk_stripe_pi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================
-- 4. Stripe Refunds Table
-- =======================
CREATE TABLE IF NOT EXISTS `stripe_refunds` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `refund_id` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Stripe Refund ID (re_...)',
  `payment_intent_id` VARCHAR(255) NOT NULL COMMENT 'Related Stripe PaymentIntent ID',
  `order_id` INT(11) NULL COMMENT 'Internal order ID',
  `amount_minor` INT NOT NULL COMMENT 'Refunded amount in minor units',
  `currency` VARCHAR(3) NOT NULL DEFAULT 'usd',
  `status` VARCHAR(50) NOT NULL COMMENT 'Refund status: pending, succeeded, failed, canceled',
  `reason` VARCHAR(255) NULL COMMENT 'Refund reason',
  `metadata` JSON NULL COMMENT 'Additional metadata',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_refund_id` (`refund_id`),
  KEY `idx_payment_intent_id` (`payment_intent_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_stripe_refund_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================
-- 5. Stripe Events Table (Idempotency)
-- =======================
CREATE TABLE IF NOT EXISTS `stripe_events` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `event_id` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Stripe Event ID (evt_...)',
  `event_type` VARCHAR(100) NOT NULL COMMENT 'Event type (e.g., payment_intent.succeeded)',
  `processed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `payload` JSON NULL COMMENT 'Full event payload for debugging',
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_processed_at` (`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================
-- 6. Add stripe_customer_id to users table
-- =======================
-- This allows us to reuse Stripe customers for repeat purchases
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `stripe_customer_id` VARCHAR(255) NULL COMMENT 'Stripe Customer ID for repeat purchases',
ADD INDEX IF NOT EXISTS `idx_stripe_customer_id` (`stripe_customer_id`);

-- =======================
-- Migration Complete
-- =======================
-- To apply this migration:
-- mysql -u [user] -p [database] < migrations/20251008_create_orders_and_stripe_tables.sql
--
-- To rollback (manual - not recommended for production):
-- - Remove added columns from orders and users tables
-- - DROP TABLE stripe_events, stripe_refunds, stripe_payment_intents
-- - Modify order_items to remove foreign key constraint
