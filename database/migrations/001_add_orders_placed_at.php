<?php
/**
 * Migration: Add placed_at column to orders table
 * 
 * This migration adds the missing placed_at column that is referenced
 * in models_extended.php:467 for the trending products query.
 */

return [
    'up' => "
        ALTER TABLE orders 
        ADD COLUMN placed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        AFTER payment_transaction_id;
        
        -- Update existing orders to set placed_at based on created_at
        UPDATE orders 
        SET placed_at = created_at 
        WHERE placed_at IS NULL OR placed_at = '0000-00-00 00:00:00';
        
        -- Add index for performance on the trending products query
        CREATE INDEX idx_orders_placed_at_status ON orders (placed_at, status);
    ",
    'down' => "
        DROP INDEX IF EXISTS idx_orders_placed_at_status ON orders;
        ALTER TABLE orders DROP COLUMN placed_at;
    "
];