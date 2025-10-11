<?php
/**
 * Stream Status API
 * Check if specific streams are currently live
 * Used by frontend pages to display live indicators
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

try {
    // Get stream_id parameter (can be single or comma-separated list)
    $streamIdParam = $_GET['stream_id'] ?? null;
    
    if (!$streamIdParam) {
        // If no stream_id provided, return all live streams
        $liveStream = new LiveStream();
        $liveStreams = $liveStream->getActiveStreams(100);
        
        echo json_encode([
            'success' => true,
            'live_streams' => array_map(function($stream) {
                return [
                    'id' => $stream['id'],
                    'vendor_id' => $stream['vendor_id'],
                    'vendor_name' => $stream['vendor_name'] ?? 'Unknown',
                    'title' => $stream['title'],
                    'viewer_count' => $stream['current_viewers'] ?? 0,
                    'started_at' => $stream['started_at']
                ];
            }, $liveStreams),
            'count' => count($liveStreams)
        ]);
        exit;
    }
    
    // Parse stream IDs (support comma-separated list)
    $streamIds = array_map('intval', explode(',', $streamIdParam));
    $streamIds = array_filter($streamIds); // Remove any invalid IDs
    
    if (empty($streamIds)) {
        throw new Exception('Invalid stream_id parameter');
    }
    
    // Query database for stream statuses
    $db = db();
    $placeholders = implode(',', array_fill(0, count($streamIds), '?'));
    
    $stmt = $db->prepare("
        SELECT 
            ls.id,
            ls.vendor_id,
            ls.title,
            ls.status,
            ls.started_at,
            ls.viewer_count,
            v.business_name as vendor_name
        FROM live_streams ls
        LEFT JOIN vendors v ON ls.vendor_id = v.id
        WHERE ls.id IN ($placeholders)
    ");
    $stmt->execute($streamIds);
    $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build response with status for each stream
    $result = [];
    foreach ($streamIds as $streamId) {
        $stream = null;
        foreach ($streams as $s) {
            if ($s['id'] == $streamId) {
                $stream = $s;
                break;
            }
        }
        
        if ($stream) {
            $result[] = [
                'id' => (int)$stream['id'],
                'vendor_id' => (int)$stream['vendor_id'],
                'vendor_name' => $stream['vendor_name'] ?? 'Unknown Vendor',
                'title' => $stream['title'],
                'is_live' => $stream['status'] === 'live',
                'status' => $stream['status'],
                'viewer_count' => (int)($stream['viewer_count'] ?? 0),
                'started_at' => $stream['started_at']
            ];
        } else {
            $result[] = [
                'id' => $streamId,
                'is_live' => false,
                'status' => 'not_found',
                'viewer_count' => 0
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'streams' => $result,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
