<?php
/**
 * Add SEO Keywords Field to Products Table
 * Migration for E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';

try {
    $db = db();
    
    echo "Adding SEO keywords field to products table...\n";
    
    // Check if keywords field already exists
    $checkQuery = "SHOW COLUMNS FROM products LIKE 'keywords'";
    $checkStmt = $db->query($checkQuery);
    
    if ($checkStmt->rowCount() === 0) {
        // Add keywords field
        $addFieldQuery = "ALTER TABLE products ADD COLUMN keywords TEXT NULL AFTER meta_description";
        $db->exec($addFieldQuery);
        echo "✅ Added keywords field to products table\n";
    } else {
        echo "ℹ️ Keywords field already exists in products table\n";
    }
    
    // Add index for better search performance
    $indexQuery = "CREATE INDEX IF NOT EXISTS idx_products_keywords ON products(keywords(255))";
    $db->exec($indexQuery);
    echo "✅ Added index for keywords field\n";
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>