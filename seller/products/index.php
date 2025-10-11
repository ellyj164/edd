<?php
/**
 * Seller Portal - Products Index (rewritten, PHP 8.1+ safe)
 * - Null-safe number formatting (no deprecated number_format(NULL))
 * - Search, filters (category, brand, status), sorting, pagination
 * - Uses shared header/footer and Database helper
 * - Minimal assumptions about schema; adapts to missing columns
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../auth.php'; // Seller authentication guard
// functions.php may already be auto-loaded in init.php; h()/csrf not required here.
if (!function_exists('h')) {
    function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('nf')) {
    /** Null-safe number format */
    function nf($value, int $decimals = 0, string $dec_point='.', string $thousands_sep=','): string {
        if ($value === '' || $value === null) $value = 0;
        if (!is_numeric($value)) $value = 0;
        return number_format((float)$value, $decimals, $dec_point, $thousands_sep);
    }
}
if (!function_exists('moneyf')) {
    /** Currency formatter (null-safe) */
    function moneyf($value, string $currency = '$', int $decimals = 2): string {
        return $currency . nf($value, $decimals);
    }
}

/* ---------------------------- Inputs ------------------------------------- */
$pageTitle = 'My Products';

$search    = trim((string)($_GET['search'] ?? ''));
$category  = trim((string)($_GET['category'] ?? ''));
$brand     = trim((string)($_GET['brand'] ?? ''));
$status    = trim((string)($_GET['status'] ?? ''));
$perPage   = max(1, min(100, (int)($_GET['perPage'] ?? 25)));
$page      = max(1, (int)($_GET['page'] ?? 1));
$offset    = ($page - 1) * $perPage;

$allowedSort = ['created_at','name','price','stock_quantity'];
$sortBy      = in_array($_GET['sort'] ?? 'created_at', $allowedSort, true) ? ($_GET['sort'] ?? 'created_at') : 'created_at';
$sortDir     = (($_GET['dir'] ?? 'desc') === 'asc') ? 'asc' : 'desc';

/* ---------------------------- Filters SQL -------------------------------- */
// Get vendor ID for the current user
$vendor = new Vendor();
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo) {
    // If no vendor account, show empty results
    $where = ["p.vendor_id = ?"];
    $params = [-1]; // Non-existent vendor ID
} else {
    $where = ["p.vendor_id = ?"];
    $params = [$vendorInfo['id']];
}

