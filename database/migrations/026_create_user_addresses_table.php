<?php
/**
 * Migration: Create user_addresses table
 * 
 * This table stores user shipping and billing addresses for faster checkout
 * and address management in the user account.
 */

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS user_addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            address_type ENUM('billing', 'shipping') NOT NULL,
            full_name VARCHAR(100),
            phone VARCHAR(50),
            address_line VARCHAR(255),
            city VARCHAR(100),
            state VARCHAR(100),
            postal_code VARCHAR(50),
            country VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'down' => "
        DROP TABLE IF EXISTS user_addresses;
    "
];
