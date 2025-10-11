<?php
/**
 * Migration: Seed initial admin and seller users
 * 
 * Adds one admin user and one approved seller user for testing
 * as required in the specifications.
 */

return [
    'up' => "
        -- Insert admin user
        INSERT IGNORE INTO users (
            username, email, pass_hash, first_name, last_name, 
            role, status, verified_at, created_at
        ) VALUES (
            'admin', 
            'admin@epd.local', 
            '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- 'password'
            'Admin', 
            'User',
            'admin', 
            'active',
            NOW(),
            NOW()
        );
        
        -- Insert seller user
        INSERT IGNORE INTO users (
            username, email, pass_hash, first_name, last_name,
            role, status, verified_at, created_at
        ) VALUES (
            'seller1',
            'seller@epd.local',
            '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- 'password' 
            'Test',
            'Seller',
            'vendor',
            'active', 
            NOW(),
            NOW()
        );
        
        -- Create vendor record for the seller
        INSERT IGNORE INTO vendors (
            user_id, business_name, business_type, status, 
            approved_at, approved_by, created_at
        ) VALUES (
            (SELECT id FROM users WHERE username = 'seller1'),
            'Test Seller Business',
            'business',
            'approved',
            NOW(),
            (SELECT id FROM users WHERE username = 'admin'),
            NOW()
        );
    ",
    'down' => "
        -- Remove vendor record
        DELETE FROM vendors WHERE user_id = (SELECT id FROM users WHERE username = 'seller1');
        
        -- Remove seed users
        DELETE FROM users WHERE username IN ('admin', 'seller1');
    "
];