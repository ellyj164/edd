<?php
/**
 * Database Connection - MariaDB/MySQL Only
 * E-Commerce Platform - Production Ready
 */
declare(strict_types=1);

if (!function_exists('db')) {
    function db(): ?PDO {
        static $pdo = null;
        if ($pdo instanceof PDO) return $pdo;

        // MariaDB/MySQL configuration only - no SQLite fallback
        $host     = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
        $port     = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: '3306');
        $dbname   = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'ecommerce_platform');
        $user     = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'duns1');
        $pass     = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: 'Tumukunde');
        $charset  = defined('DB_CHARSET') ? DB_CHARSET : (getenv('DB_CHARSET') ?: 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            // Set MySQL-specific settings for optimal performance
            $pdo->exec("SET SESSION sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        } catch (PDOException $e) {
            error_log('MariaDB connection failed: '.$e->getMessage());
            throw new Exception("Database connection failed. Please check your MariaDB configuration.");
        }
        
        return $pdo;
    }
}

if (!function_exists('db_transaction')) {
    function db_transaction(callable $fn) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $t) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $t;
        }
    }
}

if (!function_exists('db_ping')) {
    function db_ping(): bool {
        try {
            db()->query('SELECT 1')->fetchColumn();
            return true;
        } catch (Throwable $t) {
            return false;
        }
    }
}