<?php
/**
 * Live Streams API
 * Fetch active live streams for public page
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

try {
    $liveStream = new LiveStream();
    
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Get active live streams
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $streams = $liveStream->getActiveStreams($limit);
            
            echo json_encode([
                'success' => true,
                'streams' => $streams,
                'count' => count($streams)
            ]);
            break;
            
        case 'get':
            // Get single stream details
            if (!isset($_GET['stream_id'])) {
                throw new Exception('Stream ID is required');
            }
            
            $streamId = (int)$_GET['stream_id'];
            $stream = $liveStream->getStreamById($streamId);
            
            if (!$stream) {
                throw new Exception('Stream not found');
            }
            
            // Get stream statistics
            $stats = $liveStream->getStreamStats($streamId);
            
            // Get stream products
            $products = $liveStream->getStreamProducts($streamId);
            
            echo json_encode([
                'success' => true,
                'stream' => $stream,
                'stats' => $stats,
                'products' => $products
            ]);
            break;
            
        case 'products':
            // Get products for a stream
            if (!isset($_GET['stream_id'])) {
                throw new Exception('Stream ID is required');
            }
            
            $streamId = (int)$_GET['stream_id'];
            $products = $liveStream->getStreamProducts($streamId);
            
            echo json_encode([
                'success' => true,
                'products' => $products
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
