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
try {
  Database::beginTransaction();
  $src = Database::query("SELECT * FROM products WHERE id=? AND seller_id=? LIMIT 1", [$id, Session::getUserId()])->fetch(PDO::FETCH_ASSOC);
  if (!$src) throw new RuntimeException('Product not found or not owned by you.');
  $pCols = db_columns_for_table('products');
  $now = date('Y-m-d H:i:s');
  $copy = $src;
  unset($copy['id']);
  if (isset($copy['slug'])) $copy['slug'] = $copy['slug'] . '-' . substr((string)time(), -4);
  if (isset($copy['name'])) $copy['name'] = $copy['name'] . ' (Copy)';
  if (isset($copy['status'])) $copy['status'] = 'draft';
  if (isset($copy['visibility'])) $copy['visibility'] = 'private';
  if (isset($copy['created_at'])) $copy['created_at'] = $now;
  if (isset($copy['updated_at'])) $copy['updated_at'] = $now;
  $cols=[];$ph=[];$prm=[];
  foreach($copy as $k=>$v){ if(db_has_col($pCols,$k)){ $cols[]="`$k`"; $ph[]=":$k"; $prm[":$k"]=$v; } }
  if(!$cols) throw new RuntimeException('Nothing to duplicate.');
  Database::query("INSERT INTO products (".implode(',',$cols).") VALUES (".implode(',',$ph).")", $prm);
  $newId = (int)Database::lastInsertId();

  // duplicate images
  $iCols = db_columns_for_table('product_images');
  $imgs = Database::query("SELECT * FROM product_images WHERE product_id=?", [$id])->fetchAll(PDO::FETCH_ASSOC) ?: [];
  foreach ($imgs as $im) {
    $row = ['product_id'=>$newId];
    if (db_has_col($iCols,'file_path')) $row['file_path']=$im['file_path'];
    if (db_has_col($iCols,'image_url')) $row['image_url']=$im['image_url'] ?? $im['file_path'];
    if (db_has_col($iCols,'is_primary')) $row['is_primary']=$im['is_primary']??0;
    if (db_has_col($iCols,'type')) $row['type']=$im['type']??null;
    if (db_has_col($iCols,'created_at')) $row['created_at']=$now;
    $c=[];$p=[];$v=[]; foreach($row as $k=>$val){ $c[]="`$k`"; if (is_string($val) && strpos($val,'NOW()')!==false){ $v[]="NOW()"; } else { $v[]='?'; $p[]=$val; } }
    Database::query("INSERT INTO product_images (".implode(',',$c).") VALUES (".implode(',',$v).")", $p);
  }

  Database::commit();
  header('Location:/seller/products/edit.php?id='.$newId.'&msg=copied');
  exit;
} catch (Throwable $e) {
  try { Database::rollback(); } catch (Throwable $ignore) {}
  require_once __DIR__ . '/../../includes/header.php'; ?>
  <div class="container my-5"><div class="alert alert-danger">Duplicate failed: <?= h($e->getMessage()) ?></div></div>
  <?php require_once __DIR__ . '/../../includes/footer.php'; exit;
}
