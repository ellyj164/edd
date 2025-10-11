<?php
/**
 * Banner Get API
 * Retrieves banner data for editing
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../admin/auth.php';

header('Content-Type: application/json');

try {
    // Get banner ID or slot_key
    $bannerId = $_GET['id'] ?? null;
    $slotKey = $_GET['slot_key'] ?? null;
    
    if (!$bannerId && !$slotKey) {
        throw new Exception('Banner ID or slot_key is required');
    }
    
    // Get database connection
    $pdo = db();
    
    // Fetch banner data - try both tables
    if ($slotKey) {
        // Look in banners table by slot_key
        $sql = "SELECT slot_key, title, subtitle, link_url, image_url, bg_image_path, fg_image_path, width, height
                FROM banners 
                WHERE slot_key = :slot_key";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':slot_key', $slotKey);
        $stmt->execute();
        
        $banner = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Look in homepage_banners table by ID
        $sql = "SELECT id, title, subtitle, description, image_url, link_url, button_text,
                       background_color, text_color, position, sort_order, status
                FROM homepage_banners 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $bannerId, PDO::PARAM_INT);
        $stmt->execute();
        
        $banner = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'banner' => $banner ?: null
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}