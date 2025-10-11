<?php
/**
 * FezaMarket Live - Live Shopping Experience
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$product = new Product();
$liveStream = new LiveStream();

// Get active live streams from database
$activeStreams = $liveStream->getActiveStreams(10);

// Get products for live shopping events
$liveProducts = $product->findAll(8);
$featuredProducts = $product->getFeatured(4);

$page_title = 'FezaMarket Live - Shop Live Events';
includeHeader($page_title);
?>

<div class="container">
    <!-- Live Shopping Header -->
    <div class="live-header">
        <h1>🔴 FezaMarket Live</h1>
        <p>Shop live events, exclusive deals, and interactive product showcases</p>
    </div>

    <!-- Live Now Section -->
    <section class="live-now-section">
        <div class="section-header">
            <h2>🔴 Live Now</h2>
            <span class="live-count"><?php echo count($activeStreams); ?> live events</span>
        </div>
        
        <?php if (count($activeStreams) > 0): ?>
        <div class="live-streams-grid">
            <!-- Main Live Stream -->
            <?php 
            $mainStream = $activeStreams[0] ?? null;
            if ($mainStream):
                $streamProducts = $liveStream->getStreamProducts($mainStream['id']);
                $streamStats = $liveStream->getStreamStats($mainStream['id']);
            ?>
            <div class="main-live-stream" data-stream-id="<?php echo $mainStream['id']; ?>">
                <div class="stream-container">
                    <div class="stream-video">
                        <div class="live-badge">🔴 LIVE</div>
                        <div class="stream-content">
                            <div class="stream-thumbnail">📱</div>
                            <h3><?php echo htmlspecialchars($mainStream['title']); ?></h3>
                            <p><?php echo htmlspecialchars($mainStream['description'] ?? 'Join us for exclusive deals!'); ?></p>
                        </div>
                        <div class="stream-stats">
                            <span class="viewer-count" id="viewer-count-<?php echo $mainStream['id']; ?>">
                                👥 <span class="count"><?php echo $mainStream['current_viewers'] ?? 0; ?></span> watching
                            </span>
                            <span class="live-timer" id="live-timer-<?php echo $mainStream['id']; ?>">⏰ 00:00</span>
                        </div>
                        
                        <!-- Interaction Buttons -->
                        <div class="stream-actions" style="position: absolute; bottom: 20px; right: 20px; display: flex; gap: 10px;">
                            <button class="btn-icon like-btn" data-stream-id="<?php echo $mainStream['id']; ?>" onclick="handleLike(this)">
                                👍 <span class="count"><?php echo $streamStats['likes_count'] ?? 0; ?></span>
                            </button>
                            <button class="btn-icon dislike-btn" data-stream-id="<?php echo $mainStream['id']; ?>" onclick="handleDislike(this)">
                                👎 <span class="count"><?php echo $streamStats['dislikes_count'] ?? 0; ?></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="stream-interaction">
                        <div class="chat-section">
                            <h4>Live Chat</h4>
                            <div class="chat-messages" id="chatMessages">
                                <!-- Comments will be loaded here -->
                            </div>
                            
                            <?php if (Session::isLoggedIn()): ?>
                                <div class="chat-input">
                                    <input type="text" placeholder="Join the conversation..." id="chatInput">
                                    <button onclick="sendMessage(<?php echo $mainStream['id']; ?>)" class="btn btn-sm">Send</button>
                                </div>
                            <?php else: ?>
                                <div class="chat-login">
                                    <a href="/login.php?return=/live.php" class="btn btn-sm">Sign In to Chat</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="featured-products">
                            <h4>Featured in Stream</h4>
                            <?php foreach (array_slice($streamProducts, 0, 2) as $streamProduct): 
                                // Get full product details
                                $productDetails = $product->findById($streamProduct['product_id']);
                                if (!$productDetails) continue;
                            ?>
                                <div class="stream-product">
                                    <img src="<?php echo getSafeProductImageUrl($productDetails); ?>" 
                                         alt="<?php echo htmlspecialchars($productDetails['name']); ?>">
                                    <div class="product-info">
                                        <h5><?php echo htmlspecialchars(substr($productDetails['name'], 0, 30)); ?>...</h5>
                                        <div class="live-price">
                                            <span class="current-price"><?php echo formatPrice($streamProduct['special_price'] ?? $productDetails['price']); ?></span>
                                            <?php if ($streamProduct['special_price']): ?>
                                            <span class="original-price"><?php echo formatPrice($productDetails['price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-buttons" style="display: flex; gap: 5px; margin-top: 10px;">
                                            <button class="btn btn-sm btn-primary live-buy-btn" onclick="buyNow(<?php echo $productDetails['id']; ?>)">
                                                Buy Now
                                            </button>
                                            <button class="btn btn-sm btn-outline live-cart-btn" onclick="addToCart(<?php echo $productDetails['id']; ?>)">
                                                Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Other Live Streams -->
            <div class="other-streams">
                <?php foreach (array_slice($activeStreams, 1, 2) as $stream): ?>
                <div class="mini-stream" data-stream-id="<?php echo $stream['id']; ?>">
                    <a href="#" onclick="switchStream(<?php echo $stream['id']; ?>); return false;">
                        <div class="mini-stream-video">
                            <div class="live-badge">🔴 LIVE</div>
                            <div class="mini-stream-content">
                                <span class="stream-emoji">🛍️</span>
                                <h4><?php echo htmlspecialchars($stream['title']); ?></h4>
                                <p><?php echo htmlspecialchars(substr($stream['description'] ?? '', 0, 40)); ?>...</p>
                                <span class="mini-viewers">👥 <?php echo $stream['current_viewers'] ?? 0; ?> watching</span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px;">
            <h3 style="color: #6b7280; margin-bottom: 10px;">No Live Streams Right Now</h3>
            <p style="color: #9ca3af;">Check back soon for exciting live shopping events!</p>
        </div>
        <?php endif; ?>
    </section>

    <!-- Upcoming Events -->
    <section class="upcoming-events">
        <h2>📅 Upcoming Live Events</h2>
        <div class="events-grid">
            <div class="event-card">
                <div class="event-time">
                    <div class="time-badge">Today 8:00 PM</div>
                </div>
                <div class="event-content">
                    <h3>💎 Jewelry & Watches Special</h3>
                    <p>Exclusive collection reveal with celebrity stylist Maria Rodriguez</p>
                    <div class="event-details">
                        <span class="host">👤 Maria Rodriguez</span>
                        <span class="category">💎 Jewelry</span>
                    </div>
                    <button class="btn btn-outline notify-btn" onclick="setReminder(1)">
                        🔔 Set Reminder
                    </button>
                </div>
            </div>
            
            <div class="event-card">
                <div class="event-time">
                    <div class="time-badge">Tomorrow 2:00 PM</div>
                </div>
                <div class="event-content">
                    <h3>🎮 Gaming Gear Expo</h3>
                    <p>Latest gaming accessories and setup tutorials</p>
                    <div class="event-details">
                        <span class="host">👤 GameMaster Pro</span>
                        <span class="category">🎮 Gaming</span>
                    </div>
                    <button class="btn btn-outline notify-btn" onclick="setReminder(2)">
                        🔔 Set Reminder
                    </button>
                </div>
            </div>
            
            <div class="event-card">
                <div class="event-time">
                    <div class="time-badge">Sat 10:00 AM</div>
                </div>
                <div class="event-content">
                    <h3>🍳 Kitchen Essentials Workshop</h3>
                    <p>Professional chef showcases must-have cooking tools</p>
                    <div class="event-details">
                        <span class="host">👤 Chef Antonio</span>
                        <span class="category">🍳 Kitchen</span>
                    </div>
                    <button class="btn btn-outline notify-btn" onclick="setReminder(3)">
                        🔔 Set Reminder
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Shopping Benefits -->
    <section class="live-benefits">
        <h2>Why Shop Live?</h2>
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">💰</div>
                <h3>Exclusive Deals</h3>
                <p>Get special pricing only available during live events</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">🎯</div>
                <h3>Expert Advice</h3>
                <p>Ask questions and get real-time answers from experts</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">👥</div>
                <h3>Community</h3>
                <p>Shop with others and share experiences in live chat</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">📦</div>
                <h3>Product Showcases</h3>
                <p>See products in action before you buy</p>
            </div>
        </div>
    </section>

    <!-- Popular Categories -->
    <section class="live-categories">
        <h2>Popular Live Shopping Categories</h2>
        <div class="categories-grid">
            <div class="category-card" onclick="window.location.href='/category.php?name=electronics'">
                <span class="category-emoji">📱</span>
                <h3>Electronics</h3>
                <p>Tech demos and launches</p>
            </div>
            <div class="category-card" onclick="window.location.href='/category.php?name=fashion'">
                <span class="category-emoji">👗</span>
                <h3>Fashion</h3>
                <p>Style shows and trends</p>
            </div>
            <div class="category-card" onclick="window.location.href='/category.php?name=home-garden'">
                <span class="category-emoji">🏠</span>
                <h3>Home & Garden</h3>
                <p>Decorating and DIY</p>
            </div>
            <div class="category-card" onclick="window.location.href='/category.php?name=sports'">
                <span class="category-emoji">⚽</span>
                <h3>Sports</h3>
                <p>Fitness and outdoor gear</p>
            </div>
        </div>
    </section>

    <!-- Become a Host -->
    <section class="become-host">
        <div class="host-banner">
            <div class="host-content">
                <h2>Want to Host Your Own Live Shopping Event?</h2>
                <p>Reach thousands of buyers and showcase your products in real-time</p>
                <div class="host-benefits">
                    <span>📈 Increase sales</span>
                    <span>👥 Build community</span>
                    <span>🎯 Direct engagement</span>
                </div>
                <a href="/seller-center.php" class="btn btn-large">Apply to Host</a>
            </div>
            <div class="host-graphic">
                <div class="host-illustration">📹</div>
            </div>
        </div>
    </section>
</div>

<style>
.live-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 0;
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: white;
    border-radius: 12px;
}

.live-header h1 {
    font-size: 36px;
    margin-bottom: 10px;
}

.live-header p {
    font-size: 18px;
    opacity: 0.9;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.section-header h2 {
    color: #1f2937;
    font-size: 24px;
}

.live-count {
    background: #dc2626;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

.live-streams-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 60px;
}

.main-live-stream {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.stream-video {
    background: linear-gradient(135deg, #1f2937, #374151);
    color: white;
    padding: 30px;
    position: relative;
    min-height: 300px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-align: center;
}

.live-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #dc2626;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.stream-thumbnail {
    font-size: 64px;
    margin-bottom: 20px;
}

.stream-content h3 {
    font-size: 24px;
    margin-bottom: 10px;
}

.stream-stats {
    position: absolute;
    bottom: 15px;
    left: 15px;
    right: 15px;
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}

.stream-interaction {
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.chat-section h4,
.featured-products h4 {
    color: #1f2937;
    margin-bottom: 15px;
}

.chat-messages {
    background: #f9fafb;
    border-radius: 8px;
    padding: 15px;
    height: 200px;
    overflow-y: auto;
    margin-bottom: 15px;
}

.chat-message {
    margin-bottom: 10px;
    font-size: 14px;
}

.chat-message strong {
    color: #0654ba;
}

.chat-input {
    display: flex;
    gap: 10px;
}

.chat-input input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.chat-login {
    text-align: center;
}

.btn-icon {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    padding: 8px 15px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-icon:hover {
    background: white;
    transform: scale(1.05);
}

.btn-icon.active {
    background: #dc2626;
    color: white;
}

.btn-outline {
    border: 1px solid #d1d5db;
    background: white;
}

.btn-outline:hover {
    background: #f3f4f6;
}

.product-buttons {
    display: flex;
    gap: 5px;
    margin-top: 10px;
}

.stream-product {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    padding: 10px;
    background: #f9fafb;
    border-radius: 6px;
}

.stream-product img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.stream-product .product-info {
    flex: 1;
}

.stream-product h5 {
    font-size: 14px;
    color: #1f2937;
    margin-bottom: 5px;
}

.live-price {
    margin-bottom: 8px;
}

.current-price {
    color: #dc2626;
    font-weight: 600;
    margin-right: 10px;
}

.original-price {
    color: #6b7280;
    text-decoration: line-through;
    font-size: 14px;
}

.live-buy-btn {
    background: #dc2626 !important;
    color: white !important;
}

.other-streams {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.mini-stream {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.3s ease;
}

.mini-stream:hover {
    transform: translateY(-3px);
}

.mini-stream-video {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    padding: 20px;
    position: relative;
    text-align: center;
}

.mini-stream-content {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.stream-emoji {
    font-size: 32px;
}

.mini-stream h4 {
    font-size: 16px;
    margin: 0;
}

.mini-stream p {
    font-size: 14px;
    margin: 0;
    opacity: 0.9;
}

.mini-viewers {
    font-size: 12px;
    opacity: 0.8;
}

.upcoming-events {
    margin-bottom: 60px;
}

.upcoming-events h2 {
    color: #1f2937;
    margin-bottom: 30px;
    text-align: center;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.event-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.event-card:hover {
    transform: translateY(-3px);
}

.event-time {
    background: #0654ba;
    color: white;
    padding: 15px;
    text-align: center;
}

.time-badge {
    font-weight: 600;
}

.event-content {
    padding: 20px;
}

.event-content h3 {
    color: #1f2937;
    margin-bottom: 10px;
}

.event-content p {
    color: #6b7280;
    margin-bottom: 15px;
    line-height: 1.5;
}

.event-details {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 14px;
    color: #374151;
}

.live-benefits {
    margin-bottom: 60px;
}

.live-benefits h2 {
    color: #1f2937;
    margin-bottom: 40px;
    text-align: center;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 25px;
}

.benefit-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.benefit-icon {
    font-size: 36px;
    margin-bottom: 15px;
}

.benefit-card h3 {
    color: #1f2937;
    margin-bottom: 10px;
}

.benefit-card p {
    color: #6b7280;
    font-size: 14px;
}

.live-categories h2 {
    color: #1f2937;
    margin-bottom: 30px;
    text-align: center;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 60px;
}

.category-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.category-card:hover {
    transform: translateY(-3px);
}

.category-emoji {
    font-size: 36px;
    margin-bottom: 15px;
}

.category-card h3 {
    color: #1f2937;
    margin-bottom: 8px;
}

.category-card p {
    color: #6b7280;
    font-size: 14px;
}

.become-host {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 12px;
    overflow: hidden;
}

.host-banner {
    display: grid;
    grid-template-columns: 2fr 1fr;
    align-items: center;
    padding: 40px;
}

.host-content h2 {
    color: #1f2937;
    margin-bottom: 15px;
}

.host-content p {
    color: #374151;
    margin-bottom: 20px;
}

.host-benefits {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.host-benefits span {
    background: rgba(255,255,255,0.8);
    color: #1f2937;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

.host-illustration {
    font-size: 120px;
    text-align: center;
    opacity: 0.8;
}

@media (max-width: 768px) {
    .live-streams-grid {
        grid-template-columns: 1fr;
    }
    
    .stream-interaction {
        grid-template-columns: 1fr;
    }
    
    .host-banner {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .host-benefits {
        justify-content: center;
    }
}
</style>

<!-- Include purchase flows JS -->
<script src="/assets/js/purchase-flows.js"></script>

<script>
// Current stream ID (for main stream)
let currentStreamId = null;
let viewerId = null;
let isLoggedIn = <?php echo Session::isLoggedIn() ? 'true' : 'false'; ?>;

// Initialize stream when page loads
document.addEventListener('DOMContentLoaded', function() {
    const mainStream = document.querySelector('.main-live-stream');
    if (mainStream) {
        currentStreamId = parseInt(mainStream.dataset.streamId);
        joinStream(currentStreamId);
        loadComments(currentStreamId);
        
        // Update viewer count and comments periodically
        setInterval(() => {
            updateViewerCount(currentStreamId);
            loadComments(currentStreamId);
        }, 10000); // Every 10 seconds
        
        // Update stream timer
        updateStreamTimer(currentStreamId);
        setInterval(() => updateStreamTimer(currentStreamId), 1000);
    }
});

// Before leaving page, mark viewer as left
window.addEventListener('beforeunload', function() {
    if (viewerId && currentStreamId) {
        leaveStream(currentStreamId, viewerId);
    }
});

function joinStream(streamId) {
    fetch('/api/live/viewers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'join',
            stream_id: streamId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            viewerId = data.viewer_id;
            updateViewerCountDisplay(streamId, data.viewer_count);
        }
    })
    .catch(error => console.error('Error joining stream:', error));
}

function leaveStream(streamId, viewerId) {
    navigator.sendBeacon('/api/live/viewers.php', JSON.stringify({
        action: 'leave',
        stream_id: streamId,
        viewer_id: viewerId
    }));
}

function updateViewerCount(streamId) {
    fetch(`/api/live/viewers.php?action=count&stream_id=${streamId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateViewerCountDisplay(streamId, data.count);
        }
    })
    .catch(error => console.error('Error updating viewer count:', error));
}

function updateViewerCountDisplay(streamId, count) {
    const countElement = document.querySelector(`#viewer-count-${streamId} .count`);
    if (countElement) {
        countElement.textContent = count;
    }
}

function updateStreamTimer(streamId) {
    // This would be calculated from stream start time
    // For now, just incrementing
    const timerElement = document.getElementById(`live-timer-${streamId}`);
    if (timerElement && timerElement.dataset.startTime) {
        const startTime = new Date(timerElement.dataset.startTime);
        const now = new Date();
        const diff = Math.floor((now - startTime) / 1000);
        const hours = Math.floor(diff / 3600);
        const minutes = Math.floor((diff % 3600) / 60);
        const seconds = diff % 60;
        timerElement.textContent = `⏰ ${hours > 0 ? hours + ':' : ''}${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }
}

function handleLike(button) {
    if (!isLoggedIn) {
        window.location.href = '/login.php?return=/live.php';
        return;
    }
    
    const streamId = parseInt(button.dataset.streamId);
    const isActive = button.classList.contains('active');
    const action = isActive ? 'unlike' : 'like';
    
    fetch('/api/live/interact.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: action,
            stream_id: streamId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (isActive) {
                button.classList.remove('active');
                const count = parseInt(button.querySelector('.count').textContent);
                button.querySelector('.count').textContent = Math.max(0, count - 1);
            } else {
                button.classList.add('active');
                const count = parseInt(button.querySelector('.count').textContent);
                button.querySelector('.count').textContent = count + 1;
                
                // Remove dislike if present
                const dislikeBtn = document.querySelector(`.dislike-btn[data-stream-id="${streamId}"]`);
                if (dislikeBtn && dislikeBtn.classList.contains('active')) {
                    dislikeBtn.classList.remove('active');
                    const dislikeCount = parseInt(dislikeBtn.querySelector('.count').textContent);
                    dislikeBtn.querySelector('.count').textContent = Math.max(0, dislikeCount - 1);
                }
            }
        } else if (data.error === 'Authentication required') {
            window.location.href = data.redirect;
        }
    })
    .catch(error => console.error('Error:', error));
}

function handleDislike(button) {
    if (!isLoggedIn) {
        window.location.href = '/login.php?return=/live.php';
        return;
    }
    
    const streamId = parseInt(button.dataset.streamId);
    const isActive = button.classList.contains('active');
    const action = isActive ? 'undislike' : 'dislike';
    
    fetch('/api/live/interact.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: action,
            stream_id: streamId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (isActive) {
                button.classList.remove('active');
                const count = parseInt(button.querySelector('.count').textContent);
                button.querySelector('.count').textContent = Math.max(0, count - 1);
            } else {
                button.classList.add('active');
                const count = parseInt(button.querySelector('.count').textContent);
                button.querySelector('.count').textContent = count + 1;
                
                // Remove like if present
                const likeBtn = document.querySelector(`.like-btn[data-stream-id="${streamId}"]`);
                if (likeBtn && likeBtn.classList.contains('active')) {
                    likeBtn.classList.remove('active');
                    const likeCount = parseInt(likeBtn.querySelector('.count').textContent);
                    likeBtn.querySelector('.count').textContent = Math.max(0, likeCount - 1);
                }
            }
        } else if (data.error === 'Authentication required') {
            window.location.href = data.redirect;
        }
    })
    .catch(error => console.error('Error:', error));
}

function sendMessage(streamId) {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    fetch('/api/live/interact.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'comment',
            stream_id: streamId,
            comment: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            loadComments(streamId);
        } else {
            alert('Error posting comment: ' + data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

function loadComments(streamId) {
    fetch('/api/live/interact.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get_comments',
            stream_id: streamId,
            limit: 50
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.comments) {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.innerHTML = data.comments.map(comment => `
                    <div class="chat-message">
                        <strong>${escapeHtml(comment.username || 'Guest')}:</strong> 
                        ${escapeHtml(comment.comment_text)}
                    </div>
                `).join('');
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
    })
    .catch(error => console.error('Error loading comments:', error));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function switchStream(streamId) {
    // This would switch the main stream to another stream
    window.location.href = `/live.php?stream=${streamId}`;
}

function setReminder(eventId) {
    if (confirm('Set a reminder for this live event?')) {
        // In a real implementation, this would save the reminder
        const button = event.target;
        button.textContent = '✅ Reminder Set';
        button.disabled = true;
        button.style.background = '#10b981';
    }
}

// Poll for live stream status updates every 30 seconds
function checkLiveStreamStatus() {
    fetch('/api/stream-status.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.live_streams) {
                updateLiveStreamUI(data.live_streams);
            }
        })
        .catch(error => console.error('Error checking stream status:', error));
}

function updateLiveStreamUI(liveStreams) {
    // Update live count
    const liveCount = document.querySelector('.live-count');
    if (liveCount) {
        liveCount.textContent = `${liveStreams.length} live events`;
    }
    
    // If no streams are live but page shows streams, reload to update
    if (liveStreams.length === 0 && document.querySelector('.main-live-stream')) {
        location.reload();
    }
    
    // If streams are live but page shows "no live streams", reload to update
    if (liveStreams.length > 0 && !document.querySelector('.main-live-stream')) {
        location.reload();
    }
    
    // Update viewer counts for visible streams
    liveStreams.forEach(stream => {
        const viewerCountEl = document.getElementById(`viewer-count-${stream.id}`);
        if (viewerCountEl) {
            const countSpan = viewerCountEl.querySelector('.count');
            if (countSpan) {
                countSpan.textContent = stream.viewer_count;
            }
        }
    });
}

// Start polling when page loads
if (document.querySelector('.live-now-section')) {
    // Check immediately
    checkLiveStreamStatus();
    
    // Then check every 30 seconds
    setInterval(checkLiveStreamStatus, 30000);
}

// Simulate live chat activity
setInterval(() => {
    const messages = [
        '<strong>LiveShopper99:</strong> This is amazing! 😍',
        '<strong>DealHunter:</strong> How much is shipping?',
        '<strong>TechReviewer:</strong> Great product showcase!',
        '<strong>BargainFinder:</strong> Is this the best price?'
    ];
    
    const chatMessages = document.getElementById('chatMessages');
    const randomMessage = messages[Math.floor(Math.random() * messages.length)];
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message';
    messageDiv.innerHTML = randomMessage;
    
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Remove old messages to prevent overflow
    if (chatMessages.children.length > 10) {
        chatMessages.removeChild(chatMessages.firstChild);
    }
}, 8000);

// Allow Enter key to send messages
document.getElementById('chatInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        sendMessage();
    }
});
</script>

<?php includeFooter(); ?>