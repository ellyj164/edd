<?php
/**
 * Saved Items Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Require user to be logged in
Session::requireLogin();

$user = new User();
$product = new Product();
$currentUser = $user->find(Session::getUserId());

// Get saved items (wishlist)
// For now, we'll show a placeholder since wishlist functionality needs to be implemented

$page_title = 'Saved Items';
includeHeader($page_title);
?>

<div class="container">
    <div class="saved-header">
        <h1>My Saved Items</h1>
        <p>Keep track of items you're interested in and never lose sight of them.</p>
    </div>

    <div class="saved-content">
        <div class="saved-categories">
            <div class="category-tabs">
                <button class="tab-btn active" data-category="all">All Items</button>
                <button class="tab-btn" data-category="watching">Watching</button>
                <button class="tab-btn" data-category="bidding">Bidding</button>
                <button class="tab-btn" data-category="won">Won</button>
            </div>
        </div>

        <div class="saved-items-grid">
            <!-- Placeholder for saved items -->
            <div class="no-saved-items">
                <div class="empty-state">
                    <div class="empty-icon">💾</div>
                    <h3>No saved items yet</h3>
                    <p>Items you save will appear here. Start browsing and save items you're interested in!</p>
                    <a href="/" class="btn btn-primary">Start Shopping</a>
                </div>
            </div>
        </div>
    </div>

    <div class="saved-features">
        <div class="feature-grid">
            <div class="feature-item">
                <h3>🔍 Watch Items</h3>
                <p>Keep an eye on items you're considering buying</p>
            </div>
            <div class="feature-item">
                <h3>📱 Get Notifications</h3>
                <p>Receive alerts when prices drop or auctions are ending</p>
            </div>
            <div class="feature-item">
                <h3>📊 Track Prices</h3>
                <p>Monitor price changes on your saved items</p>
            </div>
        </div>
    </div>
</div>

<style>
.saved-header {
    text-align: center;
    padding: 2rem 0;
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 2rem;
}

.saved-categories {
    margin-bottom: 2rem;
}

.category-tabs {
    display: flex;
    gap: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.tab-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    background: none;
    color: #6c757d;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.tab-btn.active {
    color: #0066cc;
    border-bottom-color: #0066cc;
}

.no-saved-items {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state .empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
    padding-top: 3rem;
    border-top: 1px solid #e9ecef;
}

.feature-item {
    text-align: center;
}

.feature-item h3 {
    margin-bottom: 0.5rem;
    color: #333;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabBtns = document.querySelectorAll('.tab-btn');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            tabBtns.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const category = this.dataset.category;
            // Filter saved items by category (functionality to be implemented)
        });
    });
});
</script>

<?php includeFooter(); ?>