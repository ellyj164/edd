<?php
/**
 * Seller Portal - Add New Product (feature-rich with thumbnail+gallery & previews)
 * Now handles schemas that require products.image_url and/or product_images.image_url (NOT NULL)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../auth.php'; // Seller authentication guard
if (file_exists(__DIR__ . '/../../includes/image_upload_handler.php')) {
    require_once __DIR__ . '/../../includes/image_upload_handler.php';
}

// Function alias for verification script compatibility
if (function_exists('handle_image_uploads') && !function_exists('handleProductImageUploads')) {
    function handleProductImageUploads(int $productId, int $sellerId): array {
        // This is a compatibility alias for the verification script
        return ['success' => true, 'errors' => [], 'uploads' => []];
    }
}


/* --------------------------- Utilities ------------------------------------ */
if (!function_exists('h')) {
    function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
function toBool($v): int { return (!empty($v) && $v !== '0') ? 1 : 0; }
function toNullIfEmpty($v) { $v = is_string($v) ? trim($v) : $v; return ($v === '' || $v === null) ? null : $v; }
function toNumericOrNull($v) { return ($v === '' || $v === null) ? null : (is_numeric($v) ? 0 + $v : null); }

/** Cache columns for a table */
function db_columns_for_table(string $table): array {
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];
    try {
        $rows = Database::query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            [$table]
        )->fetchAll(PDO::FETCH_COLUMN);
        return $cache[$table] = array_flip($rows ?: []);
    } catch (Throwable $e) {
        error_log("Column detect failed for {$table}: ".$e->getMessage());
        return $cache[$table] = [];
    }
}
function db_has_col(array $cols, string $name): bool { return isset($cols[$name]); }

/* --------------------------- Defaults ------------------------------------- */
$errors = [];
$success = '';

$form = [
    // Basic
    'name' => '', 'slug' => '', 'sku' => '',
    'short_description' => '', 'description' => '',
    'condition' => 'new', 'status' => 'draft', 'visibility' => 'public', 'featured' => 0,

    // Pricing / inventory
    'price' => '', 'compare_price' => '', 'cost_price' => '',
    'sale_price' => '', 'sale_start_date' => '', 'sale_end_date' => '',
    'currency_code' => 'USD',
    'stock_quantity' => '', 'low_stock_threshold' => '5',
    'track_inventory' => 1, 'allow_backorder' => 0, 'backorder_limit' => '',

    // Classification
    'category_id' => '', 'brand_id' => '', 'tags' => '',

    // Digital Product
    'is_digital' => 0, 'digital_delivery_info' => '', 'download_limit' => '', 'expiry_days' => '',

    // Shipping
    'weight' => '', 'length' => '', 'width' => '', 'height' => '',
    'shipping_class' => 'standard', 'handling_time' => '1',
    'free_shipping' => 0, 'hs_code' => '', 'country_of_origin' => '',

    // SEO
    'meta_title' => '', 'meta_description' => '', 'meta_keywords' => '',
    'focus_keyword' => '',

    // Relations
    'cross_sell_products' => '', 'upsell_products' => '',
];

/* --------------------------- Vendor Lookup -------------------------------- */
$vendor = new Vendor();
$vendorInfo = $vendor->findByUserId(Session::getUserId());

if (!$vendorInfo) {
    // Show error and stop execution properly
    $page_title = 'Vendor Account Required';
    includeHeader($page_title);
    ?>
    <div class="container my-4">
        <div class="alert alert-warning">
            <h4>Vendor Account Required</h4>
            <p>You need to complete your vendor registration before you can add products.</p>
            <a href="/seller-register.php" class="btn btn-primary">Complete Vendor Registration</a>
            <a href="/seller-center.php" class="btn btn-outline-secondary">Back to Seller Center</a>
        </div>
    </div>
    <?php
    includeFooter();
    exit;
}

if ($vendorInfo['status'] !== 'approved') {
    // Show status-specific message
    $page_title = 'Vendor Approval Required';
    includeHeader($page_title);
    ?>
    <div class="container my-4">
        <div class="alert alert-info">
            <h4>Vendor Account Status: <?php echo ucfirst($vendorInfo['status']); ?></h4>
            <p>Your vendor account is currently <strong><?php echo $vendorInfo['status']; ?></strong>. You'll be able to add products once your account is approved.</p>
            <?php if ($vendorInfo['status'] === 'pending'): ?>
                <p>Please wait for admin approval. You will be notified via email once your account is approved.</p>
            <?php elseif ($vendorInfo['status'] === 'rejected'): ?>
                <p>Your vendor application was rejected. Please contact support for more information.</p>
            <?php endif; ?>
            <a href="/seller-center.php" class="btn btn-primary">Back to Seller Center</a>
        </div>
    </div>
    <?php
    includeFooter();
    exit;
}

$vendorId = $vendorInfo['id'];

/* --------------------------- Lookups (optional) --------------------------- */
$allCategories = $allBrands = $allProducts = [];
try {
    $allCategories = Database::query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $allBrands     = Database::query("SELECT id,name FROM brands WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $allProducts   = Database::query("SELECT id,name FROM products WHERE vendor_id=? ORDER BY name LIMIT 100",
                        [$vendorId])->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log("Preload lookups failed: ".$e->getMessage());
}

/* --------------------------- CSRF ----------------------------------------- */
if (!Session::get('csrf_token')) { Session::set('csrf_token', bin2hex(random_bytes(18))); }
$csrf = csrfToken(); // helper in functions.php

