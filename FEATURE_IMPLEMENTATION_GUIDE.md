# E-Commerce Platform Feature Implementation - October 2025

## Overview
This document describes the features implemented to enhance the e-commerce platform with currency management, product management improvements, and seller experience enhancements.

## Features Implemented

### 1. Automatic Currency Switching for Rwanda 🇷🇼

**Requirement:** Users from Rwanda must checkout in RWF (Rwandan Francs) only, with automatic currency conversion.

**Implementation:**
- Modified `api/create-payment-intent.php` to enforce RWF for Rwanda billing addresses
- Updated `checkout.php` JavaScript to detect and display selected currency
- Added visual indicators for Rwanda users showing currency conversion notice
- Prevented non-Rwanda users from selecting RWF currency

**Files Changed:**
- `api/create-payment-intent.php`
- `checkout.php`

**How it Works:**
1. When user selects Rwanda as billing country, currency is automatically set to RWF
2. Server-side validation ensures Rwanda orders can only use RWF
3. Exchange rates are applied using the existing CurrencyService
4. UI displays special message: "Payment will be processed in RWF (FRw). Exchange rate will be applied automatically."

**Testing:**
```javascript
// On checkout page
1. Select "Rwanda" as country
2. Verify currency note shows RWF
3. Complete checkout - should process in RWF
4. Select another country - should not allow RWF selection
```

---

### 2. Product Update Error Fix 🔧

**Issue:** Sellers encountered "No updatable columns found" error when updating products.

**Root Cause:** The `db_columns_for_table()` function tried SQLite PRAGMA first, which threw an exception on MySQL/MariaDB databases, preventing the MySQL fallback from executing.

**Solution:** Wrapped SQLite PRAGMA in a try-catch block to ensure MySQL fallback always executes.

**Files Changed:**
- `seller/products/edit.php`

**Code Change:**
```php
// Before: SQLite error prevented MySQL fallback
$r=Database::query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);

// After: Catches SQLite error, allows MySQL fallback
try {
    $r=Database::query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
    if($r){ /* handle SQLite */ }
} catch(Throwable $sqliteErr) {
    // SQLite failed, try MySQL
}
// MySQL fallback now executes properly
```

---

### 3. Seller Dashboard Product View Links 🔗

**Issue:** Products in "Top Performing Products" section had no clickable links.

**Solution:** Added clickable links to product names that open product view in new tab.

**Files Changed:**
- `seller/dashboard.php`

**Implementation:**
```php
<a href="/product.php?id=<?php echo $product['id']; ?>" target="_blank" style="color: inherit; text-decoration: none;">
    <?php echo htmlspecialchars($product['name']); ?>
</a>
```

---

### 4. Downloadable Product Options 📥

**Requirement:** Sellers need two options for digital products: file upload or external link.

**Implementation:**
- Added radio buttons to choose delivery method (file upload or external URL)
- File upload stores files securely in `uploads/digital_products/`
- External URL field for linking to hosted files
- JavaScript toggles between options

**Files Changed:**
- `seller/products/add.php`

**Features:**
- **File Upload Option:**
  - Accepts any file type
  - Generates secure filename: `{SKU}_{timestamp}.{extension}`
  - Stores in `uploads/digital_products/` directory
  - Path saved to `products.digital_file_path`

- **External URL Option:**
  - Text input for direct file URLs
  - Saved to `products.digital_url`
  - Allows linking to cloud storage, CDN, etc.

**UI Elements:**
```html
<input type="radio" name="digital_delivery_method" value="file" checked>
<input type="radio" name="digital_delivery_method" value="url">
```

---

### 5. Automatic SKU Generation 🏷️

**Requirement:** Generate unique SKUs automatically when not provided by seller.

**Implementation:**
- Enhanced SKU format: `V{vendorId}-{initials}-{random}`
- Database uniqueness check with retry logic
- Uses cryptographically secure random bytes

**Files Changed:**
- `seller/products/add.php`

**SKU Format:**
- `V`: Vendor prefix
- `{vendorId}`: Unique vendor identifier
- `{initials}`: First 4 letters of product name (alphanumeric only)
- `{random}`: 6-character random hex string

**Example:**
- Product: "Wireless Mouse"
- Vendor ID: 42
- Generated SKU: `V42-WIRE-A3F9D2`

**Code:**
```php
$initials = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 4));
$random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
$sku = "V{$vendorId}-{$initials}-{$random}";

// Check uniqueness, retry if exists (max 5 attempts)
```

---

### 6. "Other" Option for Brands 🏢

**Requirement:** Allow sellers to create new brands from product add/edit pages.

**Implementation:**
- Added "Other (Enter new brand)" option to brand dropdown
- Dynamic text field appears when "Other" is selected
- Server-side brand creation with duplicate checking
- Works on both add and edit product pages

