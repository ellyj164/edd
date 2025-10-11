#!/usr/bin/env php
<?php
/**
 * Simple migration runner for EPD database
 * Usage: php database/migrate.php [up|down|status]
 */

declare(strict_types=1);

// Load dependencies
require_once __DIR__ . '/../includes/db.php';

function loadEnv($path) {
    if (!file_exists($path)) return;
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// Load environment
loadEnv(__DIR__ . '/../.env');

try {
    $pdo = db();
    $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Ensure migrations table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        batch INT NOT NULL DEFAULT 1,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$command = $argv[1] ?? 'status';
$migrationsDir = __DIR__ . '/migrations';

switch ($command) {
    case 'up':
        echo "Running migrations...\n";
        runMigrations($pdo, $migrationsDir);
        break;
        
    case 'down':
        echo "Rolling back last migration batch...\n";
        rollbackMigrations($pdo, $migrationsDir);
        break;
        
    case 'status':
        showMigrationStatus($pdo, $migrationsDir);
        break;
        
    default:
        echo "Usage: php database/migrate.php [up|down|status]\n";
        exit(1);
}

function runMigrations($pdo, $migrationsDir) {
    $files = glob($migrationsDir . '/*.php');
    sort($files);
    
    $stmt = $pdo->prepare("SELECT filename FROM migrations");
    $stmt->execute();
    $executed = array_column($stmt->fetchAll(), 'filename');
    
    $currentBatch = 1;
    $stmt = $pdo->prepare("SELECT MAX(batch) FROM migrations");
    $stmt->execute();
    if ($maxBatch = $stmt->fetchColumn()) {
        $currentBatch = $maxBatch + 1;
    }
    
    $newMigrations = 0;
    foreach ($files as $file) {
        $filename = basename($file);
        
        if (in_array($filename, $executed)) {
            continue;
        }
        
        echo "Running migration: $filename\n";
        
        $migration = require $file;
        
        try {
            $pdo->beginTransaction();
            
            // Execute the 'up' migration
            $pdo->exec($migration['up']);
            
            // Record the migration
            $stmt = $pdo->prepare("INSERT INTO migrations (filename, batch) VALUES (?, ?)");
            $stmt->execute([$filename, $currentBatch]);
            
            $pdo->commit();
            echo "✓ $filename completed\n";
            $newMigrations++;
            
        } catch (Exception $e) {
            $pdo->rollback();
            echo "✗ $filename failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    if ($newMigrations === 0) {
        echo "No new migrations to run.\n";
    } else {
        echo "Ran $newMigrations migration(s) successfully.\n";
    }
}

function rollbackMigrations($pdo, $migrationsDir) {
    $stmt = $pdo->prepare("SELECT MAX(batch) FROM migrations");
    $stmt->execute();
    $maxBatch = $stmt->fetchColumn();
    
    if (!$maxBatch) {
        echo "No migrations to rollback.\n";
        return;
    }
    
    $stmt = $pdo->prepare("SELECT filename FROM migrations WHERE batch = ? ORDER BY filename DESC");
    $stmt->execute([$maxBatch]);
    $migrations = $stmt->fetchAll();
    
    foreach ($migrations as $migration) {
        $filename = $migration['filename'];
        $file = $migrationsDir . '/' . $filename;
        
        if (!file_exists($file)) {
            echo "Migration file not found: $filename\n";
            continue;
        }
        
        echo "Rolling back: $filename\n";
        
        $migrationData = require $file;
        
        try {
            $pdo->beginTransaction();
            
            // Execute the 'down' migration
            $pdo->exec($migrationData['down']);
            
            // Remove the migration record
            $stmt = $pdo->prepare("DELETE FROM migrations WHERE filename = ?");
            $stmt->execute([$filename]);
            
            $pdo->commit();
            echo "✓ $filename rolled back\n";
            
        } catch (Exception $e) {
            $pdo->rollback();
            echo "✗ $filename rollback failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

function showMigrationStatus($pdo, $migrationsDir) {
    $files = glob($migrationsDir . '/*.php');
    sort($files);
    
    $stmt = $pdo->prepare("SELECT filename, batch, executed_at FROM migrations ORDER BY filename");
    $stmt->execute();
    $executed = [];
    foreach ($stmt->fetchAll() as $row) {
        $executed[$row['filename']] = $row;
    }
    
    echo "Migration Status:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-40s %-10s %-20s\n", "Migration", "Status", "Executed At");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($files as $file) {
        $filename = basename($file);
        
        if (isset($executed[$filename])) {
            $status = "✓ Run";
            $executedAt = $executed[$filename]['executed_at'];
        } else {
            $status = "✗ Pending";
            $executedAt = "";
        }
        
        printf("%-40s %-10s %-20s\n", $filename, $status, $executedAt);
    }
    echo str_repeat("-", 80) . "\n";
}