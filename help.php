<?php
/**
 * Help Center
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'Help Center - FezaMarket Support';
includeHeader($page_title);
?>

<div class="container">
    <!-- Help Center Header -->
    <div class="help-header">
        <h1>Help Center</h1>
        <p>Find answers to your questions and get support</p>
        
        <!-- Search Help -->
        <div class="help-search">
            <form class="search-form" id="helpSearchForm">
                <div class="search-input-group">
                    <input type="text" placeholder="What can we help you with?" class="search-input" id="helpSearchInput">
                    <button type="submit" class="search-btn">Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popular Topics -->
    <section class="popular-topics">
        <h2>Popular Topics</h2>
        <div class="topics-grid">
            <a href="/help/orders.php" class="topic-card">
                <div class="topic-icon">📦</div>
                <h3>Orders & Shipping</h3>
                <p>Track orders, shipping info, delivery issues</p>
            </a>
            <a href="/help/returns.php" class="topic-card">
                <div class="topic-icon">↩️</div>
                <h3>Returns & Refunds</h3>
                <p>Return policy, refund process, exchanges</p>
            </a>
            <a href="/help/account.php" class="topic-card">
                <div class="topic-icon">👤</div>
                <h3>Account & Login</h3>
                <p>Account settings, password reset, security</p>
            </a>
            <a href="/help/payment.php" class="topic-card">
                <div class="topic-icon">💳</div>
                <h3>Payment & Billing</h3>
                <p>Payment methods, billing issues, receipts</p>
            </a>
            <a href="/help/selling.php" class="topic-card">
                <div class="topic-icon">🏪</div>
                <h3>Selling on FezaMarket</h3>
                <p>Seller registration, listing products, fees</p>
            </a>
            <a href="/help/safety.php" class="topic-card">
                <div class="topic-icon">🛡️</div>
                <h3>Safety & Security</h3>
                <p>Safe shopping, report issues, fraud protection</p>
            </a>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="actions-grid">
            <div class="action-card">
                <h3>Track Your Order</h3>
                <p>Enter your order number to see shipping status</p>
                <div class="action-form">
                    <input type="text" placeholder="Order number" class="form-input">
                    <button class="btn">Track Order</button>
                </div>
            </div>
            <div class="action-card">
                <h3>Contact Seller</h3>
                <p>Need to reach a seller about your order?</p>
                <a href="/help/contact-seller.php" class="btn btn-outline">Find Seller Contact</a>
            </div>
            <div class="action-card">
                <h3>Report an Issue</h3>
                <p>Something not right? Let us know</p>
                <a href="/help/report.php" class="btn btn-outline">Report Issue</a>
            </div>
        </div>
    </section>

    <!-- FAQs -->
    <section class="faqs-section">
        <h2>Frequently Asked Questions</h2>
        <div class="faqs-grid">
            <div class="faq-category">
                <h3>Buying</h3>
                <div class="faq-list">
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do I place an order?
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            To place an order, browse products, add items to your cart, and proceed to checkout. You'll need to provide shipping and payment information.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How can I track my order?
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            You can track your order in "My Orders" section of your account or use the order tracking tool above with your order number.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What payment methods do you accept?
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            We accept major credit and debit cards through our secure Stripe payment gateway. All card information is encrypted and processed securely.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="faq-category">
                <h3>Shipping & Delivery</h3>
                <div class="faq-list">
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How long does shipping take?
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            Shipping times vary by seller and location. Standard shipping is typically 3-7 business days, with expedited options available.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            Do you ship internationally?
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            Yes, we ship to many countries worldwide. Shipping options and costs vary by destination and seller.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What if my package is lost or damaged?
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            If your package is lost or damaged, contact us immediately. We'll work with the shipping carrier to resolve the issue.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="faq-category">
                <h3>Returns & Refunds</h3>
                <div class="faq-list">
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            What is your return policy?
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            Most items can be returned within 30 days of delivery for a full refund. Items must be in original condition with tags attached.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            How do I start a return?
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            Go to "My Orders" in your account, find the order, and click "Return Item". Follow the instructions to print a return label.
                        </div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFAQ(this)">
                            When will I get my refund?
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            Refunds are typically processed within 3-5 business days after we receive your returned item.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Support -->
    <section class="contact-support">
        <div class="support-content">
            <h2>Still Need Help?</h2>
            <p>Our customer support team is here to assist you</p>
            
            <div class="contact-options">
                <div class="contact-option">
                    <div class="contact-icon">💬</div>
                    <h3>Live Chat</h3>
                    <p>Chat with us now</p>
                    <button class="btn" onclick="startLiveChat()">Start Chat</button>
                </div>
                
                <div class="contact-option">
                    <div class="contact-icon">📧</div>
                    <h3>Email Support</h3>
                    <p>Get help via email</p>
                    <a href="/contact.php" class="btn btn-outline">Send Email</a>
                </div>
                
                <div class="contact-option">
                    <div class="contact-icon">📞</div>
                    <h3>Phone Support</h3>
                    <p>Call us: 1-800-FEZA-HELP</p>
                    <div class="hours">Mon-Fri 9AM-6PM EST</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Help Categories -->
    <section class="help-categories">
        <h2>Browse All Help Topics</h2>
        <div class="categories-list">
            <div class="category-section">
                <h3>Shopping</h3>
                <ul>
                    <li><a href="/help/how-to-shop.php">How to shop</a></li>
                    <li><a href="/help/product-search.php">Finding products</a></li>
                    <li><a href="/help/product-questions.php">Ask product questions</a></li>
                    <li><a href="/help/wishlist.php">Using your wishlist</a></li>
                </ul>
            </div>
            
            <div class="category-section">
                <h3>Orders</h3>
                <ul>
                    <li><a href="/help/order-status.php">Order status</a></li>
                    <li><a href="/help/order-changes.php">Changing orders</a></li>
                    <li><a href="/help/order-cancellation.php">Canceling orders</a></li>
                    <li><a href="/help/order-history.php">Order history</a></li>
                </ul>
            </div>
            
            <div class="category-section">
                <h3>Account</h3>
                <ul>
                    <li><a href="/help/create-account.php">Creating an account</a></li>
                    <li><a href="/help/account-settings.php">Account settings</a></li>
                    <li><a href="/help/password-reset.php">Password reset</a></li>
                    <li><a href="/help/account-security.php">Account security</a></li>
                </ul>
            </div>
            
            <div class="category-section">
                <h3>Selling</h3>
                <ul>
                    <li><a href="/help/start-selling.php">Getting started</a></li>
                    <li><a href="/help/listing-products.php">Listing products</a></li>
                    <li><a href="/help/seller-fees.php">Seller fees</a></li>
                    <li><a href="/help/seller-tools.php">Seller tools</a></li>
                </ul>
            </div>
        </div>
    </section>
</div>

<style>
.help-header {
    text-align: center;
    margin-bottom: 50px;
    padding: 40px 0;
}

.help-header h1 {
    font-size: 36px;
    color: #1f2937;
    margin-bottom: 10px;
}

.help-header p {
    font-size: 18px;
    color: #6b7280;
    margin-bottom: 30px;
}

.help-search {
    max-width: 600px;
    margin: 0 auto;
}

.search-input-group {
    display: flex;
    gap: 10px;
}

.search-input {
    flex: 1;
    padding: 15px 20px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 16px;
}

.search-btn {
    background: #0654ba;
    color: white;
    border: none;
    padding: 15px 25px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.popular-topics h2 {
    font-size: 28px;
    color: #1f2937;
    margin-bottom: 30px;
    text-align: center;
}

.topics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 60px;
}

.topic-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    text-decoration: none;
    color: inherit;
    text-align: center;
    transition: transform 0.3s ease;
}

.topic-card:hover {
    transform: translateY(-5px);
}

.topic-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.topic-card h3 {
    color: #1f2937;
    margin-bottom: 10px;
    font-size: 18px;
}

.topic-card p {
    color: #6b7280;
    font-size: 14px;
}

.quick-actions {
    margin-bottom: 60px;
}

.quick-actions h2 {
    font-size: 28px;
    color: #1f2937;
    margin-bottom: 30px;
    text-align: center;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.action-card {
    background: #f9fafb;
    padding: 25px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.action-card h3 {
    color: #1f2937;
    margin-bottom: 8px;
}

.action-card p {
    color: #6b7280;
    margin-bottom: 15px;
}

.action-form {
    display: flex;
    gap: 10px;
}

.form-input {
    flex: 1;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.faqs-section {
    margin-bottom: 60px;
}

.faqs-section h2 {
    font-size: 28px;
    color: #1f2937;
    margin-bottom: 30px;
    text-align: center;
}

.faqs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}

.faq-category h3 {
    color: #1f2937;
    margin-bottom: 20px;
    font-size: 20px;
}

.faq-item {
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 15px;
    margin-bottom: 15px;
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    font-weight: 600;
    color: #1f2937;
    padding: 10px 0;
}

.faq-toggle {
    font-size: 20px;
    font-weight: bold;
    color: #0654ba;
}

.faq-answer {
    display: none;
    color: #6b7280;
    padding: 10px 0;
    line-height: 1.6;
}

.faq-answer.active {
    display: block;
}

.contact-support {
    background: linear-gradient(135deg, #0654ba, #1e40af);
    color: white;
    padding: 50px 0;
    border-radius: 12px;
    margin-bottom: 60px;
}

.support-content {
    text-align: center;
}

.support-content h2 {
    font-size: 28px;
    margin-bottom: 10px;
}

.support-content p {
    margin-bottom: 40px;
    opacity: 0.9;
}

.contact-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.contact-option {
    background: rgba(255,255,255,0.1);
    padding: 25px;
    border-radius: 8px;
    text-align: center;
}

.contact-icon {
    font-size: 36px;
    margin-bottom: 15px;
}

.contact-option h3 {
    margin-bottom: 8px;
}

.contact-option p {
    margin-bottom: 15px;
}

.hours {
    font-size: 14px;
    opacity: 0.8;
    margin-top: 10px;
}

.help-categories h2 {
    font-size: 28px;
    color: #1f2937;
    margin-bottom: 30px;
}

.categories-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.category-section h3 {
    color: #1f2937;
    margin-bottom: 15px;
    font-size: 18px;
}

.category-section ul {
    list-style: none;
    padding: 0;
}

.category-section li {
    margin-bottom: 8px;
}

.category-section a {
    color: #0654ba;
    text-decoration: none;
    font-size: 14px;
}

.category-section a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .help-header h1 {
        font-size: 28px;
    }
    
    .search-input-group {
        flex-direction: column;
    }
    
    .topics-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .action-form {
        flex-direction: column;
    }
    
    .contact-options {
        grid-template-columns: 1fr;
    }
}

/* Search Results Styles */
.search-results {
    max-width: 1200px;
    margin: 0 auto;
}

