#!/usr/bin/env php
<?php
/**
 * Database Migration Runner
 * E-Commerce Platform - MariaDB Migration System
 * 
 * Usage: php scripts/migrate.php [options]
 * Options:
 *   --fresh    Drop all tables and run all migrations from scratch
 *   --seed     Run seed data after migrations
 *   --status   Show migration status
 *   --rollback Rollback last migration batch
 *   --help     Show this help message
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

class DatabaseMigrator {
    private $pdo;
    private $migrationsPath;
    private $verbose = true;
    
    public function __construct() {
        try {
            $this->pdo = db();
            $this->migrationsPath = __DIR__ . '/../db/sql/';
            
            // Ensure migrations table exists
            $this->createMigrationsTable();
            
        } catch (Exception $e) {
            $this->error("Database connection failed: " . $e->getMessage());
            exit(1);
        }
    }
    
    public function run($options = []) {
        $this->info("=== FezaMarket Database Migration System ===\n");
        
        if (isset($options['help'])) {
            $this->showHelp();
            return;
        }
        
        if (isset($options['status'])) {
            $this->showStatus();
            return;
        }
        
        if (isset($options['rollback'])) {
            $this->rollbackLastBatch();
            return;
        }
        
        if (isset($options['fresh'])) {
            $this->info("🔄 Running fresh migration (dropping all tables)...\n");
            $this->dropAllTables();
            $this->createMigrationsTable();
        }
        
        $this->runMigrations();
        
        if (isset($options['seed'])) {
            $this->runSeedData();
        }
        
        $this->success("\n✅ Migration completed successfully!");
    }
    
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `batch` int(11) NOT NULL DEFAULT 1,
            `executed_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_filename` (`filename`),
            KEY `idx_batch` (`batch`),
            KEY `idx_executed_at` (`executed_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
    }
    
    private function runMigrations() {
        $migrationFiles = $this->getMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        $currentBatch = $this->getCurrentBatch() + 1;
        
        $pendingMigrations = array_diff($migrationFiles, $executedMigrations);
        
        if (empty($pendingMigrations)) {
            $this->info("📋 No new migrations to run.");
            return;
        }
        
        $this->info("📦 Running " . count($pendingMigrations) . " migrations...\n");
        
        foreach ($pendingMigrations as $file) {
            $this->runMigrationFile($file, $currentBatch);
        }
    }
    
    private function runMigrationFile($filename, $batch) {
        $filepath = $this->migrationsPath . $filename;
        
        if (!file_exists($filepath)) {
            $this->error("Migration file not found: $filename");
            return false;
        }
        
        $this->info("⚙️  Running: $filename");
        
        try {
            $sql = file_get_contents($filepath);
            
            // Remove comments and split statements
            $statements = $this->parseSQL($sql);
            
            $this->pdo->beginTransaction();
            
            $executed = 0;
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $this->pdo->exec($statement);
                    $executed++;
                }
            }
            
            // Record migration
            if ($filename !== '99_seed_data.sql') {
                $stmt = $this->pdo->prepare("INSERT INTO migrations (filename, batch) VALUES (?, ?)");
                $stmt->execute([$filename, $batch]);
            }
            
            $this->pdo->commit();
            
            $this->success("   ✅ Executed $executed statements");
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            $this->error("   ❌ Failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function runSeedData() {
        $seedFile = '99_seed_data.sql';
        $this->info("\n🌱 Running seed data...");
        
        if ($this->runMigrationFile($seedFile, 999)) {
            $this->success("   ✅ Seed data loaded successfully");
        }
    }
    
    private function getMigrationFiles() {
        $files = glob($this->migrationsPath . '*.sql');
        $filenames = array_map('basename', $files);
        sort($filenames);
        
        // Exclude seed data from regular migrations
        return array_filter($filenames, function($file) {
            return $file !== '99_seed_data.sql';
        });
    }
    
    private function getExecutedMigrations() {
        try {
            $stmt = $this->pdo->query("SELECT filename FROM migrations ORDER BY executed_at");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getCurrentBatch() {
        try {
            $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM migrations");
            $result = $stmt->fetch();
            return (int)($result['max_batch'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function showStatus() {
        $migrationFiles = $this->getMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        $this->info("Migration Status:\n");
        
        foreach ($migrationFiles as $file) {
            $status = in_array($file, $executedMigrations) ? "✅ Executed" : "⏳ Pending";
            $this->info("  $file - $status");
        }
        
        $pending = count($migrationFiles) - count($executedMigrations);
        $this->info("\nSummary: " . count($executedMigrations) . " executed, $pending pending");
    }
    
    private function rollbackLastBatch() {
        $this->info("🔄 Rolling back last migration batch...\n");
        
        $lastBatch = $this->getCurrentBatch();
        if ($lastBatch === 0) {
            $this->info("No migrations to rollback.");
            return;
        }
        
        $stmt = $this->pdo->prepare("SELECT filename FROM migrations WHERE batch = ? ORDER BY executed_at DESC");
        $stmt->execute([$lastBatch]);
        $migrationsToRollback = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($migrationsToRollback)) {
            $this->info("No migrations found in batch $lastBatch");
            return;
        }
        
        $this->warning("⚠️  This will rollback " . count($migrationsToRollback) . " migrations.");
        $this->warning("⚠️  This operation cannot be undone and will drop tables!");
        
        if (!$this->confirm("Are you sure you want to continue? (y/N): ")) {
            $this->info("Rollback cancelled.");
            return;
        }
        
        try {
            $this->dropAllTables();
            $this->createMigrationsTable();
            
            // Remove rollback records
            $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE batch = ?");
            $stmt->execute([$lastBatch]);
            
            $this->success("✅ Rollback completed successfully.");
            
        } catch (Exception $e) {
            $this->error("❌ Rollback failed: " . $e->getMessage());
        }
    }
    
    private function dropAllTables() {
        $this->info("🗑️  Dropping all tables...");
        
        try {
            // Disable foreign key checks
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Get all tables
            $stmt = $this->pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Drop each table
            foreach ($tables as $table) {
                $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
            }
            
            // Re-enable foreign key checks
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $this->success("   ✅ Dropped " . count($tables) . " tables");
            
        } catch (Exception $e) {
            $this->error("   ❌ Failed to drop tables: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function parseSQL($sql) {
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Split by semicolons but not within quoted strings
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar) {
                $inString = false;
            } elseif (!$inString && $char === ';') {
                $statements[] = trim($current);
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $statements[] = trim($current);
        }
        
        return array_filter($statements, function($stmt) {
            return !empty(trim($stmt));
        });
    }
    
    private function confirm($message) {
        if (php_sapi_name() !== 'cli') {
            return false; // Non-interactive mode
        }
        
        echo $message;
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        return strtolower(trim($line)) === 'y';
    }
    
    private function showHelp() {
        echo "FezaMarket Database Migration System\n\n";
        echo "Usage: php scripts/migrate.php [options]\n\n";
        echo "Options:\n";
        echo "  --fresh    Drop all tables and run all migrations from scratch\n";
        echo "  --seed     Run seed data after migrations\n";
        echo "  --status   Show migration status\n";
        echo "  --rollback Rollback last migration batch\n";
        echo "  --help     Show this help message\n\n";
        echo "Examples:\n";
        echo "  php scripts/migrate.php                # Run pending migrations\n";
        echo "  php scripts/migrate.php --fresh --seed # Fresh install with seed data\n";
        echo "  php scripts/migrate.php --status       # Check migration status\n";
    }
    
    private function info($message) {
        if ($this->verbose) {
            echo $message . "\n";
        }
    }
    
    private function success($message) {
        if ($this->verbose) {
            echo "\033[32m" . $message . "\033[0m\n";
        }
    }
    
    private function warning($message) {
        if ($this->verbose) {
            echo "\033[33m" . $message . "\033[0m\n";
        }
    }
    
    private function error($message) {
        echo "\033[31m" . $message . "\033[0m\n";
    }
}

// Parse command line arguments
$options = [];
$args = array_slice($argv, 1);

foreach ($args as $arg) {
    if (strpos($arg, '--') === 0) {
        $options[substr($arg, 2)] = true;
    }
}

try {
    $migrator = new DatabaseMigrator();
    $migrator->run($options);
    exit(0);
} catch (Exception $e) {
    echo "\033[31m❌ Migration failed: " . $e->getMessage() . "\033[0m\n";
    exit(1);
}