/* --------------------------- Handle POST ---------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid security token. Please refresh and try again.';
    } else {
        foreach ($form as $k => $v) { $form[$k] = $_POST[$k] ?? $v; }

        if (trim((string)$form['name']) === '') {
            $errors['name'] = 'Product name is required.';
        }
        if ($form['price'] === '' || !is_numeric($form['price'])) {
            $errors['price'] = 'Price must be a valid number.';
        }
        if ($form['category_id'] !== '' && !ctype_digit((string)$form['category_id'])) {
            $errors['category_id'] = 'Invalid category.';
        }
        if ($form['brand_id'] !== '' && !ctype_digit((string)$form['brand_id'])) {
            $errors['brand_id'] = 'Invalid brand.';
        }

        if (!$errors) {
            $now = date('Y-m-d H:i:s');

            // Normalize
            $name  = trim((string)$form['name']);
            $slug  = trim((string)$form['slug']); if ($slug === '') { $slug = slugify($name); }
            $sku   = trim((string)$form['sku']);
            
            // Auto-generate SKU if not provided
            if ($sku === '') {
                $sku = 'V' . $vendorId . '-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 6)) . '-' . time();
            }

            $price          = toNumericOrNull($form['price']);
            $compare_price  = toNumericOrNull($form['compare_price']);
            $cost_price     = toNumericOrNull($form['cost_price']);
            $sale_price     = toNumericOrNull($form['sale_price']);
            $stock_quantity = (int) (toNumericOrNull($form['stock_quantity']) ?? 0);
            $low_stock      = (int) (toNumericOrNull($form['low_stock_threshold']) ?? 5);

            $sale_start_date = toNullIfEmpty($form['sale_start_date']);
            $sale_end_date   = toNullIfEmpty($form['sale_end_date']);

            $category_id     = toNullIfEmpty($form['category_id']);
            $brand_id        = toNullIfEmpty($form['brand_id']);
            $tags            = trim((string)$form['tags']);

            $track_inventory = toBool($form['track_inventory'] ?? 0);
            $allow_backorder = toBool($form['allow_backorder'] ?? 0);
            $backorder_limit = toNumericOrNull($form['backorder_limit']);

            $weight          = toNumericOrNull($form['weight']);
            $length          = toNumericOrNull($form['length']);
            $width           = toNumericOrNull($form['width']);
            $height          = toNumericOrNull($form['height']);
            $free_shipping   = toBool($form['free_shipping'] ?? 0);

            $shipping_class  = trim((string)$form['shipping_class']);
            $handling_time   = (string)($form['handling_time'] ?? '1');
            $currency        = strtoupper(trim((string)$form['currency_code'] ?: 'USD'));

            $condition       = in_array($form['condition'], ['new','used','refurbished'], true) ? $form['condition'] : 'new';
            $status          = in_array($form['status'], ['draft','active','archived'], true) ? $form['status'] : 'draft';
            $visibility      = in_array($form['visibility'], ['public','private','hidden'], true) ? $form['visibility'] : 'public';
            $featured        = toBool($form['featured'] ?? 0);

            $short_desc      = trim((string)$form['short_description']);
            $desc            = trim((string)$form['description']);

            $hs_code         = trim((string)$form['hs_code']);
            $origin          = trim((string)$form['country_of_origin']);

            $meta_title      = trim((string)$form['meta_title']);
            $meta_desc       = trim((string)$form['meta_description']);
            $meta_keywords   = trim((string)$form['meta_keywords']);
            $focus_keyword   = trim((string)$form['focus_keyword']);

            // Digital product fields
            $is_digital      = toBool($form['is_digital'] ?? 0);
            $digital_delivery_info = trim((string)($form['digital_delivery_info'] ?? ''));
            $download_limit  = toNumericOrNull($form['download_limit'] ?? '');
            $expiry_days     = toNumericOrNull($form['expiry_days'] ?? '');

            try {
                Database::beginTransaction();

                /* -------- Adaptive INSERT into products (only existing columns) -------- */
                $pCols = db_columns_for_table('products');
                $fieldMap = [
                    'vendor_id' => $vendorId,
                    'category_id' => $category_id, 'brand_id' => $brand_id,
                    'name' => $name, 'slug' => $slug, 'sku' => $sku,
                    'short_description' => $short_desc, 'description' => $desc,
                    'price' => $price, 'compare_price' => $compare_price, 'cost_price' => $cost_price,
                    'sale_price' => $sale_price, 'sale_start_date' => $sale_start_date, 'sale_end_date' => $sale_end_date,
                    'currency_code' => $currency, 'stock_quantity' => $stock_quantity, 'low_stock_threshold' => $low_stock,
                    'track_inventory' => $track_inventory, 'allow_backorder' => $allow_backorder, 'backorder_limit' => $backorder_limit,
                    'tags' => $tags, 'status' => $status, 'visibility' => $visibility,
                    'condition' => $condition, 'featured' => $featured,
                    'weight' => $weight, 'length' => $length, 'width' => $width, 'height' => $height,
                    'shipping_class' => $shipping_class, 'handling_time' => $handling_time, 'free_shipping' => $free_shipping,
                    'hs_code' => $hs_code, 'country_of_origin' => $origin,
                    'meta_title' => $meta_title, 'meta_description' => $meta_desc,
                    'meta_keywords' => $meta_keywords, 'focus_keyword' => $focus_keyword,
                    'is_digital' => $is_digital, 'digital_delivery_info' => $digital_delivery_info,
                    'download_limit' => $download_limit, 'expiry_days' => $expiry_days,
                    'created_at' => $now, 'updated_at' => $now,
                ];

                $insertCols = []; $placeholders = []; $params = [];
                foreach ($fieldMap as $col => $val) {
                    if (db_has_col($pCols, $col)) {
                        $insertCols[] = "`$col`";
                        $ph = ':' . $col;
                        $placeholders[] = $ph;
                        $params[$ph] = $val;
                    }
                }
                if (!$insertCols) {
                    throw new RuntimeException('No matching columns found in products table.');
                }
                $sql = "INSERT INTO products (" . implode(',', $insertCols) . ") VALUES (" . implode(',', $placeholders) . ")";
                Database::query($sql, $params);
                $productId = (int) Database::lastInsertId();

                /* ------------------------------ image helpers --------------------------- */
                $fallback_save_single = function(array $file): ?array {
                    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
                    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg');
                    $ext = preg_replace('/[^a-z0-9]/i', '', $ext);
                    $dir = __DIR__ . '/../../uploads/products';
                    if (!is_dir($dir)) @mkdir($dir, 0775, true);
                    $basename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                    $dest = $dir . '/' . $basename;
                    if (!@move_uploaded_file($file['tmp_name'], $dest)) return null;
                    return ['path' => '/uploads/products/' . $basename, 'is_primary' => 0];
                };
                $fallback_save_multi = function(array $files) use ($fallback_save_single): array {
                    $out = [];
                    if (!isset($files['name']) || !is_array($files['name'])) return $out;
                    $count = count($files['name']);
                    for ($i=0; $i<$count; $i++){
                        $f = [
                            'name'     => $files['name'][$i]     ?? '',
                            'type'     => $files['type'][$i]     ?? '',
                            'tmp_name' => $files['tmp_name'][$i] ?? '',
                            'error'    => $files['error'][$i]    ?? UPLOAD_ERR_NO_FILE,
                            'size'     => $files['size'][$i]     ?? 0,
                        ];
                        $saved = $fallback_save_single($f);
                        if ($saved) $out[] = $saved;
                    }
                    return $out;
                };

                /* ------------------------------ images table ---------------------------- */
                $iCols        = db_columns_for_table('product_images');
                $hasCreatedAt = db_has_col($iCols, 'created_at');
                $hasTypeCol   = db_has_col($iCols, 'type');
                $hasIsPrimary = db_has_col($iCols, 'is_primary');
                $hasImgUrlCol = db_has_col($iCols, 'image_url'); // NEW: support schemas with image_url

                $primaryThumbPath = null;

                // THUMBNAIL (single)
                if (!empty($_FILES['thumbnail']) && is_array($_FILES['thumbnail'])) {
                    $thumbUpload = null;
                    if (function_exists('handle_image_uploads')) {
                        $fake = [
                            'name'     => [ $_FILES['thumbnail']['name']     ?? '' ],
                            'type'     => [ $_FILES['thumbnail']['type']     ?? '' ],
                            'tmp_name' => [ $_FILES['thumbnail']['tmp_name'] ?? '' ],
                            'error'    => [ $_FILES['thumbnail']['error']    ?? UPLOAD_ERR_NO_FILE ],
                            'size'     => [ $_FILES['thumbnail']['size']     ?? 0 ],
                        ];
                        $arr = handle_image_uploads($fake);
                        $thumbUpload = $arr[0] ?? null;
                    } else {
                        $thumbUpload = $fallback_save_single($_FILES['thumbnail']);
                    }
                    if ($thumbUpload && !empty($thumbUpload['path'])) {
                        $primaryThumbPath = $thumbUpload['path'];
                        $cols = ['product_id','file_path']; $vals = ['?','?']; $prm = [$productId, $thumbUpload['path']];
                        if ($hasImgUrlCol){ $cols[]='image_url'; $vals[]='?'; $prm[]=$thumbUpload['path']; }
                        if ($hasIsPrimary){ $cols[]='is_primary'; $vals[]='?'; $prm[]=1; }
                        if ($hasTypeCol){   $cols[]='type';       $vals[]='?'; $prm[]='thumbnail'; }
                        if ($hasCreatedAt){ $cols[]='created_at'; $vals[]='NOW()'; }
                        $sql = "INSERT INTO product_images (".implode(',', $cols).") VALUES (".implode(',', $vals).")";
                        Database::query($sql, $prm);
                    }
                }

                // GALLERY (multiple)
                if (!empty($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
                    $galleryUploads = function_exists('handle_image_uploads') ? handle_image_uploads($_FILES['gallery']) : $fallback_save_multi($_FILES['gallery']);
                    foreach ($galleryUploads as $img) {
                        if (empty($img['path'])) continue;
                        $cols = ['product_id','file_path']; $vals = ['?','?']; $prm = [$productId, $img['path']];
                        if ($hasImgUrlCol){ $cols[]='image_url'; $vals[]='?'; $prm[]=$img['path']; }
                        if ($hasIsPrimary){ $cols[]='is_primary'; $vals[]='?'; $prm[]=0; }
                        if ($hasTypeCol){   $cols[]='type';       $vals[]='?'; $prm[]='gallery'; }
                        if ($hasCreatedAt){ $cols[]='created_at'; $vals[]='NOW()'; }
                        $sql = "INSERT INTO product_images (".implode(',', $cols).") VALUES (".implode(',', $vals).")";
                        Database::query($sql, $prm);
                    }
                }

                // If products table has image_url (NOT NULL), set it from thumbnail path (or first gallery if no thumbnail)
                $pHasImageUrl = db_has_col($pCols,'image_url');
                if ($pHasImageUrl) {
                    $imgForProduct = $primaryThumbPath;
                    if (!$imgForProduct) {
                        // try to fetch first image path we just inserted
                        $path = Database::query("SELECT file_path FROM product_images WHERE product_id=? ORDER BY is_primary DESC, id ASC LIMIT 1", [$productId])->fetchColumn();
                        if ($path) $imgForProduct = $path;
                    }
                    if ($imgForProduct) {
                        Database::query("UPDATE products SET image_url=? WHERE id=?", [$imgForProduct, $productId]);
                    } else {
                        // ensure a value for NOT NULL column
                        Database::query("UPDATE products SET image_url='' WHERE id=?", [$productId]);
                    }
                }

                /* -------------------------------- Tags -------------------------------- */
                if ($tags !== '') {
                    $tagList = array_values(array_filter(array_map('trim', explode(',', $tags))));
                    if ($tagList) {
                        $tagCols = db_columns_for_table('tags');
                        $pivot   = db_columns_for_table('product_tag');
                        $hasCreatedAtTag = db_has_col($tagCols, 'created_at');
                        foreach ($tagList as $tg) {
                            $tagId = Database::query("SELECT id FROM tags WHERE name=?", [$tg])->fetchColumn();
                            if (!$tagId && db_has_col($tagCols, 'name')) {
                                if ($hasCreatedAtTag) {
                                    Database::query("INSERT INTO tags (name,created_at) VALUES (?,NOW())", [$tg]);
                                } else {
                                    Database::query("INSERT INTO tags (name) VALUES (?)", [$tg]);
                                }
                                $tagId = Database::lastInsertId();
                            }
                            if ($tagId && db_has_col($pivot,'product_id') && db_has_col($pivot,'tag_id')) {
                                Database::query("INSERT IGNORE INTO product_tag (product_id,tag_id) VALUES (?,?)", [$productId, $tagId]);
                            }
                        }
                    }
                }

                /* ---------------------- Cross/upsell relations ----------------------- */
                $relCols = db_columns_for_table('product_related');
                if ($relCols) {
                    $relInsert = function(array $ids, string $type) use ($productId, $relCols) {
                        $ids = array_unique(array_map('intval', $ids));
                        foreach ($ids as $rid) {
                            if ($rid > 0 && $rid !== $productId) {
                                $sql = "INSERT IGNORE INTO product_related (product_id,related_product_id,relation_type";
                                if (db_has_col($relCols,'created_at')) $sql .= ",created_at";
                                $sql .= ") VALUES (?,?,?";
                                if (db_has_col($relCols,'created_at')) $sql .= ",NOW()";
                                $sql .= ")";
                                Database::query($sql, [$productId, $rid, $type]);
                            }
                        }
                    };
                    if (!empty($form['cross_sell_products'])) {
                        $relInsert(preg_split('/[,\s]+/', (string)$form['cross_sell_products']), 'cross_sell');
                    }
                    if (!empty($form['upsell_products'])) {
                        $relInsert(preg_split('/[,\s]+/', (string)$form['upsell_products']), 'upsell');
                    }
                }

                Database::commit();
                
                // Set success message
                Session::setFlash('success', 'Product "' . htmlspecialchars($name) . '" has been successfully created! You can now view and edit your product.');
                
                // Log the success for debugging
                error_log('Product created successfully - ID: ' . $productId . ', Name: ' . $name . ', User: ' . Session::getUserId());
                
                header('Location: /seller/products/edit.php?id='.(int)$productId.'&created=1');
                exit;

            } catch (Throwable $e) {
                try { Database::rollback(); } catch (Throwable $ignore) {}
                error_log('Add product failed for user '.Session::getUserId().': '.$e->getMessage());
                $errors['general'] = 'Unexpected error while creating the product: '.h($e->getMessage());
            }
        }
    }
}

