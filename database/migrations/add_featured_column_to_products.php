<?php
/**
 * Migration: Add featured column to products table
 * This migration ensures the featured column exists in the products table
 * for existing installations that might be missing it.
 */

require_once __DIR__ . '/../../includes/init.php';

try {
    $db = db();
    
    // Check if the featured column already exists
    $stmt = $db->prepare("PRAGMA table_info(products)");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    $featuredExists = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'featured') {
            $featuredExists = true;
            break;
        }
    }
    
    if (!$featuredExists) {
        echo "Adding 'featured' column to products table...\n";
        $db->exec("ALTER TABLE products ADD COLUMN featured INTEGER DEFAULT 0");
        echo "Successfully added 'featured' column to products table.\n";
    } else {
        echo "The 'featured' column already exists in products table.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Migration completed successfully.\n";