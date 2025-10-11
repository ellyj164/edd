<?php
/**
 * Migration: Create wallets and wallet_transactions tables
 * 
 * This creates tables for user wallet balances and transaction history
 */

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS wallets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            balance DECIMAL(18,2) NOT NULL DEFAULT 0,
            currency CHAR(3) NOT NULL DEFAULT 'USD',
            updated_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS wallet_transactions (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('credit','debit') NOT NULL,
            amount DECIMAL(18,2) NOT NULL,
            balance_after DECIMAL(18,2) NOT NULL,
            reference VARCHAR(100) NULL,
            description VARCHAR(500) NULL,
            meta JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'down' => "
        DROP TABLE IF EXISTS wallet_transactions;
        DROP TABLE IF EXISTS wallets;
    "
];
