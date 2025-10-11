<?php
/**
 * Run User Account Management Migration
 * Executes the 008_user_account_management.sql migration
 */

require_once __DIR__ . '/includes/init.php';

// Check if user is admin or in admin bypass mode
if (!defined('ADMIN_BYPASS') || !ADMIN_BYPASS) {
    Session::requireLogin();
    if (Session::getUserRole() !== 'admin') {
        die('Admin access required');
    }
}

try {
    $db = db();
    
    echo "Running user account management migration...\n";
    
    // Read the migration file
    $migrationFile = __DIR__ . '/migrations/008_user_account_management.sql';
    
    if (!file_exists($migrationFile)) {
        die("Migration file not found: $migrationFile\n");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split by semicolons and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { return !empty($stmt) && !preg_match('/^\s*--/', $stmt); }
    );
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        try {
            $db->exec($statement);
            $success++;
            echo ".";
        } catch (PDOException $e) {
            $errors++;
            echo "E";
            // Log error but continue
            error_log("Migration statement failed: " . $e->getMessage());
        }
    }
    
    echo "\n\nMigration completed!\n";
    echo "Successful: $success\n";
    echo "Errors: $errors\n";
    
    // Verify tables were created
    echo "\nVerifying tables...\n";
    $tables = ['user_addresses', 'user_payment_methods', 'user_preferences', 'wallets', 'wallet_transactions'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $table exists\n";
        } else {
            echo "✗ $table missing\n";
        }
    }
    
    echo "\nMigration process complete!\n";
    
} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
