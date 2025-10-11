<?php
/**
 * Migration: Create Countries Table
 * Created: 2025-10-11
 * Description: Create countries table to store comprehensive country data for checkout
 */

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS `countries` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL COMMENT 'Country name',
            `iso2` CHAR(2) NOT NULL COMMENT 'ISO 3166-1 alpha-2 code',
            `iso3` CHAR(3) NOT NULL COMMENT 'ISO 3166-1 alpha-3 code',
            `dial_code` VARCHAR(10) NOT NULL COMMENT 'International dialing code with + prefix',
            `is_eu` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if EU member state, 0 otherwise',
            `currency_code` CHAR(3) NOT NULL DEFAULT 'USD' COMMENT 'ISO 4217 currency code',
            `currency_symbol` VARCHAR(10) NOT NULL DEFAULT '\$' COMMENT 'Currency symbol',
            `flag_emoji` VARCHAR(10) NOT NULL COMMENT 'Flag emoji for display',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_iso2` (`iso2`),
            UNIQUE KEY `unique_iso3` (`iso3`),
            KEY `idx_name` (`name`),
            KEY `idx_is_eu` (`is_eu`),
            KEY `idx_currency_code` (`currency_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Stores comprehensive country data for checkout and localization';
    ",
    
    'down' => "
        DROP TABLE IF EXISTS `countries`;
    "
];
