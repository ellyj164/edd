<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/functions.php';
if (!class_exists('Session') || !Session::isLoggedIn()) { header('Location: /login.php'); exit; }
if (!function_exists('h')) { function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }
function toBool($v): int { return (!empty($v) && $v !== '0') ? 1 : 0; }
function toNullIfEmpty($v){ $v=is_string($v)?trim($v):$v; return ($v===''||$v===null)?null:$v; }
function toNumericOrNull($v){ return ($v===''||$v===null)?null:(is_numeric($v)?0+$v:null); }
function db_columns_for_table(string $table): array{
  static $c=[]; if(isset($c[$table])) return $c[$table];
  try{ $r=Database::query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",[$table])->fetchAll(PDO::FETCH_COLUMN);
       return $c[$table]=array_flip($r?:[]);
  }catch(Throwable $e){ return $c[$table]=[]; }
}
function db_has_col(array $cols, string $n): bool { return isset($cols[$n]); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0) { header('Location: /seller/products/'); exit; }
try {
    $p = Database::query("SELECT * FROM products WHERE id=? AND seller_id=? LIMIT 1", [$id, Session::getUserId()])->fetch(PDO::FETCH_ASSOC);
    if (!$p) { header('Location:/seller/products/'); exit; }
} catch (Throwable $e) {
    $p = null;
}
$imgCols = db_columns_for_table('product_images');
$sel = "SELECT id,file_path"; 
if (db_has_col($imgCols,'is_primary')) $sel .= ",is_primary";
if (db_has_col($imgCols,'type')) $sel .= ",type";
if (db_has_col($imgCols,'image_url')) $sel .= ",image_url";
$sel .= " FROM product_images WHERE product_id=? ORDER BY is_primary DESC, id ASC";
$images = Database::query($sel, [$id])->fetchAll(PDO::FETCH_ASSOC) ?: [];
$page_title = 'Product Details';
$breadcrumb_items = [
    ['title' => 'Products', 'url' => '/seller/products/'],
    ['title' => 'Product Details']
];
includeHeader($page_title);
?>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Product Details</h1>
    <div>
      <a href="/seller/products/edit.php?id=<?= (int)$id ?>" class="btn btn-primary">Edit</a>
      <a href="/seller/products/" class="btn btn-outline-secondary">Back</a>
    </div>
  </div>
  <?php if(!$p): ?>
    <div class="alert alert-danger">Product not found.</div>
  <?php else: ?>
    <div class="row g-4">
      <div class="col-md-5">
        <?php if ($images): ?>
          <div class="border rounded p-2 mb-3">
            <img src="<?= h($images[0]['image_url'] ?? $images[0]['file_path']) ?>" alt="" class="img-fluid" style="object-fit:cover; width:100%; aspect-ratio:1/1;">
          </div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($images as $im): ?>
              <img src="<?= h($im['image_url'] ?? $im['file_path']) ?>" class="rounded" style="width:84px;height:84px;object-fit:cover;">
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="text-muted">No images.</div>
        <?php endif; ?>
      </div>
      <div class="col-md-7">
        <h2 class="h4 mb-1"><?= h($p['name'] ?? '') ?></h2>
        <div class="text-muted mb-3"><?= h($p['sku'] ?? '') ?></div>
        <div class="mb-3"><?= nl2br(h($p['short_description'] ?? '')) ?></div>
        <div class="mb-3"><?= nl2br(h($p['description'] ?? '')) ?></div>
        <div class="mt-3">
          <a class="btn btn-outline-secondary" href="/product/<?= h($p['slug'] ?? '') ?>" target="_blank">View on site</a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php includeFooter(); ?>
