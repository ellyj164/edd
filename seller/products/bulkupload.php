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

$err=''; $ok='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (function_exists('verifyCsrfToken') && !verifyCsrfToken($_POST['csrf_token'] ?? '')) { $err='Invalid CSRF token'; }
  elseif (empty($_FILES['csv']) || $_FILES['csv']['error']!==UPLOAD_ERR_OK) { $err='No CSV uploaded'; }
  else {
    $pCols = db_columns_for_table('products');
    $handle = fopen($_FILES['csv']['tmp_name'], 'r');
    if (!$handle) { $err='Failed to read CSV'; }
    else {
      $header = fgetcsv($handle);
      if (!$header) { $err='Empty CSV'; }
      else {
        $count=0; $now=date('Y-m-d H:i:s');
        while (($row=fgetcsv($handle))!==false) {
          $data = array_combine($header, $row);
          if (!is_array($data)) continue;
          $name = trim((string)($data['name'] ?? 'Untitled'));
          $slug = trim((string)($data['slug'] ?? ''));
          if ($slug==='') $slug = slugify($name.'-'.substr((string)time(),-5));
          $price = toNumericOrNull($data['price'] ?? 0) ?? 0;
          $stock = (int)(toNumericOrNull($data['stock_quantity'] ?? 0) ?? 0);
          $currency = strtoupper(trim((string)($data['currency_code'] ?? 'USD')));
          $insert = [
            'seller_id'=>Session::getUserId(),
            'name'=>$name,'slug'=>$slug,
            'price'=>$price,'stock_quantity'=>$stock,'currency_code'=>$currency,
            'status'=>'draft','visibility'=>'private','created_at'=>$now,'updated_at'=>$now
          ];
          if (db_has_col($pCols,'image_url')) $insert['image_url']='';
          $c=[];$ph=[];$pm=[];
          foreach($insert as $k=>$v){ if(db_has_col($pCols,$k)){ $c[]="`$k`"; $ph[]=":$k"; $pm[":$k"]=$v; } }
          if ($c) { Database::query("INSERT INTO products (".implode(',',$c).") VALUES (".implode(',',$ph).")", $pm); $count++; }
        }
        fclose($handle);
        $ok = "Imported $count products as drafts.";
      }
    }
  }
}
require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="container my-4">
  <h1 class="h4">Bulk Upload (CSV)</h1>
  <?php if ($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert alert-success"><?= h($ok) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?= function_exists('csrfTokenInput')? csrfTokenInput(): '' ?>
    <div class="mb-3">
      <label class="form-label">CSV File</label>
      <input type="file" name="csv" accept=".csv,text/csv" class="form-control" required>
      <div class="form-text">Columns supported: name, slug, price, stock_quantity, currency_code</div>
    </div>
    <button class="btn btn-primary">Upload</button>
    <a href="/seller/products/" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
