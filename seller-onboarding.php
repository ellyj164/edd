<?php
/**
 * Seller Onboarding Completion
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Require seller login
Session::requireLogin();

$vendor = new Vendor();
$userVendor = $vendor->findByUserId(Session::getUserId());

// If no vendor record exists, redirect to registration
if (!$userVendor) {
    redirect(sellerUrl('register'));
}

// If already approved, redirect to seller center
if ($userVendor['status'] === 'approved') {
    redirect('/seller-center.php');
}

$page_title = 'Welcome to FezaMarket Sellers';
includeHeader($page_title);
?>

<div class="container">
    <div class="onboarding-completion">
        <div class="completion-content">
            <div class="completion-icon">
                <div class="check-circle">✓</div>
            </div>
            
            <h1>Welcome to FezaMarket!</h1>
            <p class="completion-message">Your seller account has been created successfully.</p>
            
            <div class="status-info">
                <div class="status-card">
                    <h3>Account Status: <?php echo ucfirst($userVendor['status']); ?></h3>
                    <?php if ($userVendor['status'] === 'pending'): ?>
                        <p>Your account is under review. We'll notify you via email once it's approved (usually within 24-48 hours).</p>
                        
                        <div class="next-steps">
                            <h4>What happens next?</h4>
                            <ul>
                                <li>Our team reviews your seller application</li>
                                <li>You'll receive an email confirmation once approved</li>
                                <li>Start adding your first products to your store</li>
                                <li>Begin selling to millions of FezaMarket customers</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="onboarding-actions">
                <a href="/seller-center.php" class="btn btn-primary btn-large">Go to Seller Center</a>
                <a href="/help/selling" class="btn btn-outline">Learn About Selling</a>
            </div>
            
            <div class="quick-links">
                <h4>Quick Start Resources</h4>
                <div class="resource-grid">
                    <a href="/help/selling/getting-started" class="resource-link">
                        <div class="resource-icon">📚</div>
                        <div class="resource-content">
                            <h5>Getting Started Guide</h5>
                            <p>Learn the basics of selling on FezaMarket</p>
                        </div>
                    </a>
                    <a href="/help/selling/listing-tips" class="resource-link">
                        <div class="resource-icon">📝</div>
                        <div class="resource-content">
                            <h5>Listing Best Practices</h5>
                            <p>Create compelling product listings</p>
                        </div>
                    </a>
                    <a href="/help/selling/fees" class="resource-link">
                        <div class="resource-icon">💰</div>
                        <div class="resource-content">
                            <h5>Fees & Pricing</h5>
                            <p>Understand FezaMarket selling fees</p>
                        </div>
                    </a>
                    <a href="/help/selling/policies" class="resource-link">
                        <div class="resource-icon">📋</div>
                        <div class="resource-content">
                            <h5>Seller Policies</h5>
                            <p>Review important selling policies</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.onboarding-completion {
    max-width: 800px;
    margin: 3rem auto;
    padding: 2rem;
    text-align: center;
}

.completion-icon {
    margin-bottom: 2rem;
}

.check-circle {
    width: 80px;
    height: 80px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    margin: 0 auto;
}

.onboarding-completion h1 {
    color: #333;
    margin-bottom: 0.5rem;
}

.completion-message {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 2rem;
}

.status-info {
    margin: 2rem 0;
}

.status-card {
    background: #f8f9fa;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 2rem;
    text-align: left;
}

.status-card h3 {
    color: #333;
    margin-bottom: 1rem;
}

.next-steps {
    margin-top: 1.5rem;
}

.next-steps h4 {
    color: #333;
    margin-bottom: 0.5rem;
}

.next-steps ul {
    list-style: none;
    padding: 0;
}

.next-steps li {
    padding: 0.5rem 0;
    position: relative;
    padding-left: 1.5rem;
}

.next-steps li::before {
    content: '→';
    position: absolute;
    left: 0;
    color: #0064d2;
    font-weight: bold;
}

.onboarding-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin: 2rem 0;
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

.btn-large {
    padding: 1rem 2.5rem;
    font-size: 1.1rem;
}

.quick-links {
    margin-top: 3rem;
    text-align: left;
}

.quick-links h4 {
    text-align: center;
    margin-bottom: 1.5rem;
    color: #333;
}

.resource-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.resource-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.resource-link:hover {
    border-color: #0064d2;
    box-shadow: 0 2px 4px rgba(0, 100, 210, 0.1);
}

.resource-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.resource-content h5 {
    margin: 0 0 0.25rem 0;
    color: #333;
}

.resource-content p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .onboarding-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
    }
    
    .resource-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php includeFooter(); ?>