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
if ($id<=0){ header('Location:/seller/products/'); exit; }
$imgCols = db_columns_for_table('product_images');
if ($_SERVER['REQUEST_METHOD']==='POST' && function_exists('verifyCsrfToken')) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(419); die('Invalid CSRF token'); }
    if (!empty($_POST['delete']) && is_array($_POST['delete'])) {
        $ids = array_values(array_unique(array_map('intval', $_POST['delete'])));
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            Database::query("DELETE FROM product_images WHERE product_id=? AND id IN ($in)", array_merge([$id], $ids));
        }
    }
    header('Location:/seller/products/media.php?id='.$id.'&msg=updated'); exit;
}
$sel = "SELECT id,file_path"; 
if (db_has_col($imgCols,'is_primary')) $sel .= ",is_primary";
if (db_has_col($imgCols,'type')) $sel .= ",type";
if (db_has_col($imgCols,'image_url')) $sel .= ",image_url";
$sel .= " FROM product_images WHERE product_id=? ORDER BY is_primary DESC, id ASC";
$images = Database::query($sel, [$id])->fetchAll(PDO::FETCH_ASSOC) ?: [];
$page_title = 'Manage Media';
$breadcrumb_items = [
    ['title' => 'Products', 'url' => '/seller/products/'],
    ['title' => 'Manage Media']
];
includeHeader($page_title); ?>
<div class="container my-4">
  <h1 class="h4 mb-3">Manage Media</h1>
  <form method="post">
    <?= function_exists('csrfTokenInput')? csrfTokenInput(): '' ?>
    <div class="row g-3">
      <?php foreach ($images as $im): ?>
        <div class="col-6 col-md-3">
          <div class="border rounded p-2">
            <img src="<?= h($im['image_url'] ?? $im['file_path']) ?>" class="img-fluid" style="object-fit:cover;aspect-ratio:1/1;">
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="delete[]" value="<?= (int)$im['id'] ?>" id="img<?= (int)$im['id'] ?>">
              <label class="form-check-label" for="img<?= (int)$im['id'] ?>">Delete</label>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$images): ?>
        <div class="text-muted">No images.</div>
      <?php endif; ?>
    </div>
    <div class="mt-3">
      <button class="btn btn-danger">Apply</button>
      <a href="/seller/products/edit.php?id=<?= (int)$id ?>" class="btn btn-outline-secondary">Back</a>
    </div>
  </form>
</div>
<?php includeFooter(); ?>
