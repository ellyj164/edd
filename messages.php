<?php
/**
 * Messages / Messaging Center
 * User-to-user and seller-to-buyer messaging
 */

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header('Location: /login.php?redirect=/messages.php');
    exit;
}

$page_title = 'Messages - FezaMarket';
$meta_description = 'View and send messages to buyers, sellers, and FezaMarket support.';

includeHeader($page_title);
?>

<div class="messages-page">
    <div class="container">
        <div class="messages-container">
            <!-- Sidebar -->
            <div class="messages-sidebar">
                <div class="sidebar-header">
                    <h2>Messages</h2>
                    <button class="btn btn-primary btn-sm" onclick="window.location.href='/contact.php'">
                        <i class="fas fa-plus"></i> New Message
                    </button>
                </div>
                
                <div class="message-filters">
                    <button class="filter-btn active">All</button>
                    <button class="filter-btn">Unread</button>
                    <button class="filter-btn">Sellers</button>
                    <button class="filter-btn">Buyers</button>
                </div>
                
                <div class="message-list">
                    <div class="message-list-item empty-state">
                        <div class="empty-icon">
                            <i class="far fa-envelope"></i>
                        </div>
                        <p>No messages yet</p>
                        <span>Your conversations will appear here</span>
                    </div>
                </div>
            </div>
            
            <!-- Message Content Area -->
            <div class="messages-content">
                <div class="empty-state-large">
                    <div class="empty-icon-large">
                        <i class="far fa-comments"></i>
                    </div>
                    <h3>Welcome to your Message Center</h3>
                    <p>Stay connected with buyers, sellers, and FezaMarket support. Your messages will appear here.</p>
                    
                    <div class="help-cards">
                        <div class="help-card">
                            <i class="fas fa-question-circle"></i>
                            <h4>Need Help?</h4>
                            <p>Contact our support team</p>
                            <a href="/contact.php" class="btn btn-outline">Contact Support</a>
                        </div>
                        
                        <div class="help-card">
                            <i class="fas fa-shopping-cart"></i>
                            <h4>Your Orders</h4>
                            <p>View order details and history</p>
                            <a href="/account.php?section=orders" class="btn btn-outline">View Orders</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.messages-page {
    background-color: #f8f9fa;
    min-height: 80vh;
    padding: 30px 20px;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
}

.messages-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 0;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    min-height: 600px;
}

.messages-sidebar {
    background: #f8f9fa;
    border-right: 1px solid #e0e0e0;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: 25px 20px;
    background: white;
    border-bottom: 1px solid #e0e0e0;
}

.sidebar-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: #333;
}

.message-filters {
    padding: 15px 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    border-bottom: 1px solid #e0e0e0;
}

.filter-btn {
    padding: 8px 16px;
    border: 1px solid #e0e0e0;
    background: white;
    border-radius: 20px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-btn:hover,
.filter-btn.active {
    background: #4285f4;
    color: white;
    border-color: #4285f4;
}

.message-list {
    flex: 1;
    overflow-y: auto;
}

.message-list-item {
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    cursor: pointer;
    transition: background 0.2s ease;
}

.message-list-item:hover {
    background: white;
}

.message-list-item.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    cursor: default;
}

.empty-icon {
    font-size: 3rem;
    color: #ccc;
    margin-bottom: 15px;
}

.message-list-item.empty-state p {
    font-weight: 600;
    color: #666;
    margin-bottom: 5px;
}

.message-list-item.empty-state span {
    font-size: 0.9rem;
    color: #999;
}

.messages-content {
    padding: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-state-large {
    text-align: center;
    max-width: 600px;
}

.empty-icon-large {
    font-size: 5rem;
    color: #ccc;
    margin-bottom: 25px;
}

.empty-state-large h3 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: #333;
}

.empty-state-large p {
    font-size: 1.1rem;
    color: #666;
    line-height: 1.6;
    margin-bottom: 40px;
}

.help-cards {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 30px;
}

.help-card {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
}

.help-card i {
    font-size: 2.5rem;
    color: #4285f4;
    margin-bottom: 15px;
}

.help-card h4 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
}

.help-card p {
    color: #666;
    margin-bottom: 20px;
    font-size: 0.95rem;
}

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 0.95rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #4285f4;
    color: white;
}

.btn-primary:hover {
    background: #3367d6;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 0.85rem;
}

.btn-outline {
    background: white;
    color: #4285f4;
    border: 2px solid #4285f4;
}

.btn-outline:hover {
    background: #4285f4;
    color: white;
}

@media (max-width: 768px) {
    .messages-container {
        grid-template-columns: 1fr;
    }
    
    .messages-sidebar {
        border-right: none;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .help-cards {
        grid-template-columns: 1fr;
    }
}
</style>

<?php includeFooter(); ?>
