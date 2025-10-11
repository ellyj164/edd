-- Wallet Transaction Ledger Migration
-- This migration ensures all wallet-related tables exist and are properly configured

-- Ensure seller_wallets table exists
CREATE TABLE IF NOT EXISTS `seller_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `pending_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_earned` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_withdrawn` decimal(15,2) NOT NULL DEFAULT 0.00,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 10.00,
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_id` (`vendor_id`),
  KEY `idx_seller_wallets_balance` (`balance`),
  KEY `idx_seller_wallets_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure seller_payouts table exists with all required fields
CREATE TABLE IF NOT EXISTS `seller_payouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) NOT NULL,
  `request_amount` decimal(10,2) NOT NULL,
  `processing_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payout_method` enum('bank_transfer','paypal','stripe','wise','manual') NOT NULL DEFAULT 'bank_transfer',
  `payout_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payout_details`)),
  `status` enum('requested','pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'requested',
  `reference_number` varchar(100) DEFAULT NULL,
  `external_transaction_id` varchar(255) DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `status` (`status`),
  KEY `idx_seller_payouts_vendor_status` (`vendor_id`,`status`),
  KEY `idx_seller_payouts_reference` (`reference_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Wallet transaction ledger for tracking all financial movements
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `wallet_type` enum('seller','user') NOT NULL DEFAULT 'seller',
  `transaction_type` enum('credit','debit') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_before` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'order, payout, refund, adjustment',
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `wallet_id` (`wallet_id`),
  KEY `wallet_type` (`wallet_type`),
  KEY `transaction_type` (`transaction_type`),
  KEY `reference_type` (`reference_type`,`reference_id`),
  KEY `idx_wallet_transactions_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- User wallets for customer balances
CREATE TABLE IF NOT EXISTS `user_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_deposits` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user_wallets_balance` (`balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add missing columns to seller_wallets if they don't exist
-- Note: These are safe operations that won't fail if columns already exist
ALTER TABLE `seller_wallets` 
  ADD COLUMN IF NOT EXISTS `total_withdrawn` decimal(15,2) NOT NULL DEFAULT 0.00 AFTER `total_earned`,
  ADD COLUMN IF NOT EXISTS `commission_rate` decimal(5,2) NOT NULL DEFAULT 10.00 AFTER `total_withdrawn`;
