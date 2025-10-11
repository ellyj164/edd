-- Migration: E-Commerce Platform Enhancements
-- Date: 2025-10-11
-- Description: Documents schema requirements for new features

-- Note: All required columns already exist in the database schema.
-- This migration file serves as documentation of the columns utilized by the new features.

-- ============================================================================
-- DIGITAL PRODUCTS ENHANCEMENT
-- ============================================================================
-- The following columns are used for digital product delivery options:
-- - products.digital_url (VARCHAR 512) - External URL for hosted digital files
-- - products.digital_file_path (VARCHAR 512) - Server path for uploaded files
-- - products.download_limit (INT) - Number of allowed downloads
-- - products.expiry_days (INT) - Days until download link expires
-- - products.is_digital (TINYINT) - Flag indicating digital product
-- - products.digital_delivery_info (TEXT) - Instructions for customers

-- ============================================================================
-- SKU GENERATION
-- ============================================================================
-- The following columns are used for automatic SKU generation:
-- - products.sku (VARCHAR 100) - Unique SKU format: V{vendorId}-{initials}-{random}
-- Index exists: UNIQUE KEY `idx_sku` (`sku`)

-- ============================================================================
-- BRAND MANAGEMENT
-- ============================================================================
-- The following columns are used for brand creation from seller interface:
-- - brands.id (BIGINT) - Primary key
-- - brands.name (VARCHAR 150) - Brand name
-- - brands.slug (VARCHAR 160) - URL-friendly slug
-- - brands.description (TEXT) - Brand description
-- - brands.is_active (TINYINT) - Active status
-- - products.brand_id (BIGINT) - Foreign key to brands table
-- Unique constraints exist on brands.name and brands.slug

-- ============================================================================
-- CURRENCY HANDLING
-- ============================================================================
-- The following are used for Rwanda RWF currency enforcement:
-- - products.currency_code (CHAR 3) - Product currency
-- - currency_rates table - Exchange rate caching
-- Note: Currency enforcement is handled in application code, not database

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================
-- Run these queries to verify all required columns exist:

-- Check products table has digital product columns
-- SELECT COLUMN_NAME FROM information_schema.COLUMNS 
-- WHERE TABLE_NAME = 'products' 
-- AND COLUMN_NAME IN ('digital_url', 'digital_file_path', 'download_limit', 'expiry_days', 'is_digital', 'digital_delivery_info');

-- Check brands table structure
-- SELECT COLUMN_NAME FROM information_schema.COLUMNS 
-- WHERE TABLE_NAME = 'brands';

-- Check SKU uniqueness constraint
-- SHOW INDEX FROM products WHERE Key_name = 'idx_sku';

-- ============================================================================
-- POST-DEPLOYMENT TASKS
-- ============================================================================
-- 1. Ensure uploads/digital_products/ directory exists with proper permissions:
--    mkdir -p /path/to/edd/uploads/digital_products
--    chmod 755 /path/to/edd/uploads/digital_products
--
-- 2. Verify exchange rates are populated in currency_rates table
--
-- 3. Test digital file uploads work correctly
--
-- 4. Test brand creation from seller interface
--
-- 5. Verify SKU uniqueness is enforced

-- ============================================================================
-- ROLLBACK NOTES
-- ============================================================================
-- No database schema changes were made. To rollback:
-- 1. Revert application code changes
-- 2. Remove any digital product files from uploads/digital_products/
-- 3. (Optional) Remove brands created via "Other" option if desired
