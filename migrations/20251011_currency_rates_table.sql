-- Migration: Currency Rates Table for Dynamic Currency Conversion
-- Created: 2025-10-11
-- Description: Stores exchange rates for multi-currency support
-- Required for: Dynamic currency switching based on country selection

CREATE TABLE IF NOT EXISTS `currency_rates` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `base` VARCHAR(3) NOT NULL COMMENT 'Base currency code (e.g., USD)',
    `quote` VARCHAR(3) NOT NULL COMMENT 'Quote currency code (e.g., EUR)',
    `rate` DECIMAL(20, 8) NOT NULL COMMENT 'Exchange rate from base to quote',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_currency_pair` (`base`, `quote`),
    KEY `idx_base` (`base`),
    KEY `idx_quote` (`quote`),
    KEY `idx_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Stores exchange rates for currency conversion';

-- Insert initial exchange rates
-- These are approximate rates and should be updated regularly via API
INSERT INTO `currency_rates` (`base`, `quote`, `rate`) VALUES
    -- USD to other currencies
    ('USD', 'EUR', 0.92),
    ('USD', 'GBP', 0.79),
    ('USD', 'RWF', 1320.00),
    ('USD', 'AUD', 1.52),
    ('USD', 'CAD', 1.36),
    
    -- EUR to other currencies
    ('EUR', 'USD', 1.09),
    ('EUR', 'GBP', 0.86),
    ('EUR', 'RWF', 1435.00),
    ('EUR', 'AUD', 1.66),
    ('EUR', 'CAD', 1.48),
    
    -- GBP to other currencies
    ('GBP', 'USD', 1.27),
    ('GBP', 'EUR', 1.16),
    ('GBP', 'RWF', 1670.00),
    ('GBP', 'AUD', 1.93),
    ('GBP', 'CAD', 1.73),
    
    -- RWF to other currencies
    ('RWF', 'USD', 0.00076),
    ('RWF', 'EUR', 0.00070),
    ('RWF', 'GBP', 0.00060)
ON DUPLICATE KEY UPDATE 
    `rate` = VALUES(`rate`),
    `updated_at` = CURRENT_TIMESTAMP;

-- Add currency preference to orders table if not exists
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `currency_code` VARCHAR(3) DEFAULT 'USD' COMMENT 'Currency used for this order' AFTER `total_amount`,
ADD COLUMN IF NOT EXISTS `exchange_rate` DECIMAL(20, 8) DEFAULT 1.00000000 COMMENT 'Exchange rate at time of order' AFTER `currency_code`;

-- Add index for better query performance
ALTER TABLE `orders` 
ADD INDEX IF NOT EXISTS `idx_currency_code` (`currency_code`);
