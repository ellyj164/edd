<?php
/**
 * Migration: Create giftcards table
 * 
 * This table stores gift card codes, amounts, sender/receiver info, and redemption status
 */

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS giftcards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(64) UNIQUE NOT NULL,
            amount DECIMAL(18,2) NOT NULL,
            currency CHAR(3) NOT NULL DEFAULT 'USD',
            sender_name VARCHAR(120),
            sender_email VARCHAR(190),
            receiver_name VARCHAR(120),
            receiver_email VARCHAR(190),
            message VARCHAR(500),
            redeemed_by INT NULL,
            redeemed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_code (code),
            INDEX idx_redeemed_by (redeemed_by),
            INDEX idx_receiver_email (receiver_email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'down' => "
        DROP TABLE IF EXISTS giftcards;
    "
];
