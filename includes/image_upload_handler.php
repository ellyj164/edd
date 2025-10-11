<?php
/**
 * Handles product image uploads, including thumbnail and gallery images.
 * This script processes files, moves them to a designated directory,
 * and records their paths in the database.
 */

if (!function_exists('handleProductImageUploads')) {
    /**
     * Processes and saves product images.
     *
     * @param int $productId The ID of the product to associate images with.
     * @param int $sellerId The ID of the seller uploading the images.
     * @return array An array containing success status, a list of errors, and upload details.
     */
    function handleProductImageUploads(int $productId, int $sellerId): array
    {
        $errors = [];
        $uploads = [];
        $baseUploadDir = __DIR__ . '/../uploads/products/' . date('Y/m');
        
        if (!is_dir($baseUploadDir) && !mkdir($baseUploadDir, 0775, true)) {
            $errors['directory'] = 'Failed to create image upload directory.';
            error_log("Image Upload Error: Failed to create directory: " . $baseUploadDir);
            return ['success' => false, 'errors' => $errors, 'uploads' => $uploads];
        }

        $processFile = function(array $file, bool $isThumbnail = false) use ($productId, $sellerId, $baseUploadDir, &$errors, &$uploads) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Error uploading file {$file['name']}.";
                }
                return;
            }

            // Validate file extension and MIME type
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'heif', 'heic', 'svg'];
            $allowedMimeTypes = [
                'image/jpeg',
                'image/jpg', 
                'image/png',
                'image/gif',
                'image/bmp',
                'image/webp',
                'image/heif',
                'image/heic',
                'image/svg+xml'
            ];

            if (!in_array($extension, $allowedExtensions)) {
                $errors[] = "Invalid file type for {$file['name']}. Only JPEG, PNG, GIF, BMP, WEBP, HEIF/HEIC, and SVG files are allowed.";
                return;
            }

            // Verify MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimeTypes)) {
                $errors[] = "Invalid file format for {$file['name']}. File appears to be: {$mimeType}";
                return;
            }

            // Check file size (max 10MB)
            $maxFileSize = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $maxFileSize) {
                $errors[] = "File {$file['name']} is too large. Maximum size is 10MB.";
                return;
            }

            $safeFilename = 'prod_' . $productId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $destinationPath = $baseUploadDir . '/' . $safeFilename;
            $publicPath = '/uploads/products/' . date('Y/m') . '/' . $safeFilename;

            if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
                $errors[] = "Failed to move uploaded file: {$file['name']}.";
                return;
            }
            
            try {
                Database::query(
                    "INSERT INTO product_images (product_id, image_path, is_thumbnail, uploaded_by) VALUES (?, ?, ?, ?)",
                    [$productId, $publicPath, $isThumbnail ? 1 : 0, $sellerId]
                );
                
                if ($isThumbnail) {
                    $uploads['thumbnail'] = $publicPath;
                } else {
                    $uploads['gallery'][] = $publicPath;
                }
            } catch (Throwable $e) {
                // =================================================================
                // TEMPORARY: Display the exact database error for debugging.
                // =================================================================
                $errors[] = "DATABASE DEBUG: " . $e->getMessage();
                // =================================================================
                
                error_log("Image DB Error for product ID {$productId}: " . $e->getMessage());
                if (file_exists($destinationPath)) {
                    unlink($destinationPath);
                }
            }
        };

        if (isset($_FILES['product_thumbnail']) && $_FILES['product_thumbnail']['error'] === UPLOAD_ERR_OK) {
            $processFile($_FILES['product_thumbnail'], true);
        }

        if (isset($_FILES['product_gallery']) && is_array($_FILES['product_gallery']['name'])) {
            $galleryFiles = [];
            foreach ($_FILES['product_gallery']['name'] as $key => $name) {
                if ($_FILES['product_gallery']['error'][$key] === UPLOAD_ERR_OK) {
                    $galleryFiles[] = [
                        'name' => $name,
                        'type' => $_FILES['product_gallery']['type'][$key],
                        'tmp_name' => $_FILES['product_gallery']['tmp_name'][$key],
                        'error' => $_FILES['product_gallery']['error'][$key],
                        'size' => $_FILES['product_gallery']['size'][$key],
                    ];
                }
            }

            foreach ($galleryFiles as $file) {
                $processFile($file, false);
            }
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'uploads' => $uploads
        ];
    }
}

if (!function_exists('handle_image_uploads')) {
    /**
     * Simple wrapper function for handling multiple image uploads
     * Expected by seller product add/edit pages
     * Also known as handleProductImageUploads for compatibility
     * 
     * @param array $files The $_FILES array for multiple files
     * @return array Array of uploaded file information
     */
    function handle_image_uploads(array $files): array {
        $uploads = [];
        $errors = [];
        
        if (empty($files) || !isset($files['name'])) {
            return $uploads;
        }
        
        $baseUploadDir = __DIR__ . '/../uploads/products/' . date('Y/m');
        
        if (!is_dir($baseUploadDir) && !mkdir($baseUploadDir, 0775, true)) {
            error_log("Failed to create upload directory: " . $baseUploadDir);
            return $uploads;
        }
        
        // Handle single file or array of files
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;
        
        for ($i = 0; $i < $fileCount; $i++) {
            $file = [];
            if (is_array($files['name'])) {
                $file = [
                    'name' => $files['name'][$i] ?? '',
                    'type' => $files['type'][$i] ?? '',
                    'tmp_name' => $files['tmp_name'][$i] ?? '',
                    'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $files['size'][$i] ?? 0
                ];
            } else {
                $file = $files;
            }
            
            if ($file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
                continue;
            }
            
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                continue;
            }
            
            $safeFilename = 'img_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $destinationPath = $baseUploadDir . '/' . $safeFilename;
            $publicPath = '/uploads/products/' . date('Y/m') . '/' . $safeFilename;
            
            if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
                $uploads[] = [
                    'path' => $publicPath,
                    'original_name' => $file['name'],
                    'is_primary' => count($uploads) === 0 ? 1 : 0
                ];
            }
        }
        
        return $uploads;
    }
}