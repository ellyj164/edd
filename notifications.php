<?php
/**
 * User Notifications
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Require user login
Session::requireLogin();

// Mock notifications data (in real implementation, this would come from database)
$notifications = [
    [
        'id' => 1,
        'type' => 'order',
        'title' => 'Order Shipped',
        'message' => 'Your order #12345 has been shipped and is on the way!',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'read' => false,
        'action_url' => '/order.php?id=12345'
    ],
    [
        'id' => 2,
        'type' => 'promotion',
        'title' => 'Flash Sale Alert',
        'message' => '50% off electronics - Limited time offer! Shop now before it ends.',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'read' => false,
        'action_url' => '/deals.php'
    ],
    [
        'id' => 3,
        'type' => 'wishlist',
        'title' => 'Price Drop Alert',
        'message' => 'An item in your wishlist is now 30% off! Don\'t miss out.',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'read' => true,
        'action_url' => '/wishlist.php'
    ],
    [
        'id' => 4,
        'type' => 'account',
        'title' => 'Security Alert',
        'message' => 'We detected a login from a new device. If this wasn\'t you, please secure your account.',
        'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'read' => true,
        'action_url' => '/account/security.php'
    ],
    [
        'id' => 5,
        'type' => 'system',
        'title' => 'Welcome to FezaMarket',
        'message' => 'Thank you for joining FezaMarket! Explore our marketplace and start shopping.',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
        'read' => true,
        'action_url' => '/products.php'
    ]
];

// Count unread notifications
$unreadCount = count(array_filter($notifications, function($n) { return !$n['read']; }));

$page_title = 'Notifications';
includeHeader($page_title);
?>

<div class="container">
    <div class="notifications-header">
        <div class="header-content">
            <h1>Notifications</h1>
            <?php if ($unreadCount > 0): ?>
                <div class="unread-badge"><?php echo $unreadCount; ?> unread</div>
            <?php endif; ?>
        </div>
        
        <div class="notifications-actions">
            <button class="btn btn-outline" onclick="markAllAsRead()">Mark all as read</button>
            <button class="btn btn-outline" onclick="clearAllNotifications()">Clear all</button>
            <button class="btn" onclick="toggleNotificationSettings()">⚙️ Settings</button>
        </div>
    </div>

    <!-- Notification Filters -->
    <div class="notification-filters">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="unread">Unread</button>
        <button class="filter-btn" data-filter="order">Orders</button>
        <button class="filter-btn" data-filter="promotion">Promotions</button>
        <button class="filter-btn" data-filter="wishlist">Wishlist</button>
        <button class="filter-btn" data-filter="account">Account</button>
    </div>

    <!-- Notifications List -->
    <div class="notifications-list">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['read'] ? 'read' : 'unread'; ?>" 
                     data-notification-id="<?php echo $notification['id']; ?>"
                     data-type="<?php echo $notification['type']; ?>">
                    
                    <div class="notification-icon">
                        <?php
                        switch ($notification['type']) {
                            case 'order':
                                echo '📦';
                                break;
                            case 'promotion':
                                echo '🏷️';
                                break;
                            case 'wishlist':
                                echo '❤️';
                                break;
                            case 'account':
                                echo '🔐';
                                break;
                            case 'system':
                                echo '📢';
                                break;
                            default:
                                echo '🔔';
                        }
                        ?>
                    </div>
                    
                    <div class="notification-content" onclick="openNotification(<?php echo $notification['id']; ?>, '<?php echo $notification['action_url']; ?>')">
                        <div class="notification-header">
                            <h3 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h3>
                            <span class="notification-time"><?php echo formatDateTime($notification['created_at'], 'M j, g:i A'); ?></span>
                        </div>
                        <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                        <?php if (!$notification['read']): ?>
                            <div class="unread-indicator"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notification-actions">
                        <button class="action-btn" onclick="toggleRead(<?php echo $notification['id']; ?>)" title="Mark as read/unread">
                            <?php echo $notification['read'] ? '📩' : '📧'; ?>
                        </button>
                        <button class="action-btn" onclick="deleteNotification(<?php echo $notification['id']; ?>)" title="Delete">
                            🗑️
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-notifications">
                <div class="empty-icon">🔔</div>
                <h2>No notifications yet</h2>
                <p>We'll notify you when there are updates about your orders, account, and special offers.</p>
                <a href="/products.php" class="btn">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Load More Button -->
    <?php if (count($notifications) >= 10): ?>
    <div class="load-more-section">
        <button class="btn btn-outline" onclick="loadMoreNotifications()">Load More</button>
    </div>
    <?php endif; ?>
</div>

<!-- Notification Settings Modal -->
<div class="modal" id="notificationSettingsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Notification Settings</h2>
            <button class="modal-close" onclick="toggleNotificationSettings()">×</button>
        </div>
        <div class="modal-body">
            <div class="settings-section">
                <h3>Email Notifications</h3>
                <div class="setting-item">
                    <label class="setting-label">
                        <input type="checkbox" checked> Order updates
                        <span class="setting-description">Get notified about order status changes</span>
                    </label>
                </div>
                <div class="setting-item">
                    <label class="setting-label">
                        <input type="checkbox" checked> Promotions and deals
                        <span class="setting-description">Receive exclusive offers and sale notifications</span>
                    </label>
                </div>
                <div class="setting-item">
                    <label class="setting-label">
                        <input type="checkbox" checked> Wishlist alerts
                        <span class="setting-description">Price drops and availability updates</span>
                    </label>
                </div>
                <div class="setting-item">
                    <label class="setting-label">
                        <input type="checkbox" checked> Account security
                        <span class="setting-description">Login alerts and security notifications</span>
                    </label>
                </div>
            </div>
            
            <div class="settings-section">
                <h3>Push Notifications</h3>
                <div class="setting-item">
                    <label class="setting-label">
                        <input type="checkbox"> Browser notifications
                        <span class="setting-description">Show notifications in your browser</span>
                    </label>
                </div>
                <div class="setting-item">
                    <label class="setting-label">
                        <input type="checkbox"> Mobile app notifications
                        <span class="setting-description">Push notifications to your mobile device</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="toggleNotificationSettings()">Cancel</button>
            <button class="btn" onclick="saveNotificationSettings()">Save Settings</button>
        </div>
    </div>
</div>

<style>
.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px 0;
    border-bottom: 1px solid #e5e7eb;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 15px;
}

.header-content h1 {
    font-size: 28px;
    color: #1f2937;
    margin: 0;
}

.unread-badge {
    background: #dc2626;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
}

.notifications-actions {
    display: flex;
    gap: 10px;
}

.notification-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
}

.filter-btn {
    background: white;
    border: 1px solid #d1d5db;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-btn:hover,
.filter-btn.active {
    background: #0654ba;
    color: white;
    border-color: #0654ba;
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.notification-item {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: flex-start;
    gap: 15px;
    transition: all 0.3s ease;
    position: relative;
}

.notification-item.unread {
    border-left: 4px solid #0654ba;
    box-shadow: 0 4px 12px rgba(6, 84, 186, 0.15);
}

.notification-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.notification-icon {
    font-size: 24px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border-radius: 50%;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
    cursor: pointer;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.notification-title {
    color: #1f2937;
    font-size: 16px;
    font-weight: 600;
    margin: 0;
}

.notification-time {
    color: #6b7280;
    font-size: 14px;
    flex-shrink: 0;
    margin-left: 15px;
}

.notification-message {
    color: #374151;
    line-height: 1.5;
    margin: 0;
}

.unread-indicator {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 8px;
    height: 8px;
    background: #0654ba;
    border-radius: 50%;
}

.notification-actions {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.action-btn {
    background: transparent;
    border: none;
    font-size: 16px;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

.action-btn:hover {
    background: #f3f4f6;
}

.empty-notifications {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-notifications h2 {
    color: #1f2937;
    margin-bottom: 10px;
}

.empty-notifications p {
    color: #6b7280;
    margin-bottom: 30px;
}

.load-more-section {
    text-align: center;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #e5e7eb;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    background: white;
    max-width: 500px;
    margin: 50px auto;
    border-radius: 8px;
    overflow: hidden;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
    margin: 0;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6b7280;
}

.modal-body {
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.settings-section {
    margin-bottom: 25px;
}

.settings-section h3 {
    color: #1f2937;
    margin-bottom: 15px;
    font-size: 16px;
}

.setting-item {
    margin-bottom: 15px;
}

.setting-label {
    display: flex;
    flex-direction: column;
    cursor: pointer;
    color: #374151;
}

.setting-label input {
    margin-bottom: 5px;
    align-self: flex-start;
}

.setting-description {
    font-size: 14px;
    color: #6b7280;
    margin-left: 20px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 20px;
    border-top: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
    .notifications-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .notification-filters {
        flex-wrap: wrap;
    }
    
    .notification-item {
        flex-direction: column;
        gap: 10px;
    }
    
    .notification-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .notification-actions {
        flex-direction: row;
        align-self: flex-end;
    }
    
    .modal-content {
        margin: 20px;
        max-width: none;
    }
}
</style>

<script>
// Filter notifications
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Update active filter
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        filterNotifications(filter);
    });
});

function filterNotifications(filter) {
    const notifications = document.querySelectorAll('.notification-item');
    
    notifications.forEach(notification => {
        if (filter === 'all') {
            notification.style.display = 'flex';
        } else if (filter === 'unread') {
            notification.style.display = notification.classList.contains('unread') ? 'flex' : 'none';
        } else {
            const type = notification.dataset.type;
            notification.style.display = type === filter ? 'flex' : 'none';
        }
    });
}

function openNotification(notificationId, actionUrl) {
    // Mark as read when opened
    markAsRead(notificationId);
    
    // Navigate to action URL
    if (actionUrl) {
        window.location.href = actionUrl;
    }
}

function markAsRead(notificationId) {
    const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (notification) {
        notification.classList.remove('unread');
        notification.classList.add('read');
        
        // Remove unread indicator
        const indicator = notification.querySelector('.unread-indicator');
        if (indicator) {
            indicator.remove();
        }
        
        // Update action button
        const actionBtn = notification.querySelector('.action-btn');
        if (actionBtn) {
            actionBtn.innerHTML = '📩';
            actionBtn.title = 'Mark as unread';
        }
        
        updateUnreadCount();
    }
}

function toggleRead(notificationId) {
    const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (notification) {
        if (notification.classList.contains('unread')) {
            markAsRead(notificationId);
        } else {
            markAsUnread(notificationId);
        }
    }
}

function markAsUnread(notificationId) {
    const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (notification) {
        notification.classList.remove('read');
        notification.classList.add('unread');
        
        // Add unread indicator if not exists
        if (!notification.querySelector('.unread-indicator')) {
            const indicator = document.createElement('div');
            indicator.className = 'unread-indicator';
            notification.querySelector('.notification-content').appendChild(indicator);
        }
        
        // Update action button
        const actionBtn = notification.querySelector('.action-btn');
        if (actionBtn) {
            actionBtn.innerHTML = '📧';
            actionBtn.title = 'Mark as read';
        }
        
        updateUnreadCount();
    }
}

function deleteNotification(notificationId) {
    if (confirm('Are you sure you want to delete this notification?')) {
        const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
        if (notification) {
            notification.remove();
            updateUnreadCount();
        }
    }
}

function markAllAsRead() {
    document.querySelectorAll('.notification-item.unread').forEach(notification => {
        const notificationId = notification.dataset.notificationId;
        markAsRead(notificationId);
    });
}

function clearAllNotifications() {
    if (confirm('Are you sure you want to clear all notifications?')) {
        document.querySelectorAll('.notification-item').forEach(notification => {
            notification.remove();
        });
        
        // Show empty state
        const emptyState = `
            <div class="empty-notifications">
                <div class="empty-icon">🔔</div>
                <h2>No notifications yet</h2>
                <p>We'll notify you when there are updates about your orders, account, and special offers.</p>
                <a href="/products.php" class="btn">Start Shopping</a>
            </div>
        `;
        document.querySelector('.notifications-list').innerHTML = emptyState;
        updateUnreadCount();
    }
}

function updateUnreadCount() {
    const unreadCount = document.querySelectorAll('.notification-item.unread').length;
    const badge = document.querySelector('.unread-badge');
    
    if (unreadCount > 0) {
        if (badge) {
            badge.textContent = `${unreadCount} unread`;
        } else {
            const headerContent = document.querySelector('.header-content');
            const newBadge = document.createElement('div');
            newBadge.className = 'unread-badge';
            newBadge.textContent = `${unreadCount} unread`;
            headerContent.appendChild(newBadge);
        }
    } else if (badge) {
        badge.remove();
    }
}

function toggleNotificationSettings() {
    const modal = document.getElementById('notificationSettingsModal');
    modal.style.display = modal.style.display === 'block' ? 'none' : 'block';
}

function saveNotificationSettings() {
    // In a real implementation, this would save settings to the server
    alert('Notification settings saved successfully!');
    toggleNotificationSettings();
}

function loadMoreNotifications() {
    // In a real implementation, this would load more notifications from the server
    alert('Loading more notifications...');
}

// Close modal when clicking outside
document.getElementById('notificationSettingsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        toggleNotificationSettings();
    }
});
</script>

<?php includeFooter(); ?>