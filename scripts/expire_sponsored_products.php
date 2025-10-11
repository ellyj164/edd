<?php
/**
 * Cron Job: Expire Sponsored Products
 * 
 * This script should be run daily to automatically expire sponsored products
 * that have passed their sponsorship period.
 * 
 * Setup: Add to crontab:
 * 0 0 * * * cd /path/to/edd && php scripts/expire_sponsored_products.php
 */

require_once __DIR__ . '/../includes/init.php';

$db = db();

try {
    // Start transaction
    $db->beginTransaction();
    
    // Find all active sponsored products that have expired
    $expireQuery = "
        UPDATE sponsored_products 
        SET status = 'expired', updated_at = NOW()
        WHERE status = 'active' 
        AND sponsored_until <= NOW()
    ";
    $stmt = $db->prepare($expireQuery);
    $stmt->execute();
    $expiredCount = $stmt->rowCount();
    
    // Log the expiration
    error_log("Sponsored Products Cron: Expired {$expiredCount} sponsored products");
    
    // Commit transaction
    $db->commit();
    
    echo "Successfully expired {$expiredCount} sponsored products.\n";
    
} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Sponsored Products Cron Error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
