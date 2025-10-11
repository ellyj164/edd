<?php
/**
 * Admin Banner Save Handler
 * Dedicated endpoint for saving banner edits with file upload support
 * Handles multipart/form-data as specified in requirements
 */

require_once __DIR__ . '/../includes/init.php';

// Ensure $pdo is available; ensure user is admin; return 403 if not
if (!function_exists('db')) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection not available']);
    exit;
}

$pdo = db();

// Check if user is admin
if (!Session::isLoggedIn() || Session::getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin access required']);
    exit;
}

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Accept multipart/form-data (FormData), not JSON, so uploads work
    $slot_key = $_POST['slot_key'] ?? '';
    $title = $_POST['title'] ?? '';
    $subtitle = $_POST['subtitle'] ?? '';
    $link_url = $_POST['link_url'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $width = !empty($_POST['width']) ? (int)$_POST['width'] : null;
    $height = !empty($_POST['height']) ? (int)$_POST['height'] : null;
    
    // Validate required fields
    if (empty($slot_key)) {
        throw new Exception('slot_key is required');
    }
    
    // Handle uploads safely
    $bg_image_path = null;
    $fg_image_path = null;
    
    // Create /uploads/banners/ if missing
    $dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/banners';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Function to safely handle file upload
    function handleFileUpload($file, $dir) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        // Validate files: allow only jpg/jpeg/png/webp; limit size (e.g., 3–5MB)
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if ($file['size'] > $max_size) {
            throw new Exception('File too large. Maximum size is 5MB.');
        }
        
        // Use finfo_file to confirm MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, and WebP are allowed.');
        }
        
        // Move uploads with unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = uniqid('bnr_') . '.' . $extension;
        $full_path = $dir . '/' . $name;
        
        if (!move_uploaded_file($file['tmp_name'], $full_path)) {
            throw new Exception('Failed to upload file');
        }
        
        return "/uploads/banners/$name";
    }
    
    // Handle background image upload
    if (isset($_FILES['bg_image'])) {
        $bg_image_path = handleFileUpload($_FILES['bg_image'], $dir);
    }
    
    // Handle foreground/overlay image upload  
    if (isset($_FILES['fg_image'])) {
        $fg_image_path = handleFileUpload($_FILES['fg_image'], $dir);
    }
    
    // Database schema & upsert
    // Perform an upsert using prepared statements
    $sql = "INSERT INTO banners (slot_key, title, subtitle, link_url, image_url, bg_image_path, fg_image_path, width, height)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              title = VALUES(title), 
              subtitle = VALUES(subtitle),
              link_url = VALUES(link_url), 
              image_url = VALUES(image_url),
              bg_image_path = COALESCE(VALUES(bg_image_path), bg_image_path),
              fg_image_path = COALESCE(VALUES(fg_image_path), fg_image_path),
              width = VALUES(width), 
              height = VALUES(height)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $slot_key,
        $title,
        $subtitle, 
        $link_url,
        $image_url,
        $bg_image_path,
        $fg_image_path,
        $width,
        $height
    ]);
    
    // Return JSON success response
    echo json_encode(['ok' => true]);
    
} catch (Exception $e) {
    // Log errors
    error_log("Banner save error: " . $e->getMessage());
    
    // Return meaningful JSON errors on validation failures  
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>