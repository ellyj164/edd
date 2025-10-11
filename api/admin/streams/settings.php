<?php
/**
 * Admin API - RTMP/Stream Settings
 * Manage RTMP server configuration and stream settings
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
    $action = $_GET['action'] ?? ($_POST['action'] ?? 'get');
    
    switch ($action) {
        case 'get':
            // Get current RTMP settings
            $stmt = $pdo->query("
                SELECT setting_key, setting_value 
                FROM system_settings 
                WHERE setting_key LIKE 'rtmp_%' OR setting_key LIKE 'stream_%'
            ");
            $settings = $stmt->fetchAll();
            
            $settingsArray = [];
            foreach ($settings as $setting) {
                $settingsArray[$setting['setting_key']] = $setting['setting_value'];
            }
            
            // Default values if not set
            $defaults = [
                'rtmp_server_url' => 'rtmp://localhost/live',
                'rtmp_server_key' => '',
                'stream_max_bitrate' => '4000',
                'stream_max_resolution' => '1920x1080',
                'stream_allowed_codecs' => 'h264,vp8,vp9',
                'stream_enable_recording' => '1',
                'stream_max_duration' => '14400' // 4 hours
            ];
            
            foreach ($defaults as $key => $value) {
                if (!isset($settingsArray[$key])) {
                    $settingsArray[$key] = $value;
                }
            }
            
            echo json_encode([
                'success' => true,
                'settings' => $settingsArray
            ]);
            break;
            
        case 'update':
            // Update RTMP settings
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['settings']) || !is_array($data['settings'])) {
                throw new Exception('Settings array is required');
            }
            
            $pdo->beginTransaction();
            
            try {
                foreach ($data['settings'] as $key => $value) {
                    // Only allow rtmp_ and stream_ prefixed settings
                    if (!preg_match('/^(rtmp_|stream_)/', $key)) {
                        continue;
                    }
                    
                    // Check if setting exists
                    $checkStmt = $pdo->prepare("
                        SELECT id FROM system_settings WHERE setting_key = ?
                    ");
                    $checkStmt->execute([$key]);
                    
                    if ($checkStmt->fetch()) {
                        // Update existing
                        $updateStmt = $pdo->prepare("
                            UPDATE system_settings 
                            SET setting_value = ?, updated_at = CURRENT_TIMESTAMP 
                            WHERE setting_key = ?
                        ");
                        $updateStmt->execute([$value, $key]);
                    } else {
                        // Insert new
                        $insertStmt = $pdo->prepare("
                            INSERT INTO system_settings (setting_key, setting_value) 
                            VALUES (?, ?)
                        ");
                        $insertStmt->execute([$key, $value]);
                    }
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Settings updated successfully'
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
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
