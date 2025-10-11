#!/usr/bin/env php
<?php
/**
 * Daily Currency Rate Update Script
 * Run this script via cron once per day to update exchange rates
 * 
 * Crontab example (runs at 2 AM daily):
 * 0 2 * * * /usr/bin/php /path/to/update_currency_rates.php
 */

// Load application bootstrap
require_once __DIR__ . '/../includes/init.php';

echo "Starting currency rate update...\n";

try {
    $currency = Currency::getInstance();
    
    // Check if update is needed
    if ($currency->shouldUpdateRates()) {
        echo "Rates are stale, updating...\n";
        
        if ($currency->updateExchangeRates()) {
            echo "Successfully updated exchange rates.\n";
            
            // Log the update
            if (class_exists('Logger')) {
                Logger::info("Currency rates updated successfully");
            }
        } else {
            echo "Failed to update exchange rates.\n";
            if (class_exists('Logger')) {
                Logger::error("Currency rate update failed");
            }
        }
    } else {
        echo "Rates are up to date, no update needed.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (class_exists('Logger')) {
        Logger::error("Currency rate update error: " . $e->getMessage());
    }
    exit(1);
}

exit(0);
