<?php
/**
 * Live Stream Scheduling API
 * Handles scheduling of future live streaming events
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

// Require vendor login
if (!Session::isLoggedIn()) {
    errorResponse('Please login to schedule streams', 401);
}

$userId = Session::getUserId();

// Check if user is a vendor
$vendor = new Vendor();
$vendorInfo = $vendor->findByUserId($userId);

if (!$vendorInfo || $vendorInfo['status'] !== 'approved') {
    errorResponse('Only approved vendors can schedule live streams', 403);
}

$vendorId = $vendorInfo['id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($input['title'])) {
            errorResponse('Event title is required');
        }
        
        if (empty($input['scheduled_start'])) {
            errorResponse('Scheduled start time is required');
        }
        
        // Validate scheduled time is in the future
        $scheduledStart = strtotime($input['scheduled_start']);
        if ($scheduledStart <= time()) {
            errorResponse('Scheduled time must be in the future');
        }
        
        // Prepare data
        $title = trim($input['title']);
        $description = isset($input['description']) ? trim($input['description']) : null;
        $scheduledStartDate = date('Y-m-d H:i:s', $scheduledStart);
        $estimatedDuration = isset($input['estimated_duration']) ? (int)$input['estimated_duration'] : 60;
        $featuredProducts = isset($input['featured_products']) && is_array($input['featured_products']) 
            ? json_encode($input['featured_products']) 
            : null;
        
        // Insert scheduled stream
        $db = db();
        $stmt = $db->prepare("
            INSERT INTO scheduled_streams 
            (vendor_id, title, description, scheduled_start, estimated_duration, featured_products, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'scheduled')
        ");
        
        $result = $stmt->execute([
            $vendorId,
            $title,
            $description,
            $scheduledStartDate,
            $estimatedDuration,
            $featuredProducts
        ]);
        
        if ($result) {
            $streamId = $db->lastInsertId();
            
            successResponse([
                'stream_id' => $streamId,
                'scheduled_start' => $scheduledStartDate
            ], 'Stream scheduled successfully');
        } else {
            errorResponse('Failed to schedule stream');
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get vendor's scheduled streams
        $db = db();
        $stmt = $db->prepare("
            SELECT * FROM scheduled_streams 
            WHERE vendor_id = ? 
            ORDER BY scheduled_start DESC 
            LIMIT 50
        ");
        $stmt->execute([$vendorId]);
        $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        successResponse(['streams' => $streams]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Cancel a scheduled stream
        $input = json_decode(file_get_contents('php://input'), true);
        $streamId = isset($input['stream_id']) ? (int)$input['stream_id'] : 0;
        
        if ($streamId <= 0) {
            errorResponse('Invalid stream ID');
        }
        
        $db = db();
        $stmt = $db->prepare("
            UPDATE scheduled_streams 
            SET status = 'cancelled' 
            WHERE id = ? AND vendor_id = ? AND status = 'scheduled'
        ");
        
        $result = $stmt->execute([$streamId, $vendorId]);
        
        if ($result && $stmt->rowCount() > 0) {
            successResponse([], 'Stream cancelled successfully');
        } else {
            errorResponse('Failed to cancel stream or stream not found');
        }
        
    } else {
        errorResponse('Method not allowed', 405);
    }
} catch (Exception $e) {
    Logger::error('Stream scheduling error: ' . $e->getMessage());
    errorResponse('An error occurred', 500);
}
