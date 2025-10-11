-- Migration: Add Stripe Checkout Session Support
-- Date: 2025-10-09
-- Description: Adds support for Stripe Checkout Sessions

-- Add checkout session ID to orders table
ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `stripe_checkout_session_id` VARCHAR(255) NULL COMMENT 'Stripe Checkout Session ID (cs_...)',
ADD INDEX IF NOT EXISTS `idx_stripe_checkout_session` (`stripe_checkout_session_id`);

-- Migration complete
-- To apply: mysql -u [user] -p [database] < migrations/20251009_add_stripe_checkout_support.sql
