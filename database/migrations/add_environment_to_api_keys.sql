-- Add environment field to api_keys table for sandbox/live differentiation
-- Migration: add_environment_to_api_keys
-- Date: 2024

-- Add environment column if it doesn't exist
ALTER TABLE `api_keys` 
ADD COLUMN `environment` ENUM('sandbox', 'live') NOT NULL DEFAULT 'sandbox' AFTER `name`;

-- Add index for faster lookups by environment
ALTER TABLE `api_keys`
ADD INDEX `idx_environment` (`environment`);
