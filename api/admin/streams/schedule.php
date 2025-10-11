<?php
/**
 * Admin API - Schedule Stream
 * Create new scheduled streams
 */

require_once __DIR__ . '/../../../includes/init.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['vendor_id', 'title', 'scheduled_at'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $vendorId = (int)$data['vendor_id'];
    $title = trim($data['title']);
    $description = $data['description'] ?? null;
    $scheduledAt = $data['scheduled_at'];
    
    // Verify vendor exists
    $pdo = db();
    $vendorStmt = $pdo->prepare("SELECT id FROM vendors WHERE id = ?");
    $vendorStmt->execute([$vendorId]);
    
    if (!$vendorStmt->fetch()) {
        throw new Exception('Vendor not found');
    }
    
    // Create stream
    $liveStream = new LiveStream();
    $streamData = [
        'title' => $title,
        'description' => $description,
        'status' => 'scheduled',
        'scheduled_at' => $scheduledAt,
        'chat_enabled' => 1
    ];
    
    $result = $liveStream->createStream($vendorId, $streamData);
    
    if ($result) {
        $streamId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Stream scheduled successfully',
            'stream_id' => $streamId
        ]);
    } else {
        throw new Exception('Failed to create stream');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
