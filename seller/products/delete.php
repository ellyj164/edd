<?php
declare(strict_types=1);
/**
 * Seller Portal - Delete Products (safe)
 * - CSRF protected; ownership checks
 * - Soft delete (status=archived) by default; hard delete if &hard=1
 * - Cleans related tables; handles image_url constraints
 */
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!class_exists('Session') || !Session::isLoggedIn()) { header('Location: /login.php'); exit; }
if (!function_exists('h')) { function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }

function db_columns_for_table(string $table): array{
  static $c=[]; if(isset($c[$table])) return $c[$table];
  try{ $r=Database::query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",[$table])->fetchAll(PDO::FETCH_COLUMN);
       return $c[$table]=array_flip($r?:[]);
  }catch(Throwable $e){ return $c[$table]=[]; }
}
function db_has_col(array $cols, string $n): bool { return isset($cols[$n]); }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'POST' && function_exists('verifyCsrfToken')) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(419);
        die('Invalid CSRF token');
    }
}

$userId = Session::getUserId();
$ids = [];

// Accept ids from GET (?id=) or POST (ids[]=)
if (isset($_GET['id'])) $ids[] = (int)$_GET['id'];
if (!empty($_POST['ids']) && is_array($_POST['ids'])) {
    foreach ($_POST['ids'] as $i) { $i=(int)$i; if ($i>0) $ids[]=$i; }
}
$ids = array_values(array_unique(array_filter($ids, fn($v)=>$v>0)));

$hardDelete = (isset($_GET['hard']) && $_GET['hard']=='1') || (isset($_POST['hard']) && $_POST['hard']=='1');

if (!$ids) {
    // Render simple confirmation UI listing recent products for reference
    require_once __DIR__ . '/../../includes/header.php';
    ?>
    <div class="container my-5">
      <h1 class="h4 mb-3">Delete Products</h1>
      <div class="alert alert-info">No product IDs were provided.</div>
      <a href="/seller/products/" class="btn btn-secondary">Back to Products</a>
    </div>
    <?php
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

try {
    Database::beginTransaction();

    // Verify ownership & clamp to owned ids
    $in = implode(',', array_fill(0, count($ids), '?'));
    $owned = Database::query("SELECT id FROM products WHERE seller_id=? AND id IN ($in)", array_merge([$userId], $ids))->fetchAll(PDO::FETCH_COLUMN) ?: [];
    if (!$owned) throw new RuntimeException('No matching products found for your account.');

    $pCols = db_columns_for_table('products');
    $imgCols = db_columns_for_table('product_images');
    $relCols = db_columns_for_table('product_related');
    $pivotCols = db_columns_for_table('product_tag');

    if (!$hardDelete) {
        // Soft delete: set status=archived, visibility=hidden
        if (db_has_col($pCols,'status')) {
            Database::query("UPDATE products SET status='archived' WHERE seller_id=? AND id IN ($in)", array_merge([$userId], $owned));
        }
        if (db_has_col($pCols,'visibility')) {
            Database::query("UPDATE products SET visibility='hidden' WHERE seller_id=? AND id IN ($in)", array_merge([$userId], $owned));
        }
        Database::commit();
        header('Location: /seller/products/?msg=archived');
        exit;
    }

    // Hard delete: remove related rows first to satisfy FKs
    if ($pivotCols) {
        Database::query("DELETE FROM product_tag WHERE product_id IN ($in)", $owned);
    }
    if ($relCols) {
        Database::query("DELETE FROM product_related WHERE product_id IN ($in) OR related_product_id IN ($in)", $owned + $owned);
    }
    if ($imgCols) {
        // Optionally unlink physical files here if desired
        $files = Database::query("SELECT file_path FROM product_images WHERE product_id IN ($in)", $owned)->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($files as $fp) {
            $path = __DIR__ . '/../../' . ltrim($fp,'/');
            if (is_file($path)) { @unlink($path); }
        }
        Database::query("DELETE FROM product_images WHERE product_id IN ($in)", $owned);
    }

    // Finally delete products
    Database::query("DELETE FROM products WHERE seller_id=? AND id IN ($in)", array_merge([$userId], $owned));

    Database::commit();
    header('Location: /seller/products/?msg=deleted');
    exit;
} catch (Throwable $e) {
    try { Database::rollback(); } catch (Throwable $ignore) {}
    require_once __DIR__ . '/../../includes/header.php';
    ?>
    <div class="container my-5">
      <div class="alert alert-danger">Delete failed: <?= h($e->getMessage()) ?></div>
      <a class="btn btn-secondary" href="/seller/products/">Back</a>
    </div>
    <?php
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}
