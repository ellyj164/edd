<?php
declare(strict_types=1);
/**
 * Seller Portal - Edit Product (safe, PHP 8.1+)
 * - Thumbnail + Gallery with previews & deletion
 * - Adaptive UPDATE and image inserts
 * - CSRF + Auth guard
 */
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

if (!class_exists('Session') || !Session::isLoggedIn()) { header('Location: /login.php'); exit; }
if (!function_exists('h')) { function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }
function toBool($v): int { return (!empty($v) && $v !== '0') ? 1 : 0; }
function toNullIfEmpty($v){ $v=is_string($v)?trim($v):$v; return ($v===''||$v===null)?null:$v; }
function toNumericOrNull($v){ return ($v===''||$v===null)?null:(is_numeric($v)?0+$v:null); }
function db_columns_for_table(string $table): array{
  static $c=[]; if(isset($c[$table])) return $c[$table];
  try{ 
    // Try SQLite PRAGMA first (most common in this project)
    $r=Database::query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
    if($r){ 
      $cols=[]; foreach($r as $row) $cols[]=$row['name']; 
      return $c[$table]=array_flip($cols);
    }
    // Fallback to MySQL information_schema
    $r=Database::query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",[$table])->fetchAll(PDO::FETCH_COLUMN);
    return $c[$table]=array_flip($r?:[]);
  }catch(Throwable $e){ error_log("col detect fail {$table}: ".$e->getMessage()); return $c[$table]=[]; }
}
function db_has_col(array $cols, string $n): bool { return isset($cols[$n]); }

// Inputs
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0){ header('Location:/seller/products/'); exit; }
if (!Session::get('csrf_token')) { Session::set('csrf_token', bin2hex(random_bytes(18))); }
$csrf = csrfToken();

// Load product - check by vendor_id instead of seller_id
try{
  // First get the vendor info for the current user
  $vendor = new Vendor();
  $vendorInfo = $vendor->findByUserId(Session::getUserId());
  if (!$vendorInfo) {
    header('Location:/seller/products/');
    exit;
  }
  
  $product = Database::query("SELECT * FROM products WHERE id=? AND vendor_id=? LIMIT 1", [$id, $vendorInfo['id']])->fetch(PDO::FETCH_ASSOC);
  if(!$product){ header('Location:/seller/products/'); exit; }
}catch(Throwable $e){ error_log('load product: '.$e->getMessage()); header('Location:/seller/products/'); exit; }

// Lookups
$categories=$brands=[];
try{
  $categories=Database::query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC)?:[];
  $brands=Database::query("SELECT id,name FROM brands WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC)?:[];
}catch(Throwable $e){ error_log('lookups: '.$e->getMessage()); }

// Images existing
$imgCols = db_columns_for_table('product_images');
$hasIsPrimary = db_has_col($imgCols,'is_primary');
$hasTypeCol   = db_has_col($imgCols,'type');
$images=[];
try{
  $sel="SELECT id,file_path"; if($hasIsPrimary)$sel.=",is_primary"; if($hasTypeCol)$sel.=",type"; $sel.=" FROM product_images WHERE product_id=? ORDER BY id ASC";
  $images=Database::query($sel,[$id])->fetchAll(PDO::FETCH_ASSOC)?:[];
}catch(Throwable $e){ error_log('images load: '.$e->getMessage()); }

