<?php
/**
 * Product Offers API
 * Handle customer offers on products
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!Session::isLoggedIn()) {
    errorResponse('Please login to make an offer', 401);
}

$userId = Session::getUserId();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'submit':
                $productId = (int)($input['product_id'] ?? 0);
                $offerPrice = (float)($input['offer_price'] ?? 0);
                $message = sanitizeInput($input['message'] ?? '');
                
                // Validation
                if ($productId <= 0) {
                    errorResponse('Invalid product');
                }
                
                if ($offerPrice <= 0) {
                    errorResponse('Invalid offer price');
                }
                
                // Check if product exists
                $product = new Product();
                $productData = $product->find($productId);
                
                if (!$productData) {
                    errorResponse('Product not found');
                }
                
                // Check if offer is reasonable (at least 30% of product price)
                if ($offerPrice < ($productData['price'] * 0.3)) {
                    errorResponse('Offer price is too low. Please submit a reasonable offer.');
                }
                
                // Check for existing pending offer
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("
                    SELECT COUNT(*) FROM product_offers 
                    WHERE product_id = ? AND user_id = ? AND status = 'pending'
                ");
                $stmt->execute([$productId, $userId]);
                
                if ($stmt->fetchColumn() > 0) {
                    errorResponse('You already have a pending offer for this product');
                }
                
                // Create offer
                $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
                $stmt = $db->prepare("
                    INSERT INTO product_offers 
                    (product_id, user_id, offer_price, message, status, expires_at, created_at)
                    VALUES (?, ?, ?, ?, 'pending', ?, NOW())
                ");
                $stmt->execute([$productId, $userId, $offerPrice, $message, $expiresAt]);
                
                successResponse(['status' => 'success', 'offer_id' => $db->lastInsertId()], 'Offer submitted successfully');
                break;
                
            case 'cancel':
                $offerId = (int)($input['offer_id'] ?? 0);
                
                if ($offerId <= 0) {
                    errorResponse('Invalid offer');
                }
                
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("
                    UPDATE product_offers 
                    SET status = 'declined' 
                    WHERE id = ? AND user_id = ? AND status = 'pending'
                ");
                $stmt->execute([$offerId, $userId]);
                
                if ($stmt->rowCount() > 0) {
                    successResponse(['status' => 'success'], 'Offer cancelled');
                } else {
                    errorResponse('Unable to cancel offer');
                }
                break;
                
            default:
                errorResponse('Invalid action');
                break;
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get user's offers
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT po.*, p.name as product_name, p.price as product_price
            FROM product_offers po
            JOIN products p ON po.product_id = p.id
            WHERE po.user_id = ?
            ORDER BY po.created_at DESC
        ");
        $stmt->execute([$userId]);
        $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        successResponse(['offers' => $offers]);
        
    } else {
        errorResponse('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    Logger::error('Product offers API error: ' . $e->getMessage());
    errorResponse('An error occurred', 500);
}
