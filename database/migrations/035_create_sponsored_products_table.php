<?php
/**
 * Migration: Create sponsored_products table
 * 
 * This table tracks product sponsorships with 7-day duration,
 * payment information, and automatic expiration handling.
 */

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS sponsored_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            vendor_id INT NOT NULL,
            seller_id INT NOT NULL,
            cost DECIMAL(10,2) NOT NULL,
            currency CHAR(3) NOT NULL DEFAULT 'USD',
            payment_method VARCHAR(50) DEFAULT 'wallet',
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
            status ENUM('pending', 'active', 'expired', 'rejected', 'cancelled') DEFAULT 'pending',
            sponsored_from DATETIME NOT NULL,
            sponsored_until DATETIME NOT NULL,
            impressions INT DEFAULT 0,
            clicks INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            approved_by INT NULL,
            approved_at DATETIME NULL,
            rejected_reason TEXT NULL,
            INDEX idx_product_id (product_id),
            INDEX idx_vendor_id (vendor_id),
            INDEX idx_seller_id (seller_id),
            INDEX idx_status (status),
            INDEX idx_payment_status (payment_status),
            INDEX idx_sponsored_until (sponsored_until),
            INDEX idx_active_sponsorships (status, sponsored_until),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
            FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        -- Create index for finding active sponsored products
        CREATE INDEX idx_active_sponsored ON sponsored_products(status, sponsored_until, product_id);
        
        -- Create table for sponsored product analytics
        CREATE TABLE IF NOT EXISTS sponsored_product_analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sponsored_product_id INT NOT NULL,
            event_type ENUM('impression', 'click', 'view') NOT NULL,
            user_id INT NULL,
            session_id VARCHAR(255) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            referrer TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sponsored_product_id (sponsored_product_id),
            INDEX idx_event_type (event_type),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (sponsored_product_id) REFERENCES sponsored_products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        -- Create settings table for ad pricing
        CREATE TABLE IF NOT EXISTS sponsored_product_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT NOT NULL,
            description TEXT NULL,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            updated_by INT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        -- Insert default pricing
        INSERT INTO sponsored_product_settings (setting_key, setting_value, description) 
        VALUES ('price_per_7_days', '50.00', 'Cost per product for 7-day sponsored placement (USD)')
        ON DUPLICATE KEY UPDATE setting_key = setting_key;
    ",
    'down' => "
        DROP TABLE IF EXISTS sponsored_product_analytics;
        DROP TABLE IF EXISTS sponsored_products;
        DROP TABLE IF EXISTS sponsored_product_settings;
    "
];
