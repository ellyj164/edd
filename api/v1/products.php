<?php
/**
 * Products API Endpoint v1
 * E-Commerce Platform
 * 
 * GET /api/v1/products - List products with filtering and pagination
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../auth.php';

// Set JSON response header
header('Content-Type: application/json');

// Authenticate request
$apiAuth = new ApiAuth();
$keyData = $apiAuth->authenticate();

if (!$keyData) {
    exit; // Authentication failed, error already sent
}

// Get environment
$environment = $apiAuth->getEnvironment();

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        listProducts($apiAuth, $environment);
        break;
        
    case 'POST':
        // Future: Create product
        sendError(405, 'METHOD_NOT_ALLOWED', 'Method not allowed');
        break;
        
    default:
        sendError(405, 'METHOD_NOT_ALLOWED', 'Method not allowed');
        break;
}

/**
 * List products with filtering and pagination
 */
function listProducts($apiAuth, $environment) {
    $db = db();
    
    // Get query parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $category = $_GET['category'] ?? null;
    $search = $_GET['search'] ?? null;
    $minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
    $maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
    
    // Build query
    $where = ["p.status = 'active'"];
    $params = [];
    
    if ($category) {
        $where[] = "c.slug = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($minPrice !== null) {
        $where[] = "p.price >= ?";
        $params[] = $minPrice;
    }
    
    if ($maxPrice !== null) {
        $where[] = "p.price <= ?";
        $params[] = $maxPrice;
    }
    
    // In sandbox mode, add a marker to products (optional)
    $environmentNote = $environment === 'sandbox' ? ' (Sandbox Data)' : '';
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    try {
        $countSql = "
            SELECT COUNT(*) as total
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE $whereClause
        ";
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $totalItems = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalItems / $limit);
        
        // Get products
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.description,
                p.price,
                p.currency,
                p.stock_quantity,
                p.image_url,
                c.name as category_name,
                c.slug as category_slug,
                u.username as seller_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.seller_id = u.id
            WHERE $whereClause
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format products for API response
        $formattedProducts = array_map(function($product) use ($environmentNote) {
            return [
                'id' => intval($product['id']),
                'name' => $product['name'] . $environmentNote,
                'description' => $product['description'],
                'price' => floatval($product['price']),
                'currency' => $product['currency'] ?? 'USD',
                'stock_quantity' => intval($product['stock_quantity']),
                'image_url' => $product['image_url'] ? getFullUrl($product['image_url']) : null,
                'category' => [
                    'name' => $product['category_name'],
                    'slug' => $product['category_slug']
                ],
                'seller' => $product['seller_name']
            ];
        }, $products);
        
        // Log the request
        $apiAuth->logRequest('/api/v1/products', 'GET', 200, json_encode($formattedProducts));
        
        // Send response
        ApiAuth::sendSuccess($formattedProducts, [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => intval($totalItems),
            'per_page' => $limit
        ]);
        
    } catch (Exception $e) {
        error_log("Products API error: " . $e->getMessage());
        $apiAuth->logRequest('/api/v1/products', 'GET', 500, null);
        sendError(500, 'SERVER_ERROR', 'An error occurred while fetching products');
    }
}

/**
 * Send error response
 */
function sendError($statusCode, $errorCode, $message) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => $errorCode,
            'message' => $message
        ]
    ]);
    exit;
}

/**
 * Get full URL for relative paths
 */
function getFullUrl($path) {
    if (empty($path)) {
        return null;
    }
    
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    return $protocol . '://' . $host . '/' . ltrim($path, '/');
}
