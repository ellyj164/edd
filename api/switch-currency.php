<?php
/**
 * Currency Switcher API Endpoint
 * Allows users to manually change their preferred currency
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['currency_code'])) {
    echo json_encode(['success' => false, 'message' => 'Currency code is required']);
    exit;
}

$currencyCode = strtoupper($input['currency_code']);

// Validate currency code
$validCurrencies = ['USD', 'EUR', 'RWF'];
if (!in_array($currencyCode, $validCurrencies)) {
    echo json_encode(['success' => false, 'message' => 'Invalid currency code']);
    exit;
}

try {
    $currency = Currency::getInstance();
    $currency->setCurrency($currencyCode);
    
    // Get currency info for response
    $symbol = $currency->getSymbol($currencyCode);
    
    echo json_encode([
        'success' => true,
        'currency_code' => $currencyCode,
        'currency_symbol' => $symbol,
        'message' => 'Currency updated successfully'
    ]);
} catch (Exception $e) {
    error_log("Currency switch error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update currency']);
}
exit;
