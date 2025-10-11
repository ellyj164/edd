<?php
/**
 * Stream Interactions API
 * Handle likes, dislikes, and comments (requires login)
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!Session::isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required',
            'redirect' => '/login.php?return=' . urlencode($_SERVER['HTTP_REFERER'] ?? '/live.php')
        ]);
        exit;
    }
    
    $userId = Session::getUserId();
    $streamInteraction = new StreamInteraction();
    
    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    if (!isset($data['stream_id'])) {
        throw new Exception('Stream ID is required');
    }
    
    $streamId = (int)$data['stream_id'];
    
    switch ($action) {
        case 'like':
            $result = $streamInteraction->addInteraction($streamId, $userId, 'like');
            echo json_encode([
                'success' => $result,
                'message' => 'Liked successfully'
            ]);
            break;
            
        case 'dislike':
            $result = $streamInteraction->addInteraction($streamId, $userId, 'dislike');
            echo json_encode([
                'success' => $result,
                'message' => 'Disliked successfully'
            ]);
            break;
            
        case 'unlike':
        case 'undislike':
            $type = $action === 'unlike' ? 'like' : 'dislike';
            $result = $streamInteraction->removeInteraction($streamId, $userId, $type);
            echo json_encode([
                'success' => $result,
                'message' => 'Removed successfully'
            ]);
            break;
            
        case 'comment':
            if (!isset($data['comment']) || empty(trim($data['comment']))) {
                throw new Exception('Comment text is required');
            }
            
            $commentText = trim($data['comment']);
            $result = $streamInteraction->addInteraction($streamId, $userId, 'comment', $commentText);
            
            echo json_encode([
                'success' => $result,
                'message' => 'Comment posted successfully',
                'comment' => [
                    'text' => $commentText,
                    'username' => Session::getUsername(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            break;
            
        case 'get_comments':
            $limit = isset($data['limit']) ? (int)$data['limit'] : 100;
            $comments = $streamInteraction->getStreamComments($streamId, $limit);
            
            echo json_encode([
                'success' => true,
                'comments' => $comments
            ]);
            break;
            
        case 'get_user_interaction':
            $interaction = $streamInteraction->getUserInteraction($streamId, $userId);
            
            echo json_encode([
                'success' => true,
                'interaction' => $interaction
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