.search-results .card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.search-results-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-top: 20px;
}

.search-result-item {
    padding: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.search-result-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.search-result-item h4 {
    margin: 0 0 10px 0;
    font-size: 20px;
}

.search-result-item h4 a {
    color: #2563eb;
    text-decoration: none;
}

.search-result-item h4 a:hover {
    text-decoration: underline;
}

.search-result-item p {
    color: #6b7280;
    margin: 10px 0 0 0;
}

.search-result-item .badge {
    font-size: 12px;
    padding: 4px 8px;
    margin-bottom: 8px;
}
</style>

<script>
function toggleFAQ(element) {
    const answer = element.nextElementSibling;
    const toggle = element.querySelector('.faq-toggle');
    
    if (answer.classList.contains('active')) {
        answer.classList.remove('active');
        toggle.textContent = '+';
    } else {
        // Close all other FAQs
        document.querySelectorAll('.faq-answer').forEach(ans => {
            ans.classList.remove('active');
        });
        document.querySelectorAll('.faq-toggle').forEach(tog => {
            tog.textContent = '+';
        });
        
        // Open this FAQ
        answer.classList.add('active');
        toggle.textContent = '−';
    }
}

function startLiveChat() {
    // Trigger the live chat widget
    const chatWidget = document.getElementById('liveChatWidget');
    if (chatWidget) {
        chatWidget.style.display = 'flex';
        initializeLiveChat();
    } else {
        alert('Live chat will be available soon! For now, please use email support.');
    }
}

// Help Search Functionality
document.getElementById('helpSearchForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const query = document.getElementById('helpSearchInput').value.trim();
    
    if (!query) return;
    
    // Show loading state
    const resultsContainer = document.createElement('div');
    resultsContainer.id = 'searchResults';
    resultsContainer.className = 'search-results mt-4';
    resultsContainer.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><p>Searching...</p></div>';
    
    // Remove existing results
    const existingResults = document.getElementById('searchResults');
    if (existingResults) {
        existingResults.remove();
    }
    
    // Insert after search form
    document.querySelector('.help-search').insertAdjacentElement('afterend', resultsContainer);
    
    try {
        const response = await fetch(`/api/help-search.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success && data.results.length > 0) {
            let html = `
                <div class="card">
                    <div class="card-body">
                        <h3>Search Results for "${query}" (${data.count} found)</h3>
                        <div class="search-results-list">
            `;
            
            data.results.forEach(result => {
                html += `
                    <div class="search-result-item">
                        <h4><a href="${result.url}">${result.title}</a></h4>
                        <span class="badge bg-primary">${result.category}</span>
                        <p>${result.content}</p>
                    </div>
                `;
            });
            
            html += `
                        </div>
                    </div>
                </div>
            `;
            
            resultsContainer.innerHTML = html;
        } else {
            resultsContainer.innerHTML = `
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-search" style="font-size: 3rem; color: #ccc;"></i>
                        <h3 class="mt-3">No Results Found</h3>
                        <p>We couldn't find any help articles matching "${query}"</p>
                        <p>Try using different keywords or <a href="/contact.php">contact our support team</a></p>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Search error:', error);
        resultsContainer.innerHTML = `
            <div class="card">
                <div class="card-body text-center">
                    <div class="alert alert-danger">
                        <strong>Error:</strong> Unable to perform search. Please try again later.
                    </div>
                </div>
            </div>
        `;
    }
});
</script>

<?php includeFooter(); ?>