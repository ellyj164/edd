<?php
/**
 * Closing Account Information Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'Closing Your Account';
includeHeader($page_title);
?>

<div class="container">
    <div class="page-header">
        <h1>Account Closure Information</h1>
        <p class="subtitle">Learn about closing your FezaMarket account</p>
    </div>

    <div class="content-wrapper">
        <!-- Information Section -->
        <div class="info-section">
            <h2>What Happens When You Close Your Account?</h2>
            
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">🔒</div>
                    <h3>Account Access</h3>
                    <p>You will no longer be able to log in or access your FezaMarket account. All personal data will be anonymized or deleted according to our privacy policy.</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">🛍️</div>
                    <h3>Order History</h3>
                    <p>Your order history will be retained for legal and tax purposes but will be anonymized. You will not be able to access this information after account closure.</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">💳</div>
                    <h3>Payment Information</h3>
                    <p>All saved payment methods and billing information will be permanently deleted from our systems.</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">📦</div>
                    <h3>Pending Orders</h3>
                    <p>Any pending orders must be completed, cancelled, or resolved before account closure. Active transactions cannot be processed after closure.</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">💰</div>
                    <h3>Wallet Balance</h3>
                    <p>If you have a remaining wallet balance, it must be withdrawn or used before closing your account. Balances cannot be recovered after closure.</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">🏆</div>
                    <h3>Loyalty Points & Benefits</h3>
                    <p>All accumulated loyalty points, rewards, and membership benefits will be forfeited upon account closure.</p>
                </div>
            </div>
        </div>

        <!-- Before You Go Section -->
        <div class="alternatives-section">
            <h2>Before You Go...</h2>
            <p>Consider these alternatives to closing your account:</p>
            
            <div class="alternative-list">
                <div class="alternative-item">
                    <h4>📧 Adjust Email Preferences</h4>
                    <p>You can control which emails you receive without closing your account. Visit your <a href="/account.php?tab=settings">account settings</a> to manage notifications.</p>
                </div>
                
                <div class="alternative-item">
                    <h4>🔐 Enhance Privacy Settings</h4>
                    <p>Review and adjust your privacy settings to control how your information is used. You have full control over your data.</p>
                </div>
                
                <div class="alternative-item">
                    <h4>⏸️ Take a Break</h4>
                    <p>You can simply stop using your account without closing it. There are no fees or charges for inactive accounts.</p>
                </div>
                
                <div class="alternative-item">
                    <h4>💬 Contact Support</h4>
                    <p>If you're having issues with your account, our support team is here to help. <a href="/contact.php">Contact us</a> before making a final decision.</p>
                </div>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="important-notes">
            <h2>Important Information</h2>
            <ul>
                <li><strong>Permanent Action:</strong> Account closure is permanent and cannot be undone. You will need to create a new account if you wish to use FezaMarket again.</li>
                <li><strong>Email Address:</strong> Your email address will be released and can be used to create a new account in the future, but your previous data will not be restored.</li>
                <li><strong>Review Period:</strong> Account closure requests are typically processed within 1-2 business days after verification.</li>
                <li><strong>Legal Obligations:</strong> We may retain certain information as required by law, even after account closure.</li>
                <li><strong>Third-Party Services:</strong> If you've used your FezaMarket account to sign in to third-party services, those connections will be terminated.</li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <?php if (Session::isLoggedIn()): ?>
                <a href="/close-account.php" class="btn btn-danger">
                    Proceed to Close Account
                </a>
                <a href="/account.php" class="btn btn-outline">
                    Return to Account
                </a>
            <?php else: ?>
                <a href="/login.php" class="btn btn-primary">
                    Login to Close Account
                </a>
                <a href="/" class="btn btn-outline">
                    Return to Home
                </a>
            <?php endif; ?>
        </div>

        <!-- Help Section -->
        <div class="help-section">
            <h3>Need Help?</h3>
            <p>If you have questions or need assistance, our support team is available:</p>
            <ul>
                <li><strong>Email:</strong> <a href="mailto:<?php echo defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : 'support@fezamarket.com'; ?>"><?php echo defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : 'support@fezamarket.com'; ?></a></li>
                <li><strong>Contact Form:</strong> <a href="/contact.php">Visit our contact page</a></li>
                <li><strong>Help Center:</strong> <a href="/help/">Browse our help articles</a></li>
            </ul>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid #e5e7eb;
}

.page-header h1 {
    color: #111827;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.page-header .subtitle {
    color: #6b7280;
    font-size: 1.125rem;
}

.content-wrapper {
    max-width: 900px;
    margin: 0 auto;
}

.info-section,
.alternatives-section,
.important-notes,
.help-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.info-section h2,
.alternatives-section h2,
.important-notes h2,
.help-section h3 {
    color: #111827;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.info-card {
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    transition: all 0.3s;
}

.info-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 6px rgba(59, 130, 246, 0.1);
}

.info-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.info-card h3 {
    color: #374151;
    font-size: 1.125rem;
    margin-bottom: 0.5rem;
}

.info-card p {
    color: #6b7280;
    font-size: 0.95rem;
    line-height: 1.6;
}

.alternative-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.alternative-item {
    padding: 1.5rem;
    background: #f9fafb;
    border-left: 4px solid #3b82f6;
    border-radius: 4px;
}

.alternative-item h4 {
    color: #111827;
    margin-bottom: 0.5rem;
    font-size: 1.125rem;
}

.alternative-item p {
    color: #4b5563;
    margin: 0;
    line-height: 1.6;
}

.alternative-item a {
    color: #3b82f6;
    text-decoration: none;
}

.alternative-item a:hover {
    text-decoration: underline;
}

.important-notes ul {
    list-style: none;
    padding: 0;
}

.important-notes li {
    padding: 1rem 0;
    border-bottom: 1px solid #f3f4f6;
    color: #4b5563;
    line-height: 1.6;
}

.important-notes li:last-child {
    border-bottom: none;
}

.important-notes li:before {
    content: "⚠️";
    margin-right: 0.75rem;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin: 3rem 0;
    flex-wrap: wrap;
}

.btn {
    padding: 0.875rem 2rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-block;
    text-align: center;
    border: none;
    cursor: pointer;
    font-size: 1rem;
}

.btn-danger {
    background: #dc2626;
    color: white;
}

.btn-danger:hover {
    background: #b91c1c;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-outline {
    background: white;
    color: #374151;
    border: 2px solid #d1d5db;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.help-section {
    background: #eff6ff;
    border: 1px solid #dbeafe;
}

.help-section ul {
    list-style: none;
    padding: 0;
    margin-top: 1rem;
}

.help-section li {
    padding: 0.5rem 0;
    color: #1e40af;
}

.help-section a {
    color: #2563eb;
    text-decoration: none;
}

.help-section a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
    
    .info-section,
    .alternatives-section,
    .important-notes,
    .help-section {
        padding: 1.5rem;
    }
}
</style>

<?php includeFooter(); ?>
