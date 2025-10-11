-- Add OAuth fields to users table
-- This migration adds support for OAuth authentication (Google, Facebook, etc.)

ALTER TABLE `users` 
ADD COLUMN `oauth_provider` VARCHAR(50) DEFAULT NULL COMMENT 'OAuth provider (google, facebook, etc.)',
ADD COLUMN `oauth_provider_id` VARCHAR(255) DEFAULT NULL COMMENT 'Unique ID from OAuth provider',
ADD COLUMN `oauth_token` TEXT DEFAULT NULL COMMENT 'OAuth access token (encrypted)',
ADD COLUMN `oauth_refresh_token` TEXT DEFAULT NULL COMMENT 'OAuth refresh token (encrypted)',
ADD INDEX `idx_oauth_provider` (`oauth_provider`),
ADD INDEX `idx_oauth_provider_id` (`oauth_provider_id`);

-- Make password optional for OAuth users (they may not have a password)
ALTER TABLE `users`
MODIFY COLUMN `pass_hash` VARCHAR(255) DEFAULT NULL;
