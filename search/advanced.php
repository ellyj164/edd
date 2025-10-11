<?php
/**
 * Advanced Search Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';

$category = new Category();
$categories = $category->getActive();

$page_title = 'Advanced Search';
includeHeader($page_title);
?>

<div class="container">
    <div class="advanced-search-header">
        <h1>Advanced Search</h1>
        <p>Find exactly what you're looking for with detailed search options</p>
    </div>

    <form class="advanced-search-form" action="/search.php" method="GET">
        <div class="search-sections">
            <!-- Keywords Section -->
            <div class="search-section">
                <h3>Keywords</h3>
                <div class="form-group">
                    <label for="keywords">Search for items containing:</label>
                    <input type="text" id="keywords" name="q" placeholder="Enter keywords..." class="form-control">
                </div>
                <div class="form-group">
                    <label for="exclude">Exclude items containing:</label>
                    <input type="text" id="exclude" name="exclude" placeholder="Words to exclude..." class="form-control">
                </div>
            </div>

            <!-- Category Section -->
            <div class="search-section">
                <h3>Category</h3>
                <div class="form-group">
                    <label for="category">Select category:</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Price Range Section -->
            <div class="search-section">
                <h3>Price Range</h3>
                <div class="price-range-inputs">
                    <div class="form-group">
                        <label for="min_price">Minimum price:</label>
                        <div class="price-input-group">
                            <span class="currency">$</span>
                            <input type="number" id="min_price" name="min_price" min="0" step="0.01" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="max_price">Maximum price:</label>
                        <div class="price-input-group">
                            <span class="currency">$</span>
                            <input type="number" id="max_price" name="max_price" min="0" step="0.01" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item Condition Section -->
            <div class="search-section">
                <h3>Item Condition</h3>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="condition[]" value="new" checked>
                        <span class="checkmark"></span>
                        New
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="condition[]" value="used" checked>
                        <span class="checkmark"></span>
                        Used
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="condition[]" value="refurbished" checked>
                        <span class="checkmark"></span>
                        Refurbished
                    </label>
                </div>
            </div>

            <!-- Selling Format Section -->
            <div class="search-section">
                <h3>Selling Format</h3>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="format[]" value="auction" checked>
                        <span class="checkmark"></span>
                        Auction
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="format[]" value="buy_it_now" checked>
                        <span class="checkmark"></span>
                        Buy It Now
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="format[]" value="best_offer" checked>
                        <span class="checkmark"></span>
                        Best Offer
                    </label>
                </div>
            </div>

            <!-- Item Location Section -->
            <div class="search-section">
                <h3>Item Location</h3>
                <div class="form-group">
                    <label for="location">Located in:</label>
                    <select id="location" name="location" class="form-control">
                        <option value="">Worldwide</option>
                        <option value="US">United States</option>
                        <option value="CA">Canada</option>
                        <option value="UK">United Kingdom</option>
                        <option value="AU">Australia</option>
                        <option value="DE">Germany</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="distance">Within distance:</label>
                    <select id="distance" name="distance" class="form-control">
                        <option value="">Any distance</option>
                        <option value="25">25 miles</option>
                        <option value="50">50 miles</option>
                        <option value="100">100 miles</option>
                        <option value="200">200 miles</option>
                    </select>
                </div>
            </div>

            <!-- Sort Options Section -->
            <div class="search-section">
                <h3>Sort Results By</h3>
                <div class="form-group">
                    <select name="sort" class="form-control">
                        <option value="relevance">Best Match</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="newest">Newest First</option>
                        <option value="ending_soon">Ending Soonest</option>
                        <option value="distance">Distance: Nearest First</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Search Buttons -->
        <div class="search-actions">
            <button type="submit" class="btn btn-primary btn-large">Search</button>
            <button type="reset" class="btn btn-secondary">Clear All</button>
            <a href="/search.php" class="btn btn-link">Basic Search</a>
        </div>
    </form>

    <!-- Search Tips -->
    <div class="search-tips">
        <h3>Search Tips</h3>
        <div class="tips-grid">
            <div class="tip">
                <strong>Use quotes</strong> for exact phrases: "vintage camera"
            </div>
            <div class="tip">
                <strong>Use - to exclude</strong> words: camera -digital
            </div>
            <div class="tip">
                <strong>Use * for wildcards:</strong> cam* finds camera, camcorder
            </div>
            <div class="tip">
                <strong>Use OR</strong> to find either term: (Canon OR Nikon)
            </div>
        </div>
    </div>
</div>

<style>
.advanced-search-header {
    text-align: center;
    padding: 2rem 0;
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 3rem;
}

.search-sections {
    display: grid;
    gap: 2rem;
    margin-bottom: 3rem;
}

.search-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.search-section h3 {
    margin-bottom: 1rem;
    color: #333;
    font-size: 1.1rem;
}

.price-range-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.price-input-group {
    display: flex;
    align-items: center;
}

.currency {
    background: #e9ecef;
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-right: 0;
    border-radius: 0.25rem 0 0 0.25rem;
}

.price-input-group .form-control {
    border-radius: 0 0.25rem 0.25rem 0;
}

.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 0.5rem;
}

.search-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    padding: 2rem 0;
    border-top: 1px solid #e9ecef;
}

.search-tips {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #e9ecef;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.tip {
    background: #fff3cd;
    padding: 1rem;
    border-radius: 0.25rem;
    border: 1px solid #ffeaa7;
}

@media (max-width: 768px) {
    .price-range-inputs {
        grid-template-columns: 1fr;
    }
    
    .search-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php includeFooter(); ?>