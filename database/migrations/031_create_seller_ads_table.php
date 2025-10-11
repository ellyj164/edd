<?php
/**
 * Migration: Create seller_ads table
 * 
 * This table stores seller advertising campaigns
 */

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS seller_ads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            seller_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            budget DECIMAL(18,2) NOT NULL,
            currency CHAR(3) NOT NULL DEFAULT 'USD',
            starts_at DATETIME NOT NULL,
            ends_at DATETIME NOT NULL,
            target JSON NULL,
            status ENUM('pending','approved','rejected','running','paused','completed') DEFAULT 'pending',
            impressions INT DEFAULT 0,
            clicks INT DEFAULT 0,
            spend DECIMAL(18,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_seller_id (seller_id),
            INDEX idx_status (status),
            INDEX idx_starts_at (starts_at),
            INDEX idx_ends_at (ends_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'down' => "
        DROP TABLE IF EXISTS seller_ads;
    "
];
