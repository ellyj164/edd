<?php
/**
 * Banner Save API
 * Handles banner creation and updates with file uploads
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../admin/auth.php';

header('Content-Type: application/json');

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }
    
    // Get form data
    $bannerId = $_POST['banner_id'] ?? null;
    $bannerType = $_POST['banner_type'] ?? 'hero';
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $linkUrl = trim($_POST['link_url'] ?? '');
    $buttonText = trim($_POST['button_text'] ?? '');
    
    // Validation
    if (empty($title)) {
        throw new Exception('Title is required');
    }
    
    if (strlen($title) > 255) {
        throw new Exception('Title is too long (max 255 characters)');
    }
    
    if (!empty($subtitle) && strlen($subtitle) > 500) {
        throw new Exception('Subtitle is too long (max 500 characters)');
    }
    
    if (!empty($buttonText) && strlen($buttonText) > 100) {
        throw new Exception('Button text is too long (max 100 characters)');
    }
    
    if (!empty($linkUrl) && !filter_var($linkUrl, FILTER_VALIDATE_URL)) {
        throw new Exception('Invalid URL format');
    }
    
    // Handle file upload
    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageUrl = handleImageUpload($_FILES['image']);
    }
    
    // Get database connection
    $pdo = db();
    $userId = Session::getUserId();
    
    if (empty($bannerId) || $bannerId === 'new') {
        // Create new banner
        $sql = "INSERT INTO homepage_banners 
                (title, subtitle, description, image_url, link_url, button_text, 
                 position, status, created_by) 
                VALUES (:title, :subtitle, :description, :image_url, :link_url, 
                        :button_text, :position, 'active', :created_by)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':subtitle', $subtitle);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':image_url', $imageUrl);
        $stmt->bindValue(':link_url', $linkUrl);
        $stmt->bindValue(':button_text', $buttonText);
        $stmt->bindValue(':position', $bannerType);
        $stmt->bindValue(':created_by', $userId, PDO::PARAM_INT);
        
    } else {
        // Update existing banner
        if ($imageUrl) {
            // Update with new image
            $sql = "UPDATE homepage_banners 
                    SET title = :title, subtitle = :subtitle, description = :description, 
                        image_url = :image_url, link_url = :link_url, button_text = :button_text,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':image_url', $imageUrl);
        } else {
            // Update without changing image
            $sql = "UPDATE homepage_banners 
                    SET title = :title, subtitle = :subtitle, description = :description, 
                        link_url = :link_url, button_text = :button_text,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
        }
        
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':subtitle', $subtitle);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':link_url', $linkUrl);
        $stmt->bindValue(':button_text', $buttonText);
        $stmt->bindValue(':id', $bannerId, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Banner saved successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Handle image upload with validation
 */
function handleImageUpload($file) {
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/banners/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $fileType = $file['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and WebP are allowed.');
    }
    
    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception('File size too large. Maximum 5MB allowed.');
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'banner_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Return relative URL
    return '/uploads/banners/' . $filename;
}