if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.slug LIKE ?)";
    $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%";
}
if ($category !== '' && ctype_digit($category)) {
    $where[] = "p.category_id = ?"; $params[] = (int)$category;
}
if ($brand !== '' && ctype_digit($brand)) {
    $where[] = "p.brand_id = ?"; $params[] = (int)$brand;
}
if ($status !== '' && in_array($status, ['draft','active','archived'], true)) {
    $where[] = "p.status = ?"; $params[] = $status;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ---------------------------- Lookups ------------------------------------ */
$categories = [];
$brands     = [];
try {
    $categories = Database::query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $brands     = Database::query("SELECT id,name FROM brands WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('lookup failed: '.$e->getMessage());
}

/* ---------------------------- Counts & List ------------------------------ */
try {
    $totalProducts = (int) Database::query("SELECT COUNT(*) FROM products p $whereSql", $params)->fetchColumn();
    $totalPages = (int) ceil(($totalProducts ?: 0) / $perPage);

    // Build select with COALESCE to reduce nulls
    $sql = "
        SELECT p.id, p.name, p.sku, p.slug,
               COALESCE(p.price,0) AS price,
               COALESCE(p.compare_price,0) AS compare_price,
               COALESCE(p.stock_quantity,0) AS stock_quantity,
               COALESCE(p.status,'draft') AS status,
               COALESCE(p.visibility,'public') AS visibility,
               p.created_at, p.updated_at,
               c.name AS category_name,
               b.name AS brand_name,
               (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) AS image_count
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN brands b ON b.id = p.brand_id
        $whereSql
        ORDER BY p.$sortBy $sortDir
        LIMIT $perPage OFFSET $offset";
    $products = Database::query($sql, $params)->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('list failed: '.$e->getMessage());
    $products = [];
    $totalProducts = 0;
    $totalPages = 1;
}

/* ---------------------------- Render ------------------------------------- */
$page_title = 'My Products';
$breadcrumb_items = [
    ['title' => 'Products']
];
includeHeader($page_title);
?>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= h($pageTitle) ?> <span class="text-muted">(<?= nf($totalProducts) ?>)</span></h1>
    <a href="/seller/products/add.php" class="btn btn-primary">Add Product</a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <form method="get" class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Search</label>
          <input type="text" name="search" class="form-control" value="<?= h($search) ?>" placeholder="Name, SKU, slug">
        </div>
        <div class="col-md-3">
          <label class="form-label">Category</label>
          <select name="category" class="form-select">
            <option value="">All</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= ($category==(string)$cat['id']?'selected':'') ?>><?= h($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Brand</label>
          <select name="brand" class="form-select">
            <option value="">All</option>
            <?php foreach ($brands as $b): ?>
              <option value="<?= (int)$b['id'] ?>" <?= ($brand==(string)$b['id']?'selected':'') ?>><?= h($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="">All</option>
            <option value="draft" <?= $status==='draft'?'selected':''; ?>>Draft</option>
            <option value="active" <?= $status==='active'?'selected':''; ?>>Active</option>
            <option value="archived" <?= $status==='archived'?'selected':''; ?>>Archived</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Per page</label>
          <select name="perPage" class="form-select">
            <?php foreach ([10,25,50,100] as $pp): ?>
              <option value="<?= $pp ?>" <?= $perPage===$pp?'selected':''; ?>><?= $pp ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button class="btn btn-outline-secondary w-100">Apply</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name / SKU</th>
            <th>Category / Brand</th>
            <th class="text-end">Price</th>
            <th class="text-end">Compare</th>
            <th class="text-end">Stock</th>
            <th class="text-center">Images</th>
            <th>Status</th>
            <th>Created</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$products): ?>
            <tr>
              <td colspan="10" class="text-center py-5">
                <div class="text-muted">
                  <h5>No products found</h5>
                  <?php if (!$vendorInfo): ?>
                    <p>You need to complete your vendor registration before you can add products.</p>
                    <a href="/seller-register.php" class="btn btn-primary">Complete Vendor Registration</a>
                  <?php elseif ($search || $category || $brand || $status): ?>
                    <p>Try adjusting your search filters to find more products.</p>
                    <a href="/seller/products/" class="btn btn-outline-primary">Clear Filters</a>
                  <?php else: ?>
                    <p>Get started by adding your first product to start selling!</p>
                    <a href="/seller/products/add.php" class="btn btn-primary">Add Your First Product</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php else: foreach ($products as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td>
                <div class="fw-semibold"><?= h($p['name']) ?></div>
                <div class="text-muted small"><?= h($p['sku'] ?: '—') ?></div>
              </td>
              <td>
                <div><?= h($p['category_name'] ?: '—') ?></div>
                <div class="text-muted small"><?= h($p['brand_name'] ?: '') ?></div>
              </td>
              <td class="text-end"><strong><?= moneyf($p['price']) ?></strong></td>
              <td class="text-end">
                <?php if ((float)$p['compare_price'] > 0): ?>
                  <span class="text-muted text-decoration-line-through"><?= moneyf($p['compare_price']) ?></span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="text-end"><?= nf($p['stock_quantity']) ?></td>
              <td class="text-center"><?= nf($p['image_count']) ?></td>
              <td>
                <?php if ($p['status']==='active'): ?>
                  <span class="badge bg-success">Active</span>
                <?php elseif ($p['status']==='archived'): ?>
                  <span class="badge bg-secondary">Archived</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">Draft</span>
                <?php endif; ?>
              </td>
              <td class="text-muted small"><?= h($p['created_at'] ?? '') ?></td>
              <td class="text-end">
                <a href="/seller/products/edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                <a href="/product/<?= h($p['slug']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank">View</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="text-muted small">
        Showing <?= nf(min($offset + 1, max($totalProducts,1))) ?> to <?= nf(min($offset + $perPage, $totalProducts)) ?>
        of <?= nf($totalProducts) ?> products
      </div>
      <nav>
        <ul class="pagination mb-0">
          <?php
            $qs = $_GET;
            $makeLink = function($p) use ($qs) {
                $qs['page'] = $p;
                return '?' . http_build_query($qs);
            };
            $disablePrev = $page <= 1;
            $disableNext = $page >= $totalPages;
          ?>
          <li class="page-item <?= $disablePrev?'disabled':''; ?>">
            <a class="page-link" href="<?= $disablePrev?'#':$makeLink($page-1) ?>" tabindex="-1">Prev</a>
          </li>
          <?php
            $start = max(1, $page-2);
            $end   = min($totalPages, $page+2);
            for ($i=$start; $i<=$end; $i++):
          ?>
            <li class="page-item <?= $i===$page?'active':''; ?>">
              <a class="page-link" href="<?= $makeLink($i) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $disableNext?'disabled':''; ?>">
            <a class="page-link" href="<?= $disableNext?'#':$makeLink($page+1) ?>">Next</a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>
<?php includeFooter(); ?>