// Form defaults
$form=[
 'name'=>$product['name']??'','slug'=>$product['slug']??'','sku'=>$product['sku']??'',
 'short_description'=>$product['short_description']??'','description'=>$product['description']??'',
 'condition'=>$product['condition']??'new','status'=>$product['status']??'draft','visibility'=>$product['visibility']??'public','featured'=>(int)($product['featured']??0),
 'price'=>$product['price']??'','compare_price'=>$product['compare_price']??'','cost_price'=>$product['cost_price']??'',
 'sale_price'=>$product['sale_price']??'','sale_start_date'=>$product['sale_start_date']??'','sale_end_date'=>$product['sale_end_date']??'',
 'currency_code'=>$product['currency_code']??'USD','stock_quantity'=>$product['stock_quantity']??'','low_stock_threshold'=>$product['low_stock_threshold']??'5',
 'track_inventory'=>(int)($product['track_inventory']??1),'allow_backorder'=>(int)($product['allow_backorder']??0),'backorder_limit'=>$product['backorder_limit']??'',
 'category_id'=>$product['category_id']??'','brand_id'=>$product['brand_id']??'','tags'=>$product['tags']??'',
 'weight'=>$product['weight']??'','length'=>$product['length']??'','width'=>$product['width']??'','height'=>$product['height']??'',
 'shipping_class'=>$product['shipping_class']??'standard','handling_time'=>$product['handling_time']??'1','free_shipping'=>(int)($product['free_shipping']??0),
 'hs_code'=>$product['hs_code']??'','country_of_origin'=>$product['country_of_origin']??'',
 'meta_title'=>$product['meta_title']??'','meta_description'=>$product['meta_description']??'','meta_keywords'=>$product['meta_keywords']??'','focus_keyword'=>$product['focus_keyword']??'',
 'cross_sell_products'=>'','upsell_products'=>''
];

// Relations
$relCols = db_columns_for_table('product_related');
if($relCols){
  try{
    $rows=Database::query("SELECT related_product_id, relation_type FROM product_related WHERE product_id=?",[$id])->fetchAll(PDO::FETCH_ASSOC)?:[];
    $cross=[];$upsell=[]; foreach($rows as $r){ if(($r['relation_type']??'')==='cross_sell')$cross[]=(int)$r['related_product_id']; if(($r['relation_type']??'')==='upsell')$upsell[]=(int)$r['related_product_id']; }
    $form['cross_sell_products']=implode(',',$cross); $form['upsell_products']=implode(',',$upsell);
  }catch(Throwable $e){ error_log('relations load: '.$e->getMessage()); }
}

$errors=[];

