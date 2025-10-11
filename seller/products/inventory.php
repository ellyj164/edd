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

if ($_SERVER['REQUEST_METHOD']==='POST' && function_exists('verifyCsrfToken')) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { http_response_code(419); die('Invalid CSRF token'); }
}
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if ($id<=0){ header('Location:/seller/products/'); exit; }
$pCols = db_columns_for_table('products');
if (!db_has_col($pCols,'stock_quantity')){ header('Location:/seller/products/edit.php?id='.$id); exit; }
try {
  $p = Database::query("SELECT id,stock_quantity FROM products WHERE id=? AND seller_id=? LIMIT 1", [$id, Session::getUserId()])->fetch(PDO::FETCH_ASSOC);
  if (!$p) throw new RuntimeException('Not found');
  if ($_SERVER['REQUEST_METHOD']==='POST') {
    $delta = (int)($_POST['delta'] ?? 0);
    $newQty = max(0, ((int)$p['stock_quantity']) + $delta);
    Database::query("UPDATE products SET stock_quantity=?, updated_at=NOW() WHERE id=? AND seller_id=?", [$newQty, $id, Session::getUserId()]);
    header('Location:/seller/products/edit.php?id='.$id.'&msg=stock-updated'); exit;
  }
} catch (Throwable $e) { $err = $e->getMessage(); }
require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="container my-4">
  <h1 class="h4">Adjust Stock</h1>
  <?php if (!empty($err)): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
  <form method="post">
    <?= function_exists('csrfTokenInput')? csrfTokenInput(): '' ?>
    <div class="mb-3"><label class="form-label">Change by (+/-)</label>
      <input type="number" name="delta" class="form-control" value="1">
    </div>
    <button class="btn btn-primary">Update</button>
    <a href="/seller/products/edit.php?id=<?= (int)$id ?>" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
