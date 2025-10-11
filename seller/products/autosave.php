<?php
declare(strict_types=1);
/**
 * Seller Portal - Product Autosave (JSON)
 * - Creates a draft on first save, then updates fields incrementally
 * - PHP 8.1+ safe; checks seller ownership
 * - Adaptive to schema (handles products.image_url if NOT NULL)
 * - CSRF optional: if header X-CSRF or field csrf_token present, verify when available
 */
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

header('Content-Type: application/json');

if (!class_exists('Session') || !Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!function_exists('h')) { function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }

function toNullIfEmpty($v){ $v=is_string($v)?trim($v):$v; return ($v===''||$v===null)?null:$v; }
function toNumericOrNull($v){ return ($v===''||$v===null)?null:(is_numeric($v)?0+$v:null); }
function toBool($v): int { return (!empty($v) && $v !== '0') ? 1 : 0; }

function db_columns_for_table(string $table): array{
  static $c=[]; if(isset($c[$table])) return $c[$table];
  try{
    // Use MySQL/MariaDB DESCRIBE 
    $r=Database::query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN, 0);
    return $c[$table]=array_flip($r?:[]);
  }catch(Throwable $e){ 
    // Fallback to information_schema
    try{
      $r=Database::query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",[$table])->fetchAll(PDO::FETCH_COLUMN);
      return $c[$table]=array_flip($r?:[]);
    }catch(Throwable $e2){ 
      return $c[$table]=[]; 
    }
  }
}
function db_has_col(array $cols, string $n): bool { return isset($cols[$n]); }

// CSRF: accept either header X-CSRF-Token or post field csrf_token if present
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_CSRF'] ?? null;
$csrfField  = $_POST['csrf_token'] ?? null;
if (($csrfHeader || $csrfField) && function_exists('verifyCsrfToken')) {
    if (!verifyCsrfToken((string)($csrfHeader ?: $csrfField))) {
        http_response_code(419);
        echo json_encode(['success'=>false,'error'=>'Invalid CSRF token']); exit;
    }
}

$userId = Session::getUserId();

// Parse JSON if content-type is application/json
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $_POST = $json + $_POST;
    }
}

// Inputs
$productId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$data = $_POST['data'] ?? $_POST; // allow nested {data:{...}} or flat

try {
    Database::beginTransaction();

    $pCols = db_columns_for_table('products');

    // Ensure vendor - use standardized vendor lookup
    $vendorId = null;
    if (db_has_col($pCols, 'vendor_id')) {
        try {
            $vendorId = DataScopeMiddleware::getCurrentVendorId();
        } catch (Throwable $e) {
            error_log("Autosave vendor lookup failed: ".$e->getMessage());
        }
        if (!$vendorId) { $vendorId = null; }
    }

    $now = date('Y-m-d H:i:s');

    if ($productId > 0) {
        // Ownership check
        $owner = Database::query("SELECT id FROM products WHERE id=? AND seller_id=? LIMIT 1", [$productId, $userId])->fetchColumn();
        if (!$owner) { throw new RuntimeException('Product not found or not owned by you'); }
    } else {
        // Create draft product with minimal required fields
        $name  = trim((string)($data['name'] ?? 'Untitled product'));
        $slug  = trim((string)($data['slug'] ?? ''));
        if ($slug==='') $slug = slugify($name);

        $fieldMap = [
          'name'=>$name,'slug'=>$slug,'seller_id'=>$userId,
          'status'=>'draft','visibility'=> 'private',
          'created_at'=>$now,'updated_at'=>$now
        ];
        if ($vendorId !== null) $fieldMap['vendor_id'] = $vendorId;

        // Set NOT NULL columns sensible defaults if present
        if (db_has_col($pCols,'price')) $fieldMap['price'] = toNumericOrNull($data['price'] ?? 0) ?? 0;
        if (db_has_col($pCols,'stock_quantity')) $fieldMap['stock_quantity'] = (int) (toNumericOrNull($data['stock_quantity'] ?? 0) ?? 0);
        if (db_has_col($pCols,'currency_code')) $fieldMap['currency_code'] = strtoupper(trim((string)($data['currency_code'] ?? 'USD')));
        if (db_has_col($pCols,'image_url')) $fieldMap['image_url'] = ''; // prevent NOT NULL issues

        $cols=[];$phs=[];$prm=[];
        foreach($fieldMap as $c=>$v){ if(db_has_col($pCols,$c)){ $cols[]="`$c`"; $phs[]=":$c"; $prm[":$c"]=$v; } }
        if (!$cols) throw new RuntimeException('No matching columns found to create draft.');
        Database::query("INSERT INTO products (".implode(',',$cols).") VALUES (".implode(',',$phs).")", $prm);
        $productId = (int)Database::lastInsertId();
    }

    // Now apply partial updates from $data
    $updates = [];
    $params  = [':id'=>$productId, ':seller_id'=>$userId];

    // Simple mappable fields
    $map = [
      'name','slug','sku','short_description','description','status','visibility','condition','currency_code',
      'meta_title','meta_description','meta_keywords','focus_keyword','shipping_class','handling_time','hs_code','country_of_origin','tags'
    ];
    foreach($map as $k){
        if (array_key_exists($k,$data) && db_has_col($pCols,$k)){
            $updates[]="`$k`=:$k";
            $v=$data[$k];
            if (in_array($k, ['slug','name']) && is_string($v) && trim($v)==='' && $k==='slug') {
                $v = slugify((string)($data['name'] ?? 'product-'.$productId));
            }
            $params[":$k"] = is_string($v) ? trim($v) : $v;
        }
    }

    // Numeric/boolean fields
    $numMap = [
      'price','compare_price','cost_price','sale_price','backorder_limit','weight','length','width','height'
    ];
    foreach($numMap as $k){
        if(array_key_exists($k,$data) && db_has_col($pCols,$k)){
            $updates[]="`$k`=:$k";
            $params[":$k"] = toNumericOrNull($data[$k]);
        }
    }
    $intMap = ['stock_quantity','low_stock_threshold'];
    foreach($intMap as $k){
        if(array_key_exists($k,$data) && db_has_col($pCols,$k)){
            $updates[]="`$k`=:$k";
            $params[":$k"] = (int)(toNumericOrNull($data[$k]) ?? 0);
        }
    }
    $boolMap = ['track_inventory','allow_backorder','free_shipping','featured'];
    foreach($boolMap as $k){
        if(array_key_exists($k,$data) && db_has_col($pCols,$k)){
            $updates[]="`$k`=:$k";
            $params[":$k"] = toBool($data[$k]);
        }
    }
    // dates
    foreach (['sale_start_date','sale_end_date'] as $k){
        if(array_key_exists($k,$data) && db_has_col($pCols,$k)){
            $updates[]="`$k`=:$k";
            $params[":$k"] = toNullIfEmpty($data[$k]);
        }
    }
    // foreign keys
    foreach (['category_id','brand_id'] as $k){
        if(array_key_exists($k,$data) && db_has_col($pCols,$k)){
            $updates[]="`$k`=:$k"; $params[":$k"] = toNullIfEmpty($data[$k]);
        }
    }

    if ($updates){
        $updates[]="`updated_at`=:updated_at";
        $params[':updated_at']=$now;
        $sql="UPDATE products SET ".implode(',',$updates)." WHERE id=:id AND seller_id=:seller_id";
        Database::query($sql, $params);
    }

    Database::commit();
    echo json_encode(['success'=>true,'id'=>$productId]);
} catch (Throwable $e) {
    try{ Database::rollback(); }catch(Throwable $ignore){}
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
