<?php
/**
 * Database compatibility and base model
 * - Centralizes connection via includes/db.php
 * - Provides a legacy Database class wrapper so old code still works
 * - Filters insert/update fields to actual table columns to avoid SQL errors
 */

require_once __DIR__ . '/db.php'; // defines db(), db_transaction(), db_ping()

/**
 * Legacy compatibility: Database class wrapper
 */
if (!class_exists('Database')) {
    final class Database {
        private static ?self $instance = null;
        private function __construct() {}

        public static function getInstance(): self {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function getConnection(): ?PDO {
            try {
                return db();
            } catch (Exception $e) {
                return null;
            }
        }

        public static function query(string $sql, array $params = []): PDOStatement {
            $pdo = db();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        }

        public static function lastInsertId(): string {
            $pdo = db();
            return $pdo->lastInsertId();
        }

        public static function beginTransaction(): bool {
            $pdo = db();
            return $pdo->beginTransaction();
        }

        public static function commit(): bool {
            $pdo = db();
            return $pdo->commit();
        }

        public static function rollback(): bool {
            $pdo = db();
            return $pdo->rollBack();
        }
    }
}

/**
 * Base Model Class
 * - Adds schema-aware create/update to avoid inserting unknown columns
 */
if (!class_exists('BaseModel')) {
    abstract class BaseModel {
        protected $db;
        protected $table;
        private array $columnCache = [];

        public function __construct() {
            try {
                $this->db = db();
            } catch (Exception $e) {
                // Database connection failed - set to null and handle gracefully
                $this->db = null;
                error_log("Database connection failed in BaseModel: " . $e->getMessage());
            }
        }

        protected function getTableColumns(): array {
            if (!$this->db) {
                throw new Exception("Database connection not available");
            }
            
            if (!isset($this->columnCache[$this->table])) {
                try {
                    // Use MySQL/MariaDB DESCRIBE instead of SQLite PRAGMA
                    $stmt = $this->db->query("DESCRIBE {$this->table}");
                    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                    $this->columnCache[$this->table] = array_flip($cols);
                } catch (Exception $e) {
                    throw new Exception("Failed to get table columns for {$this->table}: " . $e->getMessage());
                }
            }
            return $this->columnCache[$this->table];
        }

        protected function filterDataToColumns(array $data): array {
            $columns = $this->getTableColumns();
            return array_intersect_key($data, $columns);
        }

        public function find($id) {
            if (!$this->db) {
                return false; // Return false if no database connection
            }
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        }

        public function findAll($limit = null, $offset = 0) {
            if (!$this->db) {
                return []; // Return empty array if no database connection
            }
            $sql = "SELECT * FROM {$this->table}";
            if ($limit) {
                $sql .= " LIMIT {$limit} OFFSET {$offset}";
            }
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        }

        public function create($data) {
            if (!$this->db) {
                throw new Exception("Database connection not available");
            }
            
            $data = $this->filterDataToColumns($data);
            if (empty($data)) {
                throw new InvalidArgumentException("No valid columns to insert for table {$this->table}");
            }
            $fields = array_keys($data);
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($data));
            return $this->db->lastInsertId();
        }

        public function update($id, $data) {
            $data = $this->filterDataToColumns($data);
            if (empty($data)) {
                throw new InvalidArgumentException("No valid columns to update for table {$this->table}");
            }
            $fields = array_keys($data);
            $setClause = implode(' = ?, ', $fields) . ' = ?';
            $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = ?";
            $values = array_values($data);
            $values[] = $id;
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        }

        public function delete($id) {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            return $stmt->execute([$id]);
        }

        public function count($where = '') {
            $sql = "SELECT COUNT(*) FROM {$this->table}";
            if ($where) {
                $sql .= " WHERE {$where}";
            }
            $stmt = $this->db->query($sql);
            return $stmt->fetchColumn();
        }
    }
}