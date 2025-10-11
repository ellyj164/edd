<?php
/**
 * Email Tables Migration
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

echo "=== Email System Migration ===\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Read and execute email schema
    $schema = file_get_contents(__DIR__ . '/database/email_schema.sql');
    
    if (!$schema) {
        throw new Exception("Could not read email schema file");
    }
    
    // Split into individual statements and execute
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    $tableStatements = [];
    $indexStatements = [];
    
    // Separate CREATE TABLE and CREATE INDEX statements
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        if (stripos($statement, 'CREATE TABLE') === 0) {
            $tableStatements[] = $statement;
        } elseif (stripos($statement, 'CREATE INDEX') === 0) {
            $indexStatements[] = $statement;
        } else {
            $tableStatements[] = $statement; // Other statements like INSERT
        }
    }
    
    // Execute table creation first
    foreach ($tableStatements as $statement) {
        echo "Executing table: " . substr($statement, 0, 50) . "...\n";
        $db->exec($statement);
    }
    
    // Then execute index creation
    foreach ($indexStatements as $statement) {
        echo "Executing index: " . substr($statement, 0, 50) . "...\n";
        $db->exec($statement);
    }
    
    echo "✅ Email system tables created successfully\n";
    
    // Test email functionality
    echo "\n=== Testing Email System ===\n";
    
    $emailService = EmailService::getInstance();
    
    // Queue a test email (won't actually send without proper SMTP)
    $testResult = $emailService->send(
        'test@example.com',
        'Email System Test',
        'welcome',
        [
            'user' => [
                'first_name' => 'Test',
                'email' => 'test@example.com',
                'role' => 'customer'
            ]
        ]
    );
    
    if ($testResult) {
        echo "✅ Test email queued successfully\n";
    } else {
        echo "❌ Test email queueing failed\n";
    }
    
    // Test token generation
    $token = EmailTokenManager::generateToken(1, 'email_verification');
    if ($token) {
        echo "✅ Email token generation working\n";
        
        // Test token verification
        $verifyResult = EmailTokenManager::verifyToken($token, 'email_verification');
        if ($verifyResult) {
            echo "✅ Email token verification working\n";
        } else {
            echo "❌ Email token verification failed\n";
        }
    } else {
        echo "❌ Email token generation failed\n";
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "Email system is ready for use!\n";
    echo "Remember to:\n";
    echo "1. Configure SMTP settings in config/config.php\n";
    echo "2. Set up cron job for process_email_queue.php\n";
    echo "3. Test email delivery in production\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>