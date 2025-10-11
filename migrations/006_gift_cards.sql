-- Create gift_cards table for gift card functionality
-- Supports both digital and physical gift cards

CREATE TABLE IF NOT EXISTS `gift_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL UNIQUE COMMENT 'Unique gift card code',
  `amount` decimal(10,2) NOT NULL COMMENT 'Original gift card value',
  `balance` decimal(10,2) NOT NULL COMMENT 'Current balance',
  `card_type` enum('digital','physical') NOT NULL DEFAULT 'digital',
  `design` varchar(50) DEFAULT 'generic' COMMENT 'Card design theme',
  `recipient_name` varchar(255) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `sender_name` varchar(255) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `personal_message` text DEFAULT NULL,
  `status` enum('pending','active','redeemed','expired','cancelled') NOT NULL DEFAULT 'pending',
  `order_id` int(11) DEFAULT NULL COMMENT 'Order ID for purchase',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `activated_at` timestamp NULL DEFAULT NULL COMMENT 'When gift card was activated after payment',
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code` (`code`),
  KEY `idx_recipient_email` (`recipient_email`),
  KEY `idx_sender_email` (`sender_email`),
  KEY `idx_status` (`status`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create gift_card_transactions table to track usage
CREATE TABLE IF NOT EXISTS `gift_card_transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `gift_card_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL COMMENT 'Order where gift card was used',
  `transaction_type` enum('purchase','redemption','refund','adjustment') NOT NULL,
  `amount` decimal(10,2) NOT NULL COMMENT 'Amount added or deducted',
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gift_card_id` (`gift_card_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
