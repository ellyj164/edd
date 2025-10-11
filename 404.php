<?php
/**
 * 404 Error Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

http_response_code(404);

$page_title = '404 - Page Not Found';
includeHeader($page_title);
?>

<div class="container">
    <div class="error-page">
        <div class="error-content">
            <div class="error-icon">
                <h1 class="error-code">404</h1>
                <div class="error-graphic">📦</div>
            </div>
            
            <h2 class="error-title">Oops! We can't find that page</h2>
            <p class="error-message">The page you're looking for might have been moved, deleted, or doesn't exist.</p>
            
            <div class="error-suggestions">
                <h3>Here's what you can try:</h3>
                <ul>
                    <li>Check the URL for any typos</li>
                    <li>Go back to the <a href="/">homepage</a></li>
                    <li>Use the search bar to find what you're looking for</li>
                    <li>Browse our <a href="/category.php">categories</a></li>
                </ul>
            </div>
            
            <div class="error-actions">
                <a href="/" class="btn btn-primary">Go Home</a>
                <a href="javascript:history.back()" class="btn btn-outline">Go Back</a>
                <a href="/help.php" class="btn btn-outline">Get Help</a>
            </div>
            
            <div class="popular-links">
                <h4>Popular Links</h4>
                <div class="link-grid">
                    <a href="/deals.php">Daily Deals</a>
                    <a href="/live.php">FezaMarket Live</a>
                    <a href="/brands.php">Brand Outlet</a>
                    <a href="<?php echo getSellingUrl(); ?>">Start Selling</a>
                    <a href="/help.php">Help & Contact</a>
                    <a href="/account.php">My Account</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.error-content {
    max-width: 600px;
    text-align: center;
}

.error-icon {
    margin-bottom: 2rem;
}

.error-code {
    font-size: 6rem;
    font-weight: bold;
    color: #666;
    margin: 0;
    line-height: 1;
}

.error-graphic {
    font-size: 3rem;
    margin-top: 1rem;
}

.error-title {
    color: #333;
    margin-bottom: 1rem;
}

.error-message {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 2rem;
}

.error-suggestions {
    text-align: left;
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.error-suggestions h3 {
    margin-bottom: 1rem;
    color: #333;
}

.error-suggestions ul {
    list-style: none;
    padding: 0;
}

.error-suggestions li {
    padding: 0.25rem 0;
    position: relative;
    padding-left: 1.5rem;
}

.error-suggestions li::before {
    content: '→';
    position: absolute;
    left: 0;
    color: #0064d2;
    font-weight: bold;
}

.error-suggestions a {
    color: #0064d2;
    text-decoration: none;
}

.error-suggestions a:hover {
    text-decoration: underline;
}

.error-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 3rem;
    flex-wrap: wrap;
}

.btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    transition: background-color 0.2s;
}

.btn-primary {
    background: #0064d2;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-outline {
    background: white;
    color: #0064d2;
    border: 1px solid #0064d2;
}

.btn-outline:hover {
    background: #0064d2;
    color: white;
}

.popular-links {
    border-top: 1px solid #e5e5e5;
    padding-top: 2rem;
}

.popular-links h4 {
    margin-bottom: 1rem;
    color: #333;
}

.link-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.5rem;
    justify-items: center;
}

.link-grid a {
    color: #0064d2;
    text-decoration: none;
    padding: 0.5rem;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.link-grid a:hover {
    background: #f8f9fa;
    text-decoration: underline;
}

@media (max-width: 768px) {
    .error-code {
        font-size: 4rem;
    }
    
    .error-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
    }
    
    .link-grid {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    }
}
</style>

<?php includeFooter(); ?>