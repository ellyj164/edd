<?php
/**
 * Stream Viewers API
 * Track and retrieve viewer information
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? $_GET['action'] ?? '';
    
    if (!isset($data['stream_id']) && !isset($_GET['stream_id'])) {
        throw new Exception('Stream ID is required');
    }
    
    $streamId = (int)($data['stream_id'] ?? $_GET['stream_id']);
    $streamViewer = new StreamViewer();
    
    switch ($action) {
        case 'join':
            // Add viewer to stream
            $userId = Session::isLoggedIn() ? Session::getUserId() : null;
            $sessionId = session_id();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $viewerId = $streamViewer->addViewer($streamId, $userId, $sessionId, $ipAddress, $userAgent);
            
            // Update viewer count
            $liveStream = new LiveStream();
            $activeViewers = $streamViewer->getActiveViewers($streamId);
            $liveStream->updateViewerCount($streamId, count($activeViewers));
            
            echo json_encode([
                'success' => true,
                'viewer_id' => $viewerId,
                'viewer_count' => count($activeViewers)
            ]);
            break;
            
        case 'leave':
            if (!isset($data['viewer_id'])) {
                throw new Exception('Viewer ID is required');
            }
            
            $viewerId = (int)$data['viewer_id'];
            $streamViewer->markViewerLeft($viewerId);
            
            // Update viewer count
            $liveStream = new LiveStream();
            $activeViewers = $streamViewer->getActiveViewers($streamId);
            $liveStream->updateViewerCount($streamId, count($activeViewers));
            
            echo json_encode([
                'success' => true,
                'viewer_count' => count($activeViewers)
            ]);
            break;
            
        case 'list':
            // Get active viewers (requires vendor authentication)
            if (!Session::isLoggedIn()) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Authentication required'
                ]);
                exit;
            }
            
            $activeViewers = $streamViewer->getActiveViewers($streamId);
            
            echo json_encode([
                'success' => true,
                'viewers' => array_map(function($viewer) {
                    return [
                        'id' => $viewer['id'],
                        'username' => $viewer['username'] ?? 'Guest',
                        'joined_at' => $viewer['joined_at']
                    ];
                }, $activeViewers),
                'count' => count($activeViewers)
            ]);
            break;
            
        case 'count':
            // Get current viewer count (public)
            $activeViewers = $streamViewer->getActiveViewers($streamId);
            
            echo json_encode([
                'success' => true,
                'count' => count($activeViewers)
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
