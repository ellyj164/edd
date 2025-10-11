<?php
/**
 * Admin Data Export Functionality
 * Handles CSV exports for various data types
 */

require_once __DIR__ . '/../includes/init.php';

// Require admin authentication
if (!Session::isLoggedIn() || !hasRole('admin')) {
    http_response_code(403);
    exit('Access denied');
}

$type = $_GET['type'] ?? '';
$filename = '';
$data = [];

try {
    $db = Database::getInstance()->getConnection();
    
    switch ($type) {
        case 'users':
            $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
            $stmt = $db->prepare("SELECT id, username, email, first_name, last_name, role, status, created_at, last_login_at FROM users ORDER BY created_at DESC");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'products':
            $filename = 'products_export_' . date('Y-m-d_H-i-s') . '.csv';
            $stmt = $db->prepare("SELECT id, name, sku, price, stock_quantity, status, created_at FROM products ORDER BY created_at DESC");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'orders':
            $filename = 'orders_export_' . date('Y-m-d_H-i-s') . '.csv';
            $stmt = $db->prepare("SELECT id, user_id, total_amount, status, payment_status, created_at FROM orders ORDER BY created_at DESC");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'financials':
            $filename = 'financials_export_' . date('Y-m-d_H-i-s') . '.csv';
            $stmt = $db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_orders,
                    SUM(total_amount) as revenue,
                    AVG(total_amount) as avg_order_value
                FROM orders 
                WHERE status = 'completed'
                GROUP BY DATE(created_at)
                ORDER BY date DESC
                LIMIT 365
            ");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        default:
            throw new Exception('Invalid export type');
    }
    
} catch (Exception $e) {
    // Log error and return error message instead of demo data
    error_log("Export error: " . $e->getMessage());
    http_response_code(500);
    exit('Export failed: ' . htmlspecialchars($e->getMessage()) . '. Please check database connection.');
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Output CSV
$output = fopen('php://output', 'w');

// Write header row
if (!empty($data)) {
    fputcsv($output, array_keys($data[0]));
    
    // Write data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit;