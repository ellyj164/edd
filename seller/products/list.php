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

header('Location: /seller/products/index.php'); exit;
