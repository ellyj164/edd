<?php
/**
 * Example Usage of Standardized Database Access
 * Demonstrates how to use the new db() function and helpers
 */

require __DIR__ . '/includes/db.php';

echo "=== Example Database Usage ===\n\n";

// Example 1: Basic usage
echo "Example 1: Basic query\n";
try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT ? as message');
    $stmt->execute(['Hello from standardized DB!']);
    $result = $stmt->fetch();
    echo "Result: " . $result['message'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 2: Transaction usage
echo "Example 2: Transaction\n";
try {
    $result = db_transaction(function($pdo) {
        // This would be a real transaction in practice
        $stmt = $pdo->prepare('SELECT ? as transaction_test');
        $stmt->execute(['Transaction works!']);
        return $stmt->fetch();
    });
    echo "Transaction result: " . $result['transaction_test'] . "\n";
} catch (Exception $e) {
    echo "Transaction error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 3: Health check
echo "Example 3: Health check\n";
if (db_ping()) {
    echo "Database is healthy!\n";
} else {
    echo "Database is not available\n";
}

echo "\n";

// Example 4: Backward compatibility
echo "Example 4: Using legacy Database class\n";
require __DIR__ . '/includes/database.php';

try {
    $legacy_db = Database::getInstance();
    $legacy_pdo = $legacy_db->getConnection();
    echo "Legacy Database class still works!\n";
    echo "Same PDO instance: " . (db() === $legacy_pdo ? 'Yes' : 'No') . "\n";
} catch (Exception $e) {
    echo "Legacy class error: " . $e->getMessage() . "\n";
}

echo "\n=== Example Complete ===\n";
?>