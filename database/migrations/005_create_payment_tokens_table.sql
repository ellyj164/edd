-- Migration: Create payment_tokens table
-- Purpose: Store tokenized payment methods for users
-- Required by: PaymentToken model in includes/models_advanced.php

CREATE TABLE IF NOT EXISTS `payment_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL COMMENT 'Tokenized payment method identifier',
  `gateway` enum('stripe','paypal','flutterwave','mobile_momo','mock') NOT NULL DEFAULT 'stripe' COMMENT 'Payment gateway provider',
  `type` enum('card','bank_account','paypal','mobile_money','crypto') NOT NULL DEFAULT 'card' COMMENT 'Payment method type',
  `last_four` varchar(4) DEFAULT NULL COMMENT 'Last 4 digits for cards',
  `brand` varchar(50) DEFAULT NULL COMMENT 'Card brand or payment method brand',
  `exp_month` tinyint(2) DEFAULT NULL COMMENT 'Card expiration month',
  `exp_year` smallint(4) DEFAULT NULL COMMENT 'Card expiration year',
  `holder_name` varchar(100) DEFAULT NULL COMMENT 'Cardholder or account holder name',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Default payment method for user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Active status',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)) COMMENT 'Additional payment method data',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_default` (`is_default`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_payment_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tokenized payment methods for secure transactions';
