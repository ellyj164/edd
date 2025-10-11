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
$action = $_REQUEST['action'] ?? 'activate'; // activate|deactivate|archive|unarchive|hide|show
if ($id<=0){ header('Location:/seller/products/'); exit; }
$map = [
  'activate'   => ['status'=>'active',   'visibility'=>null],
  'deactivate' => ['status'=>'draft',    'visibility'=>null],
  'archive'    => ['status'=>'archived', 'visibility'=>'hidden'],
  'unarchive'  => ['status'=>'draft',    'visibility'=>'private'],
  'hide'       => ['status'=>null,       'visibility'=>'hidden'],
  'show'       => ['status'=>null,       'visibility'=>'public'],
];
$set = $map[$action] ?? $map['activate'];
$pCols = db_columns_for_table('products');
$updates=[];$params=[':id'=>$id, ':seller'=>Session::getUserId()];
if ($set['status'] && db_has_col($pCols,'status')) { $updates[]="status=:status"; $params[':status']=$set['status']; }
if ($set['visibility'] && db_has_col($pCols,'visibility')) { $updates[]="visibility=:visibility"; $params[':visibility']=$set['visibility']; }
if (!$updates){ header('Location:/seller/products/'); exit; }
Database::query("UPDATE products SET ".implode(',',$updates)." WHERE id=:id AND seller_id=:seller", $params);
header('Location:/seller/products/edit.php?id='.$id.'&msg=updated');
exit;
