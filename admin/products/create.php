<?php
/**
 * Admin: Create Product
 * E-Commerce Platform
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/database.php';

// Security: Ensure user is an admin
if (!hasRole('admin')) {
    redirect('/403.php');
}

$productModel = new Product();
$categoryModel = new Category();
$vendorModel = new Vendor();

$categories = $categoryModel->getActive();
$vendors = $vendorModel->findAll(); // Assuming a simple findAll method exists

$errors = [];
$name = '';
$description = '';
$price = '';
$stock_quantity = '';
$category_id = '';
$vendor_id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!verifyCsrfToken()) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Sanitize and validate inputs
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $vendor_id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);

        if (empty($name)) $errors[] = 'Product name is required.';
        if ($price === false || $price < 0) $errors[] = 'A valid price is required.';
        if ($stock_quantity === false || $stock_quantity < 0) $errors[] = 'A valid stock quantity is required.';
        if (empty($category_id)) $errors[] = 'Please select a category.';
        
        // Handle file upload
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image_url = 'products/' . $fileName; // Store relative path
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }

        if (empty($errors)) {
            $slug = slugify($name);
            $productData = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'price' => $price,
                'stock_quantity' => $stock_quantity,
                'category_id' => $category_id,
                'vendor_id' => $vendor_id ?: null,
                'status' => 'active',
            ];

            $productId = $productModel->create($productData);

            if ($productId) {
                // If an image was uploaded, associate it with the new product
                if ($image_url) {
                    $productModel->addImage($productId, $image_url, $name, true);
                }
                
                Session::setFlash('success', 'Product created successfully.');
                redirect('/admin/products');
            } else {
                $errors[] = 'Failed to create product.';
            }
        }
    }
}

$page_title = "Create New Product";
includeHeader($page_title, 'admin-page');
?>

<div class="container admin-container">
    <h1><i class="fas fa-plus-circle"></i> Create New Product</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="/admin/products/create.php" method="post" enctype="multipart/form-data">
                <?php echo csrfTokenInput(); ?>

                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5"><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="price">Price</label>
                        <input type="number" id="price" name="price" class="form-control" step="0.01" value="<?php echo htmlspecialchars($price); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="stock_quantity">Stock Quantity</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" value="<?php echo htmlspecialchars($stock_quantity); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="vendor_id">Vendor (Optional)</label>
                        <select id="vendor_id" name="vendor_id" class="form-control">
                            <option value="">-- No Vendor --</option>
                            <?php foreach ($vendors as $vendor): ?>
                                <option value="<?php echo $vendor['id']; ?>" <?php echo ($vendor_id == $vendor['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vendor['business_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" class="form-control-file">
                </div>

                <button type="submit" class="btn btn-primary">Create Product</button>
                <a href="/admin/products" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php includeFooter(); ?>