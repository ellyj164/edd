-- Migration: Stripe Customers and Payment Methods Tables
-- Date: 2025-10-09
-- Description: Templates for storing Stripe customer IDs and saved payment methods
-- Database: MariaDB 10.2+ compatible with JSON column support
--
-- NOTE: These tables are OPTIONAL. The checkout flow will work without them.
-- Apply these migrations if you want to persist customer/payment method data locally.
-- The runtime code includes hooks/notes where to persist data if tables exist.

-- =======================
-- 1. Customers Table (Optional)
-- =======================
-- Store Stripe customer mappings for users
-- Allows quick lookup of Stripe customer ID by user_id or email
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NULL COMMENT 'Internal user ID (foreign key to users table)',
  `email` VARCHAR(255) NOT NULL COMMENT 'Customer email address',
  `stripe_customer_id` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Stripe Customer ID (cus_...)',
  `name` VARCHAR(255) NULL COMMENT 'Customer full name',
  `phone` VARCHAR(50) NULL COMMENT 'Customer phone number',
  `metadata` JSON NULL COMMENT 'Additional customer metadata',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_stripe_customer_id` (`stripe_customer_id`),
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================
-- 2. Payment Methods Table (Optional)
-- =======================
-- Store saved payment methods for customers
-- Enables "save this card for future billing" functionality
CREATE TABLE IF NOT EXISTS `payment_methods` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NULL COMMENT 'Internal user ID',
  `customer_id` INT(11) NULL COMMENT 'Internal customer ID (foreign key to customers table)',
  `stripe_customer_id` VARCHAR(255) NOT NULL COMMENT 'Stripe Customer ID',
  `stripe_payment_method_id` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Stripe PaymentMethod ID (pm_...)',
  `type` VARCHAR(50) NOT NULL DEFAULT 'card' COMMENT 'Payment method type (card, bank_account, etc)',
  `brand` VARCHAR(50) NULL COMMENT 'Card brand (visa, mastercard, amex, etc)',
  `last4` VARCHAR(4) NULL COMMENT 'Last 4 digits of card',
  `exp_month` INT(2) NULL COMMENT 'Card expiration month',
  `exp_year` INT(4) NULL COMMENT 'Card expiration year',
  `fingerprint` VARCHAR(255) NULL COMMENT 'Stripe card fingerprint for duplicate detection',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Is this the default payment method',
  `metadata` JSON NULL COMMENT 'Additional payment method metadata',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_stripe_customer_id` (`stripe_customer_id`),
  KEY `idx_stripe_payment_method_id` (`stripe_payment_method_id`),
  KEY `idx_is_default` (`is_default`),
  CONSTRAINT `fk_payment_methods_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_methods_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================
-- 3. Payments Table Enhancement (Optional)
-- =======================
-- Add receipt_email column to track where receipts were sent
-- Note: This assumes you have a 'payments' table. Adjust table name as needed.
-- You may already have this in stripe_payment_intents - check your schema.
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NULL COMMENT 'Related order ID',
  `stripe_payment_intent_id` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Stripe PaymentIntent ID',
  `stripe_payment_method_id` VARCHAR(255) NULL COMMENT 'Stripe PaymentMethod ID used',
  `stripe_customer_id` VARCHAR(255) NULL COMMENT 'Stripe Customer ID',
  `amount` DECIMAL(10,2) NOT NULL COMMENT 'Amount in decimal format',
  `amount_minor` INT NOT NULL COMMENT 'Amount in minor units (cents)',
  `currency` VARCHAR(3) NOT NULL DEFAULT 'usd',
  `status` VARCHAR(50) NOT NULL COMMENT 'Payment status',
  `receipt_email` VARCHAR(255) NULL COMMENT 'Email where receipt was sent',
  `metadata` JSON NULL COMMENT 'Additional payment metadata',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_stripe_payment_intent_id` (`stripe_payment_intent_id`),
  KEY `idx_stripe_customer_id` (`stripe_customer_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================
-- Usage Notes
-- =======================
-- To apply this migration:
-- mysql -u [user] -p [database] < migrations/20251009_stripe_customers_and_payment_methods.sql
--
-- Integration points in code:
-- 1. After creating/retrieving Stripe Customer, persist to 'customers' table
-- 2. After successful payment with setup_future_usage, persist PaymentMethod to 'payment_methods' table
-- 3. When customer returns, query 'payment_methods' to show saved cards
-- 4. Set receipt_email in 'payments' table when sending confirmation emails
--
-- The runtime code will check if these tables exist and persist data accordingly.
-- All checkout functionality works WITHOUT these tables - they're purely for enhanced features.