**Files Changed:**
- `seller/products/add.php`
- `seller/products/edit.php`

**Features:**
- **UI Enhancement:**
  - Brand dropdown includes "Other" option at end
  - Text field appears/hides dynamically via JavaScript
  - Field becomes required when "Other" is selected

- **Server-Side Logic:**
  - Generates URL-friendly slug from brand name
  - Checks if brand already exists (by slug)
  - Creates new brand if doesn't exist
  - Associates new brand with product

**JavaScript:**
```javascript
function toggleOtherBrandField(value) {
    if (value === 'other') {
        newBrandField.style.display = 'block';
        document.getElementById('new_brand_name').setAttribute('required', 'required');
    } else {
        newBrandField.style.display = 'none';
        document.getElementById('new_brand_name').removeAttribute('required');
    }
}
```

**PHP:**
```php
if ($brand_id === 'other') {
    $newBrandName = trim($_POST['new_brand_name'] ?? '');
    $brandSlug = slugify($newBrandName);
    
    // Check if exists
    $existingBrand = Database::query("SELECT id FROM brands WHERE slug = ?", [$brandSlug])->fetch();
    if ($existingBrand) {
        $brand_id = $existingBrand['id'];
    } else {
        // Create new brand
        Database::query(
            "INSERT INTO brands (name, slug, description, is_active, created_at, updated_at) 
             VALUES (?, ?, ?, 1, NOW(), NOW())",
            [$newBrandName, $brandSlug, "Added by seller"]
        );
        $brand_id = Database::lastInsertId();
    }
}
```

---

## Database Schema

All required columns already exist in the database. No migrations needed.

**Columns Used:**
- `products.digital_url` - External URL for digital files
- `products.digital_file_path` - Server path for uploaded files
- `products.download_limit` - Download count limit
- `products.expiry_days` - Link expiration days
- `products.sku` - Unique product SKU
- `products.brand_id` - Foreign key to brands
- `brands.*` - All brand columns

See `migrations/20251011_ecommerce_enhancements.sql` for details.

---

## Post-Deployment Tasks

### 1. Create Digital Products Directory
```bash
mkdir -p /path/to/edd/uploads/digital_products
chmod 755 /path/to/edd/uploads/digital_products
```

### 2. Test Currency Switching
- Go to checkout
- Select Rwanda as country
- Verify RWF is displayed
- Complete test purchase

### 3. Test Product Updates
- Edit an existing product
- Save changes
- Verify no "No updatable columns" error

### 4. Test Brand Creation
- Add new product
- Select "Other" in brand dropdown
- Enter new brand name
- Save product
- Verify brand is created and associated

### 5. Test Digital Product Upload
- Add new product
- Check "Digital/Downloadable"
- Select "File Upload"
- Upload a file
- Save and verify file is stored

---

## Testing Checklist

- [ ] Rwanda users can only checkout in RWF
- [ ] Non-Rwanda users cannot select RWF
- [ ] Product updates work without errors
- [ ] Dashboard product links work
- [ ] Digital file upload works
- [ ] Digital external URL works
- [ ] SKU is auto-generated when empty
- [ ] SKUs are unique
- [ ] Brand "Other" option appears
- [ ] New brands are created successfully
- [ ] New brands are deduplicated

---

## Backward Compatibility

All changes are backward compatible:
- Existing products continue to work
- Existing brands are unaffected
- SKU generation only runs when SKU is empty
- Currency enforcement only applies to new checkouts
- All existing checkout flows continue to work

---

## Known Limitations

1. **Currency Rates:** Exchange rates use static fallback values. For production, integrate with a live currency API.

2. **File Upload Security:** Basic validation is in place. For production, add:
   - File type whitelist
   - Virus scanning
   - File size limits
   - Content-type validation

3. **SKU Format:** Current format is vendor-centric. May need adjustment for multi-vendor platforms.

---

## Support

For issues or questions:
1. Check error logs in `error_log` or system logs
2. Verify database columns exist (see migration file)
3. Check file permissions on `uploads/digital_products/`
4. Review browser console for JavaScript errors

---

## Files Modified

```
api/create-payment-intent.php      - Currency enforcement for Rwanda
checkout.php                        - Currency UI and detection
seller/dashboard.php                - Product view links
seller/products/add.php             - SKU generation, brand creation, digital options
seller/products/edit.php            - Column detection fix, brand creation
migrations/20251011_ecommerce_enhancements.sql - Documentation migration
```

## Version History

**v1.0 - October 11, 2025**
- Initial implementation of all 6 features
- Currency switching for Rwanda
- Product update bug fix
- Dashboard link fix
- Digital product options
- Automatic SKU generation
- Brand "Other" option
