<?php
/**
 * API: Delete Payment Method
 * Removes a saved payment method from the user's account
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/stripe/init_stripe.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Require authentication
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

try {
    $userId = Session::getUserId();
    $input = json_decode(file_get_contents('php://input'), true);
    $paymentMethodId = (int)($input['payment_method_id'] ?? 0);
    
    if (!$paymentMethodId) {
        throw new Exception('Payment method ID is required');
    }
    
    $db = db();
    
    // Get payment method details and verify ownership
    $stmt = $db->prepare("SELECT stripe_payment_method_id FROM user_payment_methods WHERE id = ? AND user_id = ?");
    $stmt->execute([$paymentMethodId, $userId]);
    $paymentMethod = $stmt->fetch();
    
    if (!$paymentMethod) {
        throw new Exception('Payment method not found');
    }
    
    // Detach payment method from Stripe (optional, but recommended)
    if (!empty($paymentMethod['stripe_payment_method_id'])) {
        try {
            $stripe = initStripe();
            $stripe->paymentMethods->detach($paymentMethod['stripe_payment_method_id']);
        } catch (Exception $e) {
            // Log but don't fail if Stripe detach fails
            error_log("Failed to detach payment method from Stripe: " . $e->getMessage());
        }
    }
    
    // Delete from database
    $stmt = $db->prepare("DELETE FROM user_payment_methods WHERE id = ? AND user_id = ?");
    $stmt->execute([$paymentMethodId, $userId]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
