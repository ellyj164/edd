<?php
/**
 * End Stream API
 * Handle stream end with save/delete options
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!Session::isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required'
        ]);
        exit;
    }
    
    $userId = Session::getUserId();
    
    // Check if user is a vendor
    $vendor = new Vendor();
    $vendorInfo = $vendor->findByUserId($userId);
    
    if (!$vendorInfo) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Vendor access required'
        ]);
        exit;
    }
    
    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['stream_id'])) {
        throw new Exception('Stream ID is required');
    }
    
    $streamId = (int)$data['stream_id'];
    $action = $data['action'] ?? 'save'; // 'save' or 'delete'
    
    // Verify the stream belongs to this vendor
    $liveStream = new LiveStream();
    $stream = $liveStream->getStreamById($streamId);
    
    if (!$stream || $stream['vendor_id'] != $vendorInfo['id']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied'
        ]);
        exit;
    }
    
    // Verify stream is live
    if ($stream['status'] !== 'live') {
        throw new Exception('Stream is not currently live');
    }
    
    // End the stream
    $liveStream->endStream($streamId);
    
    if ($action === 'save') {
        // Get stream statistics
        $stats = $liveStream->getStreamStats($streamId);
        
        // Calculate duration
        $duration = strtotime('now') - strtotime($stream['started_at']);
        
        // Save the stream
        $savedStream = new SavedStream();
        $result = $savedStream->saveStream([
            'stream_id' => $streamId,
            'vendor_id' => $vendorInfo['id'],
            'title' => $stream['title'],
            'description' => $stream['description'],
            'video_url' => $data['video_url'] ?? $stream['stream_url'],
            'thumbnail_url' => $stream['thumbnail_url'],
            'duration' => $duration,
            'viewer_count' => $stats['total_viewers'] ?? 0,
            'total_revenue' => $stats['total_revenue'] ?? 0.00,
            'streamed_at' => $stream['started_at']
        ]);
        
        if (!$result) {
            throw new Exception('Failed to save stream');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Stream ended and saved successfully',
            'action' => 'saved',
            'stats' => [
                'duration' => $duration,
                'viewers' => $stats['total_viewers'] ?? 0,
                'revenue' => $stats['total_revenue'] ?? 0.00,
                'likes' => $stats['likes_count'] ?? 0,
                'comments' => $stats['comments_count'] ?? 0,
                'orders' => $stats['orders_count'] ?? 0
            ]
        ]);
        
    } else if ($action === 'delete') {
        // Just end the stream without saving
        echo json_encode([
            'success' => true,
            'message' => 'Stream ended successfully',
            'action' => 'deleted'
        ]);
        
    } else {
        throw new Exception('Invalid action. Use "save" or "delete"');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