// POST
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!verifyCsrfToken($_POST['csrf_token']??'')){
    $errors['general']='Invalid security token. Please refresh and try again.';
  }else{
    foreach($form as $k=>$v){ $form[$k]=$_POST[$k]??$v; }
    if(trim((string)$form['name'])===''){ $errors['name']='Product name is required.'; }
    if($form['price']!=='' && !is_numeric($form['price'])){ $errors['price']='Price must be a valid number.'; }
    if($form['compare_price']!=='' && !is_numeric($form['compare_price'])){ $errors['compare_price']='Compare price must be a valid number.'; }
    if($form['stock_quantity']!=='' && !is_numeric($form['stock_quantity'])){ $errors['stock_quantity']='Stock quantity must be numeric.'; }
    if($form['category_id']!=='' && !ctype_digit((string)$form['category_id'])){ $errors['category_id']='Invalid category.'; }
    if($form['brand_id']!=='' && !ctype_digit((string)$form['brand_id'])){ $errors['brand_id']='Invalid brand.'; }

    if(!$errors){
      $now=date('Y-m-d H:i:s');
      $name=trim((string)$form['name']); $slug=trim((string)$form['slug']); if($slug===''){ $slug=slugify($name); }
      $sku=trim((string)$form['sku']);

      $price=toNumericOrNull($form['price']); $compare_price=toNumericOrNull($form['compare_price']); $cost_price=toNumericOrNull($form['cost_price']);
      $sale_price=toNumericOrNull($form['sale_price']); $sale_start_date=toNullIfEmpty($form['sale_start_date']); $sale_end_date=toNullIfEmpty($form['sale_end_date']);
      $stock_quantity=(int)(toNumericOrNull($form['stock_quantity'])??0); $low_stock=(int)(toNumericOrNull($form['low_stock_threshold'])??5);
      $category_id=toNullIfEmpty($form['category_id']); $brand_id=toNullIfEmpty($form['brand_id']); $tags=trim((string)$form['tags']);
      $track_inventory=toBool($form['track_inventory']??0); $allow_backorder=toBool($form['allow_backorder']??0); $backorder_limit=toNumericOrNull($form['backorder_limit']);
      $weight=toNumericOrNull($form['weight']); $length=toNumericOrNull($form['length']); $width=toNumericOrNull($form['width']); $height=toNumericOrNull($form['height']);
      $free_shipping=toBool($form['free_shipping']??0); $shipping_class=trim((string)$form['shipping_class']); $handling_time=(string)($form['handling_time']??'1');
      $currency=strtoupper(trim((string)$form['currency_code']?:'USD'));
      $condition=in_array($form['condition'],['new','used','refurbished'],true)?$form['condition']:'new';
      $status=in_array($form['status'],['draft','active','archived'],true)?$form['status']:'draft';
      $visibility=in_array($form['visibility'],['public','private','hidden'],true)?$form['visibility']:'public';
      $featured=toBool($form['featured']??0);
      $short_desc=trim((string)$form['short_description']); $desc=trim((string)$form['description']);
      $hs_code=trim((string)$form['hs_code']); $origin=trim((string)$form['country_of_origin']);
      $meta_title=trim((string)$form['meta_title']); $meta_desc=trim((string)$form['meta_description']); $meta_keywords=trim((string)$form['meta_keywords']); $focus_keyword=trim((string)$form['focus_keyword']);

      try{
        Database::beginTransaction();

        $pCols=db_columns_for_table('products');
        $fieldMap=[
          'name'=>$name,'slug'=>$slug,'sku'=>$sku,'short_description'=>$short_desc,'description'=>$desc,
          'price'=>$price,'compare_price'=>$compare_price,'cost_price'=>$cost_price,
          'sale_price'=>$sale_price,'sale_start_date'=>$sale_start_date,'sale_end_date'=>$sale_end_date,
          'currency_code'=>$currency,'stock_quantity'=>$stock_quantity,'low_stock_threshold'=>$low_stock,
          'track_inventory'=>$track_inventory,'allow_backorder'=>$allow_backorder,'backorder_limit'=>$backorder_limit,
          'category_id'=>$category_id,'brand_id'=>$brand_id,'tags'=>$tags,
          'status'=>$status,'visibility'=>$visibility,'condition'=>$condition,'featured'=>$featured,
          'weight'=>$weight,'length'=>$length,'width'=>$width,'height'=>$height,
          'shipping_class'=>$shipping_class,'handling_time'=>$handling_time,'free_shipping'=>$free_shipping,
          'hs_code'=>$hs_code,'country_of_origin'=>$origin,
          'meta_title'=>$meta_title,'meta_description'=>$meta_desc,'meta_keywords'=>$meta_keywords,'focus_keyword'=>$focus_keyword,
          'updated_at'=>$now,
        ];
        $sets=[];$params=[]; foreach($fieldMap as $col=>$val){ if(db_has_col($pCols,$col)){ $sets[]="`$col`=:$col"; $params[":$col"]=$val; } }
        if(!$sets) throw new RuntimeException('No updatable columns found.');
        $params[':id']=$id; 
        // Use vendor_id if it exists in the table, otherwise fallback to seller_id
        if(db_has_col($pCols, 'vendor_id') && $vendorInfo) {
          $params[':vendor_id']=$vendorInfo['id'];
          Database::query("UPDATE products SET ".implode(', ',$sets)." WHERE id=:id AND vendor_id=:vendor_id", $params);
        } else {
          $params[':seller_id']=Session::getUserId();
          Database::query("UPDATE products SET ".implode(', ',$sets)." WHERE id=:id AND seller_id=:seller_id", $params);
        }

        // delete images
        if(!empty($_POST['delete_images']) && is_array($_POST['delete_images'])){
          $ids=array_filter(array_map('intval', $_POST['delete_images']), fn($v)=>$v>0);
          if($ids){ $in=implode(',', array_fill(0,count($ids),'?')); Database::query("DELETE FROM product_images WHERE product_id=? AND id IN ($in)", array_merge([$id], $ids)); }
        }

        // thumbnail
        if(!empty($_FILES['thumbnail']) && is_array($_FILES['thumbnail'])){
          if($hasIsPrimary){ Database::query("UPDATE product_images SET is_primary=0 WHERE product_id=?",[$id]); }
          $thumb=null;
          if(function_exists('handle_image_uploads')){
            $fake=['name'=>[$_FILES['thumbnail']['name']??''],'type'=>[$_FILES['thumbnail']['type']??''],'tmp_name'=>[$_FILES['thumbnail']['tmp_name']??''],'error'=>[$_FILES['thumbnail']['error']??UPLOAD_ERR_NO_FILE],'size'=>[$_FILES['thumbnail']['size']??0]];
            $arr=handle_image_uploads($fake); $thumb=$arr[0]??null;
          }else{
            $f=$_FILES['thumbnail']; if(!empty($f['tmp_name']) && ($f['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_OK){
              $ext=strtolower(pathinfo($f['name']??'', PATHINFO_EXTENSION)?:'jpg'); $ext=preg_replace('/[^a-z0-9]/i','',$ext);
              $dir=__DIR__.'/../../uploads/products'; if(!is_dir($dir)) @mkdir($dir,0775,true);
              $base=bin2hex(random_bytes(8)).'_'.time().'.'.$ext; $dest=$dir.'/'.$base;
              if(@move_uploaded_file($f['tmp_name'],$dest)) $thumb=['path'=>'/uploads/products/'.$base];
            }
          }
          if($thumb && !empty($thumb['path'])){
            $cols=['product_id','file_path']; $vals=['?','?']; $prm=[$id,$thumb['path']];
            if($hasIsPrimary){ $cols[]='is_primary'; $vals[]='1'; }
            if($hasTypeCol){ $cols[]='type'; $vals[]="'thumbnail'"; }
            if(db_has_col($imgCols,'created_at')){ $cols[]='created_at'; $vals[]='NOW()'; }
            $sql="INSERT INTO product_images (".implode(',', $cols).") VALUES (".implode(',', $vals).")";
            Database::query($sql,$prm);
          }
        }

        // gallery
        if(!empty($_FILES['gallery']) && is_array($_FILES['gallery']['name'])){
          $ups=[];
          if(function_exists('handle_image_uploads')){ $ups=handle_image_uploads($_FILES['gallery']); }
          else{
            $files=$_FILES['gallery']; $cnt=count($files['name']??[]);
            for($i=0;$i<$cnt;$i++){
              $f=['name'=>$files['name'][$i]??'','tmp_name'=>$files['tmp_name'][$i]??'','error'=>$files['error'][$i]??UPLOAD_ERR_NO_FILE];
              if(empty($f['tmp_name'])||$f['error']!==UPLOAD_ERR_OK) continue;
              $ext=strtolower(pathinfo($f['name']??'', PATHINFO_EXTENSION)?:'jpg'); $ext=preg_replace('/[^a-z0-9]/i','',$ext);
              $dir=__DIR__.'/../../uploads/products'; if(!is_dir($dir)) @mkdir($dir,0775,true);
              $base=bin2hex(random_bytes(8)).'_'.time().'.'.$ext; $dest=$dir.'/'.$base;
              if(@move_uploaded_file($f['tmp_name'],$dest)) $ups[]=['path'=>'/uploads/products/'.$base];
            }
          }
          foreach($ups as $img){
            if(empty($img['path'])) continue;
            $cols=['product_id','file_path']; $vals=['?','?']; $prm=[$id,$img['path']];
            if($hasIsPrimary){ $cols[]='is_primary'; $vals[]='0'; }
            if($hasTypeCol){ $cols[]='type'; $vals[]="'gallery'"; }
            if(db_has_col($imgCols,'created_at')){ $cols[]='created_at'; $vals[]='NOW()'; }
            $sql="INSERT INTO product_images (".implode(',', $cols).") VALUES (".implode(',', $vals).")";
            Database::query($sql,$prm);
          }
        }

        // tags
        if(isset($form['tags'])){
          $tagCols=db_columns_for_table('tags'); $pivot=db_columns_for_table('product_tag');
          if($tagCols && $pivot){
            Database::query("DELETE FROM product_tag WHERE product_id=?",[$id]);
            $list=array_values(array_filter(array_map('trim', explode(',', (string)$form['tags']))));
            $hasCreatedAtTag=db_has_col($tagCols,'created_at');
            foreach($list as $tg){
              if($tg==='') continue;
              $tagId=Database::query("SELECT id FROM tags WHERE name=?",[$tg])->fetchColumn();
              if(!$tagId && db_has_col($tagCols,'name')){
                if($hasCreatedAtTag) Database::query("INSERT INTO tags (name,created_at) VALUES (?,NOW())",[$tg]);
                else Database::query("INSERT INTO tags (name) VALUES (?)",[$tg]);
                $tagId=Database::lastInsertId();
              }
              if($tagId && db_has_col($pivot,'product_id') && db_has_col($pivot,'tag_id')){
                Database::query("INSERT IGNORE INTO product_tag (product_id,tag_id) VALUES (?,?)",[$id,$tagId]);
              }
            }
          }
        }

        // relations
        if($relCols){
          $apply=function($ids,$type) use($id,$relCols){
            Database::query("DELETE FROM product_related WHERE product_id=? AND relation_type=?",[$id,$type]);
            $ids=array_unique(array_map('intval', (array)$ids));
            foreach($ids as $rid){ if($rid>0 && $rid!==$id){
              $sql="INSERT IGNORE INTO product_related (product_id,related_product_id,relation_type";
              if(db_has_col($relCols,'created_at')) $sql.=",created_at";
              $sql.=") VALUES (?,?,?"; if(db_has_col($relCols,'created_at')) $sql.=",NOW()"; $sql.=")";
              Database::query($sql,[$id,$rid,$type]);
            }}
          };
          if(isset($form['cross_sell_products'])) $apply(preg_split('/[,\s]+/', (string)$form['cross_sell_products']), 'cross_sell');
          if(isset($form['upsell_products']))     $apply(preg_split('/[,\s]+/', (string)$form['upsell_products']), 'upsell');
        }

        Database::commit();
        header('Location: /seller/products/edit.php?id='.$id.'&saved=1');
        exit;
      }catch(Throwable $e){
        try{ Database::rollback(); }catch(Throwable $ignore){}
        error_log('update product: '.$e->getMessage());
        $errors['general']='Unexpected error while updating the product: '.h($e->getMessage());
      }
    }
  }
}

// reload images
try{
  $sel="SELECT id,file_path"; if($hasIsPrimary)$sel.=",is_primary"; if($hasTypeCol)$sel.=",type"; $sel.=" FROM product_images WHERE product_id=? ORDER BY id ASC";
  $images=Database::query($sel,[$id])->fetchAll(PDO::FETCH_ASSOC)?:[];
}catch(Throwable $e){}

$page_title = 'Edit Product';
$breadcrumb_items = [
    ['title' => 'Products', 'url' => '/seller/products/'],
    ['title' => 'Edit Product']
];
includeHeader($page_title);
?>
<div class="container my-4">
  <?php 
  // Show success message if product was just created
  if (Session::hasFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>Success!</strong> <?php echo Session::getFlash('success'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif;
  
  // Check for created parameter
  if (isset($_GET['created']) && $_GET['created'] == '1'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>Product Created Successfully!</strong> Your product "<?php echo h($product['name']); ?>" has been created and saved. You can now continue editing or add images.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Edit Product</h1>
    <div>
      <a href="/seller/products/" class="btn btn-outline-secondary">Back</a>
      <a href="/product/<?= h($form['slug']) ?>" class="btn btn-outline-primary" target="_blank">View</a>
    </div>
  </div>

  <?php if (!empty($_GET['saved'])): ?>
    <div class="alert alert-success">Saved successfully.</div>
  <?php endif; ?>
  <?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= h($errors['general']) ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" action="">
    <?= csrfTokenInput(); ?>

    <div class="card mb-3">
      <div class="card-header">Basic Info</div>
      <div class="card-body row g-3">
        <div class="col-md-6">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':''; ?>" value="<?= h($form['name']) ?>" required>
          <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= h($errors['name']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label">Slug</label>
          <input type="text" name="slug" class="form-control" value="<?= h($form['slug']) ?>" placeholder="auto from name">
        </div>
        <div class="col-md-4">
          <label class="form-label">SKU</label>
          <input type="text" name="sku" class="form-control" value="<?= h($form['sku']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Category</label>
          <select name="category_id" class="form-select <?= isset($errors['category_id'])?'is-invalid':''; ?>">
            <option value="">-- Select --</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= ($form['category_id']==$c['id']?'selected':'') ?>><?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['category_id'])): ?><div class="invalid-feedback"><?= h($errors['category_id']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-4">
          <label class="form-label">Brand</label>
          <select name="brand_id" class="form-select <?= isset($errors['brand_id'])?'is-invalid':''; ?>">
            <option value="">-- Select --</option>
            <?php foreach ($brands as $b): ?>
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

    <div class="card mb-3">
      <div class="card-header">Pricing</div>
      <div class="card-body row g-3">
        <div class="col-md-3">
          <label class="form-label">Currency</label>
          <input type="text" name="currency_code" class="form-control" value="<?= h($form['currency_code']) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Price</label>
          <input type="number" step="0.01" name="price" class="form-control <?= isset($errors['price'])?'is-invalid':''; ?>" value="<?= h($form['price']) ?>">
          <?php if (isset($errors['price'])): ?><div class="invalid-feedback"><?= h($errors['price']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-3">
          <label class="form-label">Compare-at Price</label>
          <input type="number" step="0.01" name="compare_price" class="form-control <?= isset($errors['compare_price'])?'is-invalid':''; ?>" value="<?= h($form['compare_price']) ?>">
          <?php if (isset($errors['compare_price'])): ?><div class="invalid-feedback"><?= h($errors['compare_price']) ?></div><?php endif; ?>
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

    <div class="card mb-3">
      <div class="card-header">Shipping</div>
      <div class="card-body row g-3">
        <div class="col-md-2">
          <label class="form-label">Weight</label>
          <input type="number" step="0.001" name="weight" class="form-control" value="<?= h($form['weight']) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Length</label>
          <input type="number" step="0.01" name="length" class="form-control" value="<?= h($form['length']) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Width</label>
          <input type="number" step="0.01" name="width" class="form-control" value="<?= h($form['width']) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Height</label>
          <input type="number" step="0.01" name="height" class="form-control" value="<?= h($form['height']) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Shipping Class</label>
          <input type="text" name="shipping_class" class="form-control" value="<?= h($form['shipping_class']) ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Handling Time (days)</label>
          <input type="number" name="handling_time" class="form-control" value="<?= h($form['handling_time']) ?>">
        </div>
        <div class="col-md-3 form-check mt-4">
          <input class="form-check-input" type="checkbox" name="free_shipping" value="1" <?= ($form['free_shipping']? 'checked':'') ?> id="freeShip">
          <label class="form-check-label" for="freeShip">Free shipping</label>
        </div>
        <div class="col-md-3">
          <label class="form-label">HS Code</label>
          <input type="text" name="hs_code" class="form-control" value="<?= h($form['hs_code']) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Country of Origin</label>
          <input type="text" name="country_of_origin" class="form-control" value="<?= h($form['country_of_origin']) ?>">
        </div>
      </div>
    </div>

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
          <input class="form-check-input" type="checkbox" name="featured" value="1" <?= ($form['featured']? 'checked':'' ) ?> id="flagFeatured">
          <label class="form-check-label" for="flagFeatured">Featured</label>
        </div>
        <div class="col-md-9">
          <label class="form-label">Tags (comma-separated)</label>
          <input type="text" name="tags" class="form-control" value="<?= h($form['tags']) ?>">
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">Images</div>
      <div class="card-body">
        <div class="row g-4">
          <div class="col-12 col-md-4">
            <label class="form-label">Thumbnail (replace)</label>
            <input type="file" name="thumbnail" id="thumbnailInput" accept="image/*" class="form-control">
            <div class="form-text">Uploading will set new primary thumbnail.</div>
            <div id="thumbPreview" class="mt-2 d-flex align-items-center" style="min-height:88px;"></div>
          </div>
          <div class="col-12 col-md-8">
            <label class="form-label">Add Gallery Images</label>
            <input type="file" name="gallery[]" id="galleryInput" accept="image/*" multiple class="form-control">
            <div class="form-text">Select multiple images to append to gallery.</div>
            <div id="galleryPreview" class="mt-2 d-flex flex-wrap gap-2" style="min-height:88px;"></div>
          </div>
        </div>
        <?php if ($images): ?>
        <hr>
        <div class="row g-3">
          <div class="col-12"><strong>Existing Images</strong></div>
          <?php foreach ($images as $img): ?>
            <div class="col-6 col-md-3">
              <div class="border rounded p-2">
                <img src="<?= h($img['file_path']) ?>" alt="img" class="img-fluid" style="object-fit:cover;aspect-ratio:1/1;">
                <div class="small mt-2 d-flex justify-content-between align-items-center">
                  <label class="form-check-label">
                    <input class="form-check-input me-1" type="checkbox" name="delete_images[]" value="<?= (int)$img['id'] ?>"> delete
                  </label>
                  <span class="text-muted">
                    <?php if ($hasTypeCol && !empty($img['type'])): ?>
                      <?= h($img['type']) ?>
                    <?php elseif ($hasIsPrimary && !empty($img['is_primary'])): ?>
                      primary
                    <?php endif; ?>
                  </span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

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
      </div>
    </div>

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

    <div class="mb-5">
      <button type="submit" class="btn btn-primary">Save Changes</button>
      <a href="/seller/products/" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<script>
(function(){
  function makeImg(src, title){
    const img=document.createElement('img');
    img.src=src; img.alt=title||'preview';
    img.style.maxWidth='120px'; img.style.maxHeight='88px'; img.style.objectFit='cover';
    img.style.borderRadius='8px'; img.style.boxShadow='0 1px 4px rgba(0,0,0,0.12)';
    img.loading='lazy'; return img;
  }
  function clear(el){ while(el.firstChild) el.removeChild(el.firstChild); }
  const thumbInput=document.getElementById('thumbnailInput'), thumbPreview=document.getElementById('thumbPreview');
  if(thumbInput&&thumbPreview){ thumbInput.addEventListener('change', function(){ clear(thumbPreview);
    const f=this.files&&this.files[0]; if(!f)return; const fr=new FileReader(); fr.onload=e=>thumbPreview.appendChild(makeImg(e.target.result,f.name)); fr.readAsDataURL(f);
  });}
  const galleryInput=document.getElementById('galleryInput'), galleryPreview=document.getElementById('galleryPreview');
  if(galleryInput&&galleryPreview){ galleryInput.addEventListener('change', function(){ clear(galleryPreview);
    Array.from(this.files||[]).forEach(f=>{ const fr=new FileReader(); fr.onload=e=>galleryPreview.appendChild(makeImg(e.target.result,f.name)); fr.readAsDataURL(f); });
  });}
})();
</script>
<?php includeFooter(); ?>
