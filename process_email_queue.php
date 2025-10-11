<?php
/**
 * Email Queue Processor
 * E-Commerce Platform
 * 
 * This script processes the email queue and should be run via cron job
 * Usage: php process_email_queue.php
 */

require_once __DIR__ . '/includes/init.php';

echo "Email Queue Processor Starting...\n";

try {
    $emailService = EmailService::getInstance();
    $processed = $emailService->processQueue(50); // Process up to 50 emails
    
    echo "Processed {$processed} emails from queue.\n";
    
    if ($processed > 0) {
        Logger::info("Email queue processed: {$processed} emails sent");
    }
    
} catch (Exception $e) {
    echo "Error processing email queue: " . $e->getMessage() . "\n";
    Logger::error("Email queue processing failed: " . $e->getMessage());
    exit(1);
}

echo "Email Queue Processor Complete.\n";
?>