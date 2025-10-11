<?php
/**
 * Migration: Update users table with suspended/deleted_at and coupons with code/name
 * 
 * Adds admin user management fields and ensures coupon fields exist
 */

return [
    'up' => "
        ALTER TABLE users ADD COLUMN IF NOT EXISTS suspended TINYINT(1) DEFAULT 0 AFTER status;
        ALTER TABLE users ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL AFTER suspended;
        
        ALTER TABLE coupons ADD COLUMN IF NOT EXISTS code VARCHAR(80) AFTER id;
        ALTER TABLE coupons ADD COLUMN IF NOT EXISTS name VARCHAR(150) AFTER code;
    ",
    'down' => "
        ALTER TABLE users DROP COLUMN IF EXISTS deleted_at;
        ALTER TABLE users DROP COLUMN IF EXISTS suspended;
        
        ALTER TABLE coupons DROP COLUMN IF EXISTS name;
        ALTER TABLE coupons DROP COLUMN IF EXISTS code;
    "
];
