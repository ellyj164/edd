<?php
/**
 * Store Intended Action API Endpoint
 * Used to store user's intended action before redirecting to login
 */

session_start();

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Store the intended action in session
$_SESSION['intended_action'] = [
    'action' => $input['action'],
    'product_id' => $input['product_id'] ?? null,
    'quantity' => $input['quantity'] ?? 1,
    'timestamp' => time()
];

echo json_encode(['success' => true]);
exit;
