<?php
/**
 * Complete Migration Script
 * Ensures all database enhancements are applied
 */

require_once __DIR__ . '/../includes/init.php';

echo "Starting complete migration...\n";

try {
    $db = db();
    
    echo "✅ Database connection established\n";
    
    // 1. Add SEO keywords field to products table
    echo "\n1. Adding SEO keywords field to products table...\n";
    
    $checkQuery = "SHOW COLUMNS FROM products LIKE 'keywords'";
    $checkStmt = $db->query($checkQuery);
    
    if ($checkStmt->rowCount() === 0) {
        $addFieldQuery = "ALTER TABLE products ADD COLUMN keywords TEXT NULL AFTER meta_description";
        $db->exec($addFieldQuery);
        echo "✅ Added keywords field to products table\n";
    } else {
        echo "ℹ️ Keywords field already exists in products table\n";
    }
    
    // 2. Add index for better search performance
    $indexQuery = "CREATE INDEX IF NOT EXISTS idx_products_keywords ON products(keywords(255))";
    $db->exec($indexQuery);
    echo "✅ Added index for keywords field\n";
    
    // 3. Add two_factor_enabled field to users table
    echo "\n2. Adding two_factor_enabled field to users table...\n";
    
    $checkTwoFactorQuery = "SHOW COLUMNS FROM users LIKE 'two_factor_enabled'";
    $checkTwoFactorStmt = $db->query($checkTwoFactorQuery);
    
    if ($checkTwoFactorStmt->rowCount() === 0) {
        $addTwoFactorQuery = "ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER email_verified_at";
        $db->exec($addTwoFactorQuery);
        echo "✅ Added two_factor_enabled field to users table\n";
    } else {
        echo "ℹ️ two_factor_enabled field already exists in users table\n";
    }
    
    // 4. Ensure product_images table exists with all required fields
    echo "\n3. Verifying product_images table structure...\n";
    
    $createImagesTableQuery = "
        CREATE TABLE IF NOT EXISTS product_images (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            image_url varchar(255) NOT NULL,
            alt_text varchar(255) DEFAULT NULL,
            is_primary tinyint(1) NOT NULL DEFAULT 0,
            sort_order int(11) NOT NULL DEFAULT 0,
            file_size int(11) DEFAULT NULL,
            width int(11) DEFAULT NULL,
            height int(11) DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (id),
            KEY idx_product_id (product_id),
            KEY idx_is_primary (is_primary),
            KEY idx_sort_order (sort_order),
            CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->exec($createImagesTableQuery);
    echo "✅ Product images table verified/created\n";
    
    // 5. Populate categories if table is empty
    echo "\n4. Checking categories table...\n";
    
    $categoryCountQuery = "SELECT COUNT(*) FROM categories";
    $categoryCountStmt = $db->query($categoryCountQuery);
    $categoryCount = $categoryCountStmt->fetchColumn();
    
    if ($categoryCount < 10) {
        echo "Populating categories table...\n";
        include __DIR__ . '/populate_categories.php';
    } else {
        echo "ℹ️ Categories table already populated ({$categoryCount} categories)\n";
    }
    
    // 6. Create uploads directory structure
    echo "\n5. Creating upload directories...\n";
    
    $uploadDirs = [
        '/home/runner/work/epd/epd/uploads',
        '/home/runner/work/epd/epd/uploads/products',
        '/home/runner/work/epd/epd/uploads/avatars',
        '/home/runner/work/epd/epd/uploads/temp'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✅ Created directory: {$dir}\n";
        } else {
            echo "ℹ️ Directory already exists: {$dir}\n";
        }
    }
    
    // 7. Verify all critical tables exist
    echo "\n6. Verifying critical table structure...\n";
    
    $criticalTables = [
        'users', 'products', 'categories', 'vendors', 'orders', 'order_items',
        'addresses', 'product_images', 'coupons', 'cms_pages'
    ];
    
    foreach ($criticalTables as $table) {
        $checkTableQuery = "SHOW TABLES LIKE '{$table}'";
        $checkTableStmt = $db->query($checkTableQuery);
        
        if ($checkTableStmt->rowCount() > 0) {
            echo "✅ Table '{$table}' exists\n";
        } else {
            echo "❌ Table '{$table}' is missing\n";
        }
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "\nSummary:\n";
    echo "- SEO keywords field added to products\n";
    echo "- Two-factor authentication support added\n";
    echo "- Product images table verified\n";
    echo "- Upload directories created\n";
    echo "- Database structure validated\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "This is expected if no database is available.\n";
    echo "Run this script when database connection is available.\n";
    exit(1);
}
?>