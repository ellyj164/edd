<?php
/**
 * Migration: Create user_payment_methods table
 * 
 * This table stores saved Stripe payment methods for users,
 * allowing them to reuse cards for future purchases.
 */

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS user_payment_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            stripe_payment_method_id VARCHAR(255),
            brand VARCHAR(50),
            last4 VARCHAR(4),
            exp_month INT,
            exp_year INT,
            is_default TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_stripe_pm_id (stripe_payment_method_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'down' => "
        DROP TABLE IF EXISTS user_payment_methods;
    "
];
