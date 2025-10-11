<?php
/**
 * Migration: Create product_files table and add is_digital flag to products
 * 
 * This table stores digital product files for download
 */

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS product_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_size BIGINT NULL,
            download_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product_id (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        ALTER TABLE products ADD COLUMN IF NOT EXISTS is_digital TINYINT(1) DEFAULT 0 AFTER price;
    ",
    'down' => "
        DROP TABLE IF EXISTS product_files;
        ALTER TABLE products DROP COLUMN IF EXISTS is_digital;
    "
];
