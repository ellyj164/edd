<?php
/**
 * Migration: Make billing_address and shipping_address nullable in orders table
 * 
 * This migration fixes the SQL error: "Field 'billing_address' doesn't have a default value"
 * by allowing NULL values for these fields. With embedded Stripe checkout, addresses
 * will be collected but may not be available at order creation time.
 */

return [
    'up' => "
        ALTER TABLE orders 
        MODIFY COLUMN billing_address LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
        MODIFY COLUMN shipping_address LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL;
    ",
    'down' => "
        -- Note: This rollback will fail if there are NULL values in the table
        -- Make sure to populate NULL values before rolling back
        ALTER TABLE orders 
        MODIFY COLUMN billing_address LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(billing_address)),
        MODIFY COLUMN shipping_address LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(shipping_address));
    "
];
