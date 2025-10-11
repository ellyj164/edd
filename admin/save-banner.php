<?php
/**
 * Admin Banner Save Handler
 * Handles AJAX requests to update banner content from homepage inline editing
 * Enhanced with file upload support and better validation
 */

require_once __DIR__ . '/../includes/init.php';

// Ensure this is an AJAX request
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Forbidden');
}

// Ensure admin is logged in
if (!Session::isLoggedIn() || Session::getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $image_url = '';
    
    // Handle file upload if present
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/banners/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file = $_FILES['banner_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'banner_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $image_url = '/uploads/banners/' . $filename;
        } else {
            throw new Exception('Failed to upload file');
        }
    } else {
        // Get JSON input for regular updates
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception('Invalid JSON data');
        }
        
        $image_url = sanitizeInput($data['image_url'] ?? '');
    }
    
    // Get form data (either from JSON or form data)
    if (isset($data)) {
        // JSON request
        $banner_id = sanitizeInput($data['banner_id']);
        $banner_type = sanitizeInput($data['banner_type']);
        $title = sanitizeInput($data['title'] ?? '');
        $description = sanitizeInput($data['description'] ?? '');
        $link_url = sanitizeInput($data['link_url'] ?? '');
        $button_text = sanitizeInput($data['button_text'] ?? '');
    } else {
        // Form request (file upload)
        $banner_id = sanitizeInput($_POST['banner_id']);
        $banner_type = sanitizeInput($_POST['banner_type']);
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $link_url = sanitizeInput($_POST['link_url'] ?? '');
        $button_text = sanitizeInput($_POST['button_text'] ?? '');
    }
    
    // Validate required fields
    if (empty($banner_id) || empty($banner_type)) {
        throw new Exception('Missing required fields: banner_id or banner_type');
    }
    
    // Get database connection
    $pdo = db();
    
    // Check if banner exists, if not create it
    $check_sql = "SELECT id FROM homepage_banners WHERE id = ? OR (title LIKE ? AND position = ?)";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$banner_id, "%$banner_id%", $banner_type === 'hero' ? 'hero' : 'top']);
    $existing_banner = $check_stmt->fetch();
    
    if ($existing_banner) {
        // Update existing banner
        $update_sql = "UPDATE homepage_banners 
                       SET title = ?, subtitle = ?, description = ?, 
                           image_url = CASE WHEN ? != '' THEN ? ELSE image_url END,
                           link_url = ?, button_text = ?, updated_at = NOW()
                       WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([
            $title,
            $description, // Use description as subtitle for now
            $description,
            $image_url,
            $image_url,
            $link_url,
            $button_text,
            $existing_banner['id']
        ]);
    } else {
        // Create new banner
        $insert_sql = "INSERT INTO homepage_banners 
                       (title, subtitle, description, image_url, link_url, button_text, 
                        position, status, created_by, created_at, updated_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW(), NOW())";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([
            $title,
            $description,
            $description,
            $image_url,
            $link_url,
            $button_text,
            $banner_type === 'hero' ? 'hero' : 'top',
            Session::getUserId()
        ]);
    }
    
    // Log the admin action
    if (function_exists('logAdminAction')) {
        logAdminAction(Session::getUserId(), 'banner_update', 'Updated banner: ' . $banner_id);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Banner updated successfully',
        'banner_id' => $banner_id,
        'image_url' => $image_url
    ]);

} catch (Exception $e) {
    error_log("Banner save error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating banner: ' . $e->getMessage()
    ]);
}