<?php
/**
 * Migration: Create currency_rates table
 * 
 * This table caches currency exchange rates for multi-currency support
 */

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS currency_rates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            base CHAR(3) NOT NULL,
            quote CHAR(3) NOT NULL,
            rate DECIMAL(18,8) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_pair (base, quote),
            INDEX idx_base (base),
            INDEX idx_quote (quote)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'down' => "
        DROP TABLE IF EXISTS currency_rates;
    "
];