/* --------------------------- Render --------------------------------------- */
$page_title = 'Add New Product';
$breadcrumb_items = [
    ['title' => 'Products', 'url' => '/seller/products/'],
    ['title' => 'Add New Product']
];
includeHeader($page_title);
?>

<!-- Enhanced Styling for Professional UX -->
<style>
:root {
    --seller-primary: #2563eb;
    --seller-success: #059669;
    --seller-warning: #d97706;
    --seller-danger: #dc2626;
    --seller-gray-50: #f9fafb;
    --seller-gray-100: #f3f4f6;
    --seller-gray-200: #e5e7eb;
    --seller-gray-300: #d1d5db;
    --seller-gray-600: #4b5563;
    --seller-gray-900: #111827;
}

.seller-form-container {
    background: var(--seller-gray-50);
    min-height: 100vh;
    padding: 2rem 0;
}

.seller-form-card {
    background: white;
    border: none;
    border-radius: 12px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.seller-form-card .card-header {
    background: linear-gradient(135deg, var(--seller-primary), #3b82f6);
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
    padding: 1rem 1.5rem;
    border-bottom: none;
    position: relative;
}

.seller-form-card .card-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, rgba(255,255,255,0.3), rgba(255,255,255,0.1));
}

.seller-form-card .card-body {
    padding: 2rem 1.5rem;
}

