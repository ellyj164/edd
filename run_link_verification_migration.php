<?php
/**
 * Link-based Email Verification Migration Runner
 * Run this script once to migrate the database
 */

require_once __DIR__ . '/includes/init.php';

echo "==================================================\n";
echo "Link-based Email Verification Migration\n";
echo "==================================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Read migration file
    $migrationFile = __DIR__ . '/migrations/link_verification_migration.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: {$migrationFile}");
    }
    
    $sql = file_get_contents($migrationFile);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "Executing migration...\n\n";
    
    $db->beginTransaction();
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            echo "Executing: " . substr($statement, 0, 60) . "...\n";
            $db->exec($statement);
            echo "  ✓ Success\n";
        } catch (PDOException $e) {
            // Ignore "column already exists" errors
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "  ℹ Column already exists, skipping\n";
            } else {
                throw $e;
            }
        }
    }
    
    $db->commit();
    
    echo "\n==================================================\n";
    echo "✅ Migration completed successfully!\n";
    echo "==================================================\n\n";
    
    echo "Next steps:\n";
    echo "1. Test registration with a new user\n";
    echo "2. Check that verification email is sent with a link\n";
    echo "3. Click the link from the same device/IP to verify\n";
    echo "4. Test link expiration (24 hours)\n";
    echo "5. Test IP/device mismatch security\n\n";
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
