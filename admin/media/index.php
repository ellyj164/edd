<?php
/**
 * Media Library Management
 * Admin Module - File and Asset Management
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check admin authentication - simplified
if (!Session::isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Media Library - Admin';
$currentModule = 'media';

// Handle file upload
$uploadMessage = '';
$uploadError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload' && isset($_FILES['media_file'])) {
        $uploadDir = __DIR__ . '/../../uploads/media/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['media_file'];
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'heic', 'heif', 'svg', 'mp4', 'avi', 'mov', 'pdf', 'doc', 'docx'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($fileExt, $allowedTypes) && $file['size'] <= 10 * 1024 * 1024) {
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Save to database
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("INSERT INTO cms_media (filename, original_name, file_path, file_size, mime_type, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))");
                $stmt->execute([
                    $fileName,
                    $file['name'],
                    'uploads/media/' . $fileName,
                    $file['size'],
                    $file['type'],
                    Session::getUserId()
                ]);
                $uploadMessage = 'File uploaded successfully!';
            } else {
                $uploadError = 'Failed to upload file.';
            }
        } else {
            $uploadError = 'Invalid file type or size too large (max 10MB).';
        }
    }
}

// Get media files
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM cms_media ORDER BY created_at DESC LIMIT 50");
$stmt->execute();
$mediaFiles = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; }
        
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .admin-header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .admin-header p { opacity: 0.9; }
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        
        .upload-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .upload-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .message.error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .media-item {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .media-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .media-preview {
            width: 100%;
            height: 150px;
            border-radius: 8px;
            object-fit: cover;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .media-info h4 {
            font-size: 0.9rem;
            color: #374151;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .media-meta {
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        .file-icon {
            font-size: 3rem;
            color: #9ca3af;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            opacity: 0.9;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .back-link:hover { opacity: 1; }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>Media Library Management</h1>
        <p>Upload, organize, and manage media assets for your e-commerce platform</p>
        <a href="/admin/" class="back-link">← Back to Admin Dashboard</a>
    </div>
    
    <div class="container">
        <?php if ($uploadMessage): ?>
            <div class="message success"><?php echo htmlspecialchars($uploadMessage); ?></div>
        <?php endif; ?>
        
        <?php if ($uploadError): ?>
            <div class="message error"><?php echo htmlspecialchars($uploadError); ?></div>
        <?php endif; ?>
        
        <div class="upload-section">
            <h2 style="margin-bottom: 1.5rem; color: #374151;">Upload New Media</h2>
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="action" value="upload">
                <div class="form-group">
                    <label for="media_file">Choose File</label>
                    <input type="file" id="media_file" name="media_file" required 
                           accept=".jpg,.jpeg,.png,.gif,.bmp,.webp,.heic,.heif,.svg,.mp4,.avi,.mov,.pdf,.doc,.docx">
                    <small style="color: #6b7280; margin-top: 0.5rem; display: block;">
                        Supported: Images, Videos, Documents (Max: 10MB)
                    </small>
                </div>
                <button type="submit" class="btn btn-primary">Upload File</button>
            </form>
        </div>
        
        <div class="media-section">
            <h2 style="color: #374151; margin-bottom: 1rem;">Media Library (<?php echo count($mediaFiles); ?> files)</h2>
            
            <?php if (empty($mediaFiles)): ?>
                <div style="text-align: center; padding: 3rem; color: #6b7280;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📁</div>
                    <h3>No Media Files</h3>
                    <p>Upload your first media file to get started.</p>
                </div>
            <?php else: ?>
                <div class="media-grid">
                    <?php foreach ($mediaFiles as $file): ?>
                        <div class="media-item">
                            <div class="media-preview">
                                <?php 
                                $isImage = in_array(strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
                                if ($isImage): 
                                ?>
                                    <img src="/<?php echo htmlspecialchars($file['file_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($file['original_name']); ?>"
                                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                <?php else: ?>
                                    <div class="file-icon">📄</div>
                                <?php endif; ?>
                            </div>
                            <div class="media-info">
                                <h4 title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                    <?php echo htmlspecialchars($file['original_name']); ?>
                                </h4>
                                <div class="media-meta">
                                    <div>Size: <?php echo number_format($file['file_size'] / 1024, 1); ?> KB</div>
                                    <div>Type: <?php echo htmlspecialchars($file['mime_type']); ?></div>
                                    <div>Uploaded: <?php echo date('M j, Y', strtotime($file['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>