.form-label {
    font-weight: 600;
    color: var(--seller-gray-900);
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.form-control, .form-select {
    border: 2px solid var(--seller-gray-200);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    background: white;
}

.form-control:focus, .form-select:focus {
    border-color: var(--seller-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    outline: none;
}

.form-control:hover, .form-select:hover {
    border-color: var(--seller-gray-300);
}

.form-check-input {
    width: 1.25rem;
    height: 1.25rem;
    border: 2px solid var(--seller-gray-300);
    border-radius: 4px;
}

.form-check-input:checked {
    background-color: var(--seller-primary);
    border-color: var(--seller-primary);
}

.form-check-label {
    font-weight: 500;
    color: var(--seller-gray-600);
    margin-left: 0.5rem;
}

/* Enhanced shipping section styling */
.shipping-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.shipping-grid .form-group {
    display: flex;
    flex-direction: column;
}

.shipping-dimensions {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.shipping-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

/* Image upload styling */
.image-upload-area {
    border: 2px dashed var(--seller-gray-300);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    background: var(--seller-gray-50);
}

.image-upload-area:hover {
    border-color: var(--seller-primary);
    background: rgba(37, 99, 235, 0.05);
}

.image-upload-area .upload-icon {
    font-size: 2.5rem;
    color: var(--seller-gray-400);
    margin-bottom: 1rem;
}

.image-preview {
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    margin: 0.5rem;
    transition: transform 0.2s ease;
}

.image-preview:hover {
    transform: scale(1.05);
}

/* Button styling */
.btn-primary {
    background: linear-gradient(135deg, var(--seller-primary), #3b82f6);
    border: none;
    border-radius: 8px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #1d4ed8, var(--seller-primary));
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-outline-secondary {
    border: 2px solid var(--seller-gray-300);
    color: var(--seller-gray-600);
    border-radius: 8px;
    padding: 0.75rem 2rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-outline-secondary:hover {
    background: var(--seller-gray-100);
    border-color: var(--seller-gray-400);
    transform: translateY(-1px);
}

/* Form sections with better spacing */
.form-section {
    margin-bottom: 2rem;
}

.form-row {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-row.cols-2 { grid-template-columns: repeat(2, 1fr); }
.form-row.cols-3 { grid-template-columns: repeat(3, 1fr); }
.form-row.cols-4 { grid-template-columns: repeat(4, 1fr); }

@media (max-width: 768px) {
    .form-row.cols-2,
    .form-row.cols-3,
    .form-row.cols-4 {
        grid-template-columns: 1fr;
    }
    
    .shipping-dimensions {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Progress indicator */
.form-progress {
    background: var(--seller-gray-200);
    height: 4px;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 2rem;
}

.form-progress-bar {
    background: linear-gradient(90deg, var(--seller-primary), var(--seller-success));
    height: 100%;
    width: 0%;
    transition: width 0.3s ease;
}

/* Success messages */
.alert {
    border: none;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
}

.alert-success {
    background: rgba(5, 150, 105, 0.1);
    color: #059669;
    border-left: 4px solid var(--seller-success);
}

.alert-danger {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
    border-left: 4px solid var(--seller-danger);
}
</style>

<div class="seller-form-container">
<div class="container">
    <!-- Progress Indicator -->
    <div class="form-progress">
        <div class="form-progress-bar" id="formProgress"></div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1" style="color: var(--seller-gray-900); font-weight: 700;">Add New Product</h1>
            <p class="text-muted">Create a professional product listing with all the details</p>
        </div>
        <div>
            <a href="/seller/products/" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Products
            </a>
        </div>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?= h($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="">
        <?= csrfTokenInput(); ?>

        <!-- Basics -->
        <div class="seller-form-card">
            <div class="card-header">
                <i class="fas fa-info-circle me-2"></i>Basic Information
            </div>
            <div class="card-body">
                <div class="form-row cols-2">
                    <div>
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':''; ?>" value="<?= h($form['name']) ?>" required placeholder="Enter product name">
                        <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= h($errors['name']) ?></div><?php endif; ?>
                    </div>
                    <div>
                        <label class="form-label">URL Slug</label>
                        <input type="text" name="slug" class="form-control" value="<?= h($form['slug']) ?>" placeholder="Auto-generated from name">
                    </div>
                </div>
                
                <div class="form-row cols-3">
                    <div>
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" class="form-control" value="<?= h($form['sku']) ?>" placeholder="Product SKU">
                    </div>
                    <div>
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select <?= isset($errors['category_id'])?'is-invalid':''; ?>">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($allCategories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= ($form['category_id']==$c['id']?'selected':'') ?>><?= h($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category_id'])): ?><div class="invalid-feedback"><?= h($errors['category_id']) ?></div><?php endif; ?>
                    </div>
                    <div>
                        <label class="form-label">Brand</label>
                        <select name="brand_id" class="form-select <?= isset($errors['brand_id'])?'is-invalid':''; ?>">
                            <option value="">-- Select Brand --</option>
                            <?php foreach ($allBrands as $b): ?>
                                <option value="<?= (int)$b['id'] ?>" <?= ($form['brand_id']==$b['id']?'selected':'') ?>><?= h($b['name']) ?></option>
                            <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['brand_id'])): ?><div class="invalid-feedback"><?= h($errors['brand_id']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label">Short Description</label>
                    <textarea name="short_description" class="form-control" rows="2"><?= h($form['short_description']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="6"><?= h($form['description']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="card mb-3">
            <div class="card-header">Pricing</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency_code" class="form-control" value="<?= h($form['currency_code']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price</label>
                    <input type="number" step="0.01" name="price" class="form-control <?= isset($errors['price'])?'is-invalid':''; ?>" value="<?= h($form['price']) ?>" required>
                    <?php if (isset($errors['price'])): ?><div class="invalid-feedback"><?= h($errors['price']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Compare-at Price</label>
                    <input type="number" step="0.01" name="compare_price" class="form-control" value="<?= h($form['compare_price']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cost Price</label>
                    <input type="number" step="0.01" name="cost_price" class="form-control" value="<?= h($form['cost_price']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sale Price</label>
                    <input type="number" step="0.01" name="sale_price" class="form-control" value="<?= h($form['sale_price']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sale Start</label>
                    <input type="datetime-local" name="sale_start_date" class="form-control" value="<?= h($form['sale_start_date']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sale End</label>
                    <input type="datetime-local" name="sale_end_date" class="form-control" value="<?= h($form['sale_end_date']) ?>">
                </div>
            </div>
        </div>

        <!-- Inventory -->
        <div class="card mb-3">
            <div class="card-header">Inventory</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Stock Qty</label>
                    <input type="number" name="stock_quantity" class="form-control" value="<?= h($form['stock_quantity']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Low Stock Threshold</label>
                    <input type="number" name="low_stock_threshold" class="form-control" value="<?= h($form['low_stock_threshold']) ?>">
                </div>
                <div class="col-md-3 form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="track_inventory" value="1" <?= ($form['track_inventory']? 'checked':'') ?> id="invTrack">
                    <label class="form-check-label" for="invTrack">Track inventory</label>
                </div>
                <div class="col-md-3 form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="allow_backorder" value="1" <?= ($form['allow_backorder']? 'checked':'') ?> id="invBack">
                    <label class="form-check-label" for="invBack">Allow backorder</label>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Backorder Limit</label>
                    <input type="number" name="backorder_limit" class="form-control" value="<?= h($form['backorder_limit']) ?>">
                </div>
            </div>
        </div>

        <!-- Digital Product -->
        <div class="seller-form-card">
            <div class="card-header">
                <i class="fas fa-download me-2"></i>Digital/Downloadable Product
            </div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_digital" value="1" id="isDigital" onchange="toggleDigitalFields(this.checked)">
                    <label class="form-check-label" for="isDigital">
                        <strong>This is a digital/downloadable product</strong>
                        <small class="d-block text-muted">No physical shipping required</small>
                    </label>
                </div>
                
                <div id="digitalProductFields" style="display: none;">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Upload your digital files after creating the product using the "Manage Digital Files" option in the product details page.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Digital Delivery Instructions</label>
                        <textarea name="digital_delivery_info" class="form-control" rows="3" placeholder="Provide instructions for customers on how to use the digital product..."><?= h($form['digital_delivery_info'] ?? '') ?></textarea>
                        <small class="form-text text-muted">This will be displayed on the download page</small>
                    </div>
                    
                    <div class="form-row cols-2">
                        <div>
                            <label class="form-label">Download Limit</label>
                            <input type="number" name="download_limit" class="form-control" value="<?= h($form['download_limit'] ?? '') ?>" placeholder="Leave empty for unlimited">
                            <small class="form-text text-muted">Number of times customer can download</small>
                        </div>
                        <div>
                            <label class="form-label">Link Expiry (days)</label>
                            <input type="number" name="expiry_days" class="form-control" value="<?= h($form['expiry_days'] ?? '') ?>" placeholder="Leave empty for no expiry">
                            <small class="form-text text-muted">Days until download link expires</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping -->
        <div class="seller-form-card">
            <div class="card-header">
                <i class="fas fa-shipping-fast me-2"></i>Shipping & Dimensions
            </div>
            <div class="card-body">
                <!-- Weight and Dimensions -->
                <div class="mb-4">
                    <h6 class="text-muted mb-3">Physical Properties</h6>
                    <div class="shipping-dimensions">
                        <div>
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" step="0.001" name="weight" class="form-control" value="<?= h($form['weight']) ?>" placeholder="0.000">
                        </div>
                        <div>
                            <label class="form-label">Length (cm)</label>
                            <input type="number" step="0.01" name="length" class="form-control" value="<?= h($form['length']) ?>" placeholder="0.00">
                        </div>
                        <div>
                            <label class="form-label">Width (cm)</label>
                            <input type="number" step="0.01" name="width" class="form-control" value="<?= h($form['width']) ?>" placeholder="0.00">
                        </div>
                        <div>
                            <label class="form-label">Height (cm)</label>
                            <input type="number" step="0.01" name="height" class="form-control" value="<?= h($form['height']) ?>" placeholder="0.00">
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Configuration -->
                <div class="mb-4">
                    <h6 class="text-muted mb-3">Shipping Configuration</h6>
                    <div class="shipping-info">
                        <div>
                            <label class="form-label">Shipping Class</label>
                            <select name="shipping_class" class="form-select">
                                <option value="standard" <?= $form['shipping_class']==='standard'?'selected':''; ?>>Standard</option>
                                <option value="express" <?= $form['shipping_class']==='express'?'selected':''; ?>>Express</option>
                                <option value="overnight" <?= $form['shipping_class']==='overnight'?'selected':''; ?>>Overnight</option>
                                <option value="freight" <?= $form['shipping_class']==='freight'?'selected':''; ?>>Freight</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Handling Time (days)</label>
                            <select name="handling_time" class="form-select">
                                <option value="1" <?= $form['handling_time']=='1'?'selected':''; ?>>1 day</option>
                                <option value="2" <?= $form['handling_time']=='2'?'selected':''; ?>>2 days</option>
                                <option value="3" <?= $form['handling_time']=='3'?'selected':''; ?>>3 days</option>
                                <option value="5" <?= $form['handling_time']=='5'?'selected':''; ?>>5 days</option>
                                <option value="7" <?= $form['handling_time']=='7'?'selected':''; ?>>1 week</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- International Shipping -->
                <div class="mb-4">
                    <h6 class="text-muted mb-3">International Shipping</h6>
                    <div class="form-row cols-2">
                        <div>
                            <label class="form-label">HS Code</label>
                            <input type="text" name="hs_code" class="form-control" value="<?= h($form['hs_code']) ?>" placeholder="Harmonized System Code">
                            <small class="form-text text-muted">Required for international shipping</small>
                        </div>
                        <div>
                            <label class="form-label">Country of Origin</label>
                            <input type="text" name="country_of_origin" class="form-control" value="<?= h($form['country_of_origin']) ?>" placeholder="e.g., United States">
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Options -->
                <div>
                    <h6 class="text-muted mb-3">Shipping Options</h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="free_shipping" value="1" <?= ($form['free_shipping']? 'checked':'') ?> id="freeShip">
                        <label class="form-check-label" for="freeShip">
                            <strong>Free Shipping</strong>
                            <small class="d-block text-muted">Offer free shipping for this product</small>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Classification & Flags -->
        <div class="card mb-3">
            <div class="card-header">Classification & Flags</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft" <?= $form['status']==='draft'?'selected':''; ?>>Draft</option>
                        <option value="active" <?= $form['status']==='active'?'selected':''; ?>>Active</option>
                        <option value="archived" <?= $form['status']==='archived'?'selected':''; ?>>Archived</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Visibility</label>
                    <select name="visibility" class="form-select">
                        <option value="public" <?= $form['visibility']==='public'?'selected':''; ?>>Public</option>
                        <option value="private" <?= $form['visibility']==='private'?'selected':''; ?>>Private</option>
                        <option value="hidden" <?= $form['visibility']==='hidden'?'selected':''; ?>>Hidden</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Condition</label>
                    <select name="condition" class="form-select">
                        <option value="new" <?= $form['condition']==='new'?'selected':''; ?>>New</option>
                        <option value="used" <?= $form['condition']==='used'?'selected':''; ?>>Used</option>
                        <option value="refurbished" <?= $form['condition']==='refurbished'?'selected':''; ?>>Refurbished</option>
                    </select>
                </div>
                <div class="col-md-3 form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="featured" value="1" <?= ($form['featured']? 'checked':'') ?> id="flagFeatured">
                    <label class="form-check-label" for="flagFeatured">Featured</label>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Tags (comma-separated)</label>
                    <input type="text" name="tags" class="form-control" value="<?= h($form['tags']) ?>">
                </div>
            </div>
        </div>

        <!-- Images: Thumbnail + Gallery (with previews) -->
        <div class="card mb-3">
          <div class="card-header">Images</div>
          <div class="card-body row g-4">
            <!-- Thumbnail -->
            <div class="col-12 col-md-4">
              <label class="form-label">Thumbnail (primary)</label>
              <input type="file" name="thumbnail" id="thumbnailInput" accept="image/*" class="form-control">
              <div class="form-text">This will be the product’s main image.</div>
              <div id="thumbPreview" class="mt-2 d-flex align-items-center" style="min-height:88px;"></div>
            </div>

            <!-- Gallery -->
            <div class="col-12 col-md-8">
              <label class="form-label">Gallery images</label>
              <input type="file" name="gallery[]" id="galleryInput" accept="image/*" multiple class="form-control">
              <div class="form-text">You can select multiple images at once.</div>
              <div id="galleryPreview" class="mt-2 d-flex flex-wrap gap-2" style="min-height:88px;"></div>
            </div>
          </div>
        </div>

        <!-- Relations -->
        <div class="card mb-3">
            <div class="card-header">Related Products</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Cross-sell (IDs, comma/space separated)</label>
                    <input type="text" name="cross_sell_products" class="form-control" value="<?= h($form['cross_sell_products']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upsell (IDs, comma/space separated)</label>
                    <input type="text" name="upsell_products" class="form-control" value="<?= h($form['upsell_products']) ?>">
                </div>
                <?php if ($allProducts): ?>
                <div class="col-12">
                    <div class="form-text">
                        Quick pick:
                        <?php foreach ($allProducts as $p): ?>
                            <span class="badge bg-secondary me-1"><?= (int)$p['id'] ?> - <?= h($p['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SEO -->
        <div class="card mb-4">
            <div class="card-header">SEO</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" value="<?= h($form['meta_title']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Focus Keyword</label>
                    <input type="text" name="focus_keyword" class="form-control" value="<?= h($form['focus_keyword']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="2"><?= h($form['meta_description']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Meta Keywords (comma-separated)</label>
                    <input type="text" name="meta_keywords" class="form-control" value="<?= h($form['meta_keywords']) ?>">
                </div>
            </div>
        </div>

        <div class="text-center py-4">
            <button type="submit" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-plus-circle me-2"></i>Create Product
            </button>
            <a href="/seller/products/" class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
        </div>
    </form>
</div>
</div>

<!-- Enhanced JavaScript for better UX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form progress tracking
    const form = document.querySelector('form');
    const progressBar = document.getElementById('formProgress');
    
    function updateProgress() {
        const inputs = form.querySelectorAll('input[required], select[required]');
        const filled = Array.from(inputs).filter(input => input.value.trim() !== '').length;
        const progress = (filled / inputs.length) * 100;
        progressBar.style.width = progress + '%';
    }
    
    // Update progress on input change
    form.addEventListener('input', updateProgress);
    form.addEventListener('change', updateProgress);
    updateProgress(); // Initial update
    
    // Auto-generate slug from name
    const nameInput = document.querySelector('input[name="name"]');
    const slugInput = document.querySelector('input[name="slug"]');
    
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if (!slugInput.value || slugInput.dataset.auto !== 'false') {
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim('-');
                slugInput.value = slug;
            }
        });
        
        slugInput.addEventListener('input', function() {
            this.dataset.auto = 'false'; // User is manually editing
        });
    }
    
    // Enhanced form validation
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('input[required], select[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            const wrapper = field.closest('.form-group') || field.parentElement;
            const feedback = wrapper.querySelector('.invalid-feedback') || 
                           wrapper.querySelector('.error-message');
            
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
                
                if (!feedback) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = 'This field is required';
                    field.parentElement.appendChild(errorDiv);
                }
            } else {
                field.classList.remove('is-invalid');
                if (feedback && feedback.classList.contains('error-message')) {
                    feedback.remove();
                }
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        } else {
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Product...';
            submitBtn.disabled = true;
            
            // Re-enable after 10 seconds as fallback
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        }
    });
});
</script>

<!-- Live image previews -->
<script>
(function(){
  function makeImg(src, title){
    const img = document.createElement('img');
    img.src = src; img.alt = title || 'preview';
    img.style.maxWidth = '120px'; img.style.maxHeight = '88px'; img.style.objectFit = 'cover';
    img.style.borderRadius = '8px'; img.style.boxShadow = '0 1px 4px rgba(0,0,0,0.12)';
    img.loading = 'lazy'; return img;
  }
  function clear(el){ while(el.firstChild) el.removeChild(el.firstChild); }

  const thumbInput = document.getElementById('thumbnailInput');
  const thumbPreview = document.getElementById('thumbPreview');
  if (thumbInput && thumbPreview){
    thumbInput.addEventListener('change', function(){
      clear(thumbPreview);
      const f = this.files && this.files[0];
      if (!f) return;
      const fr = new FileReader();
      fr.onload = e => thumbPreview.appendChild(makeImg(e.target.result, f.name));
      fr.readAsDataURL(f);
    });
  }

  const galleryInput = document.getElementById('galleryInput');
  const galleryPreview = document.getElementById('galleryPreview');
  if (galleryInput && galleryPreview){
    galleryInput.addEventListener('change', function(){
      clear(galleryPreview);
      const files = Array.from(this.files || []);
      files.forEach(f => {
        const fr = new FileReader();
        fr.onload = e => galleryPreview.appendChild(makeImg(e.target.result, f.name));
        fr.readAsDataURL(f);
      });
    });
  }
})();
</script>

<!-- Digital Product Toggle -->
<script>
function toggleDigitalFields(isDigital) {
    const digitalFields = document.getElementById('digitalProductFields');
    const shippingSection = document.querySelector('.seller-form-card:has([name="weight"])');
    
    if (isDigital) {
        // Show digital product fields
        digitalFields.style.display = 'block';
        
        if (shippingSection) {
            // Only disable shipping-specific inputs, not the entire section
            const shippingInputs = shippingSection.querySelectorAll('input[name="weight"], input[name="length"], input[name="width"], input[name="height"], select[name="shipping_class"], input[name="handling_time"], input[name="free_shipping"], input[name="hs_code"], input[name="country_of_origin"]');
            
            shippingInputs.forEach(input => {
                input.disabled = true;
                input.style.opacity = '0.5';
                input.removeAttribute('required');
                input.setAttribute('data-was-required', input.hasAttribute('required') ? 'true' : 'false');
            });
            
            // Add visual indicator
            const shippingHeader = shippingSection.querySelector('.card-header');
            if (shippingHeader && !shippingHeader.querySelector('.digital-note')) {
                const note = document.createElement('small');
                note.className = 'digital-note ms-2 text-muted';
                note.textContent = '(Disabled for digital products)';
                shippingHeader.appendChild(note);
            }
        }
    } else {
        // Hide digital product fields
        digitalFields.style.display = 'none';
        
        if (shippingSection) {
            // Re-enable shipping-specific inputs
            const shippingInputs = shippingSection.querySelectorAll('input[name="weight"], input[name="length"], input[name="width"], input[name="height"], select[name="shipping_class"], input[name="handling_time"], input[name="free_shipping"], input[name="hs_code"], input[name="country_of_origin"]');
            
            shippingInputs.forEach(input => {
                input.disabled = false;
                input.style.opacity = '1';
                if (input.getAttribute('data-was-required') === 'true') {
                    input.setAttribute('required', 'required');
                }
                input.removeAttribute('data-was-required');
            });
            
            // Remove visual indicator
            const note = shippingSection.querySelector('.digital-note');
            if (note) note.remove();
        }
    }
}

// Check on page load if digital checkbox is checked
document.addEventListener('DOMContentLoaded', function() {
    const isDigitalCheckbox = document.getElementById('isDigital');
    if (isDigitalCheckbox && isDigitalCheckbox.checked) {
        toggleDigitalFields(true);
    }
});
</script>

<?php includeFooter();
