<?php
/**
 * Admin API - Stream Control
 * Start, pause, stop, and delete streams
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
    
    if (!isset($data['action']) || !isset($data['stream_id'])) {
        throw new Exception('Action and stream_id are required');
    }
    
    $streamId = (int)$data['stream_id'];
    $action = $data['action'];
    
    $liveStream = new LiveStream();
    $pdo = db();
    
    switch ($action) {
        case 'start':
            // Start a scheduled stream
            $stmt = $pdo->prepare("
                UPDATE live_streams 
                SET status = 'live', started_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND status = 'scheduled'
            ");
            $stmt->execute([$streamId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Stream not found or cannot be started');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Stream started successfully'
            ]);
            break;
            
        case 'pause':
            // Pause a live stream (set to scheduled temporarily)
            $stmt = $pdo->prepare("
                UPDATE live_streams 
                SET status = 'scheduled' 
                WHERE id = ? AND status = 'live'
            ");
            $stmt->execute([$streamId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Stream not found or cannot be paused');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Stream paused successfully'
            ]);
            break;
            
        case 'stop':
            // End a live stream
            $stmt = $pdo->prepare("
                UPDATE live_streams 
                SET status = 'ended', ended_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND status = 'live'
            ");
            $stmt->execute([$streamId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Stream not found or cannot be stopped');
            }
            
            // Mark all viewers as inactive
            $pdo->prepare("
                UPDATE stream_viewers 
                SET is_active = 0, left_at = CURRENT_TIMESTAMP 
                WHERE stream_id = ? AND is_active = 1
            ")->execute([$streamId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Stream stopped successfully'
            ]);
            break;
            
        case 'delete':
            // Delete a stream (only if not live)
            $stmt = $pdo->prepare("
                DELETE FROM live_streams 
                WHERE id = ? AND status != 'live'
            ");
            $stmt->execute([$streamId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Stream not found or cannot be deleted (may be live)');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Stream deleted successfully'
            ]);
            break;
            
        case 'cancel':
            // Cancel a scheduled stream
            $stmt = $pdo->prepare("
                UPDATE live_streams 
                SET status = 'cancelled' 
                WHERE id = ? AND status = 'scheduled'
            ");
            $stmt->execute([$streamId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Stream not found or cannot be cancelled');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Stream cancelled successfully'
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
