<?php
/**
 * Admin API - Chat Moderation
 * Moderation tools for stream chat
 */

require_once __DIR__ . '/../../../includes/init.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

try {
    $pdo = db();
    $action = $_GET['action'] ?? ($_POST['action'] ?? null);
    
    switch ($action) {
        case 'get_comments':
            // Get comments for a specific stream
            if (!isset($_GET['stream_id'])) {
                throw new Exception('Stream ID is required');
            }
            
            $streamId = (int)$_GET['stream_id'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            
            $stmt = $pdo->prepare("
                SELECT si.*, u.username, u.email
                FROM stream_interactions si
                LEFT JOIN users u ON si.user_id = u.id
                WHERE si.stream_id = ? AND si.interaction_type = 'comment'
                ORDER BY si.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$streamId, $limit]);
            $comments = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'comments' => $comments
            ]);
            break;
            
        case 'delete_comment':
            // Delete a specific comment
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['comment_id'])) {
                throw new Exception('Comment ID is required');
            }
            
            $commentId = (int)$data['comment_id'];
            
            $stmt = $pdo->prepare("
                DELETE FROM stream_interactions 
                WHERE id = ? AND interaction_type = 'comment'
            ");
            $stmt->execute([$commentId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Comment not found');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);
            break;
            
        case 'ban_user':
            // Ban a user from commenting (would need a banned_users table)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['user_id']) || !isset($data['stream_id'])) {
                throw new Exception('User ID and Stream ID are required');
            }
            
            $userId = (int)$data['user_id'];
            $streamId = (int)$data['stream_id'];
            
            // Delete all comments from this user in this stream
            $stmt = $pdo->prepare("
                DELETE FROM stream_interactions 
                WHERE stream_id = ? AND user_id = ? AND interaction_type = 'comment'
            ");
            $stmt->execute([$streamId, $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'User comments removed from stream',
                'deleted_count' => $stmt->rowCount()
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
