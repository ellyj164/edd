<?php
/**
 * Admin Chat Console
 * Simple interface for support agents to manage live chats
 */

require_once __DIR__ . '/../../includes/init.php';

// Require admin authentication
Session::requireLogin();

// Simple role check - adjust based on your auth system
$currentUserId = Session::getUserId();
$user = new User();
$userData = $user->find($currentUserId);

if (!$userData || !in_array($userData['role'], ['admin', 'support', 'agent'])) {
    redirect('/');
}

// Handle agent actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $chatId = $_POST['chat_id'] ?? null;
    
    $db = Database::getInstance()->getConnection();
    
    if ($action === 'assign' && $chatId) {
        $stmt = $db->prepare("UPDATE chats SET assigned_agent_id = ?, status = 'active' WHERE id = ?");
        $stmt->execute([$currentUserId, $chatId]);
    } elseif ($action === 'close' && $chatId) {
        $stmt = $db->prepare("UPDATE chats SET status = 'closed', closed_at = NOW() WHERE id = ?");
        $stmt->execute([$chatId]);
    } elseif ($action === 'send_message' && $chatId) {
        $message = trim($_POST['message'] ?? '');
        if ($message) {
            $stmt = $db->prepare("
                INSERT INTO chat_messages (chat_id, sender, sender_id, message, created_at)
                VALUES (?, 'agent', ?, ?, NOW())
            ");
            $stmt->execute([$chatId, $currentUserId, $message]);
        }
    }
}

$page_title = 'Live Chat Console';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>📞 Live Chat Console</h1>
        <p>Manage customer support conversations</p>
    </div>
    
    <div class="chat-console-layout">
        <!-- Chat Queue Sidebar -->
        <div class="chat-queue">
            <div class="queue-header">
                <h3>Active Chats <span id="activeCount" class="badge">0</span></h3>
                <button onclick="refreshQueue()" class="btn-refresh">🔄</button>
            </div>
            
            <div id="chatQueue" class="queue-list">
                <div class="loading">Loading chats...</div>
            </div>
            
            <div class="queue-footer">
                <div class="agent-status">
                    <label>Status:</label>
                    <select id="agentStatus" onchange="updateAgentStatus(this.value)">
                        <option value="online">🟢 Online</option>
                        <option value="away">🟡 Away</option>
                        <option value="busy">🔴 Busy</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Chat Window -->
        <div class="chat-window">
            <div id="noChatSelected" class="empty-state">
                <div class="empty-icon">💬</div>
                <h3>No Chat Selected</h3>
                <p>Select a chat from the queue to start helping customers</p>
            </div>
            
            <div id="chatContainer" class="chat-container" style="display: none;">
                <div class="chat-window-header">
                    <div class="chat-info">
                        <h4 id="chatCustomerName"></h4>
                        <span id="chatCustomerEmail" class="customer-email"></span>
                    </div>
                    <div class="chat-actions">
                        <button onclick="closeChatSession()" class="btn-close-chat">Close Chat</button>
                    </div>
                </div>
                
                <div id="chatMessages" class="chat-messages-area"></div>
                
                <div class="chat-input-area">
                    <form id="sendMessageForm" onsubmit="sendMessage(event)">
                        <input type="hidden" id="currentChatId" value="">
                        <textarea id="messageInput" placeholder="Type your message..." rows="3" required></textarea>
                        <div class="input-actions">
                            <button type="submit" class="btn-send">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-console-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 20px;
    height: calc(100vh - 200px);
    min-height: 600px;
}

.chat-queue {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.queue-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.queue-header h3 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.badge {
    background: #3b82f6;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.btn-refresh {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    padding: 4px;
}

.queue-list {
    flex: 1;
    overflow-y: auto;
}

.queue-item {
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    transition: background 0.2s;
}

.queue-item:hover {
    background: #f9fafb;
}

.queue-item.active {
    background: #eff6ff;
    border-left: 3px solid #3b82f6;
}

.queue-item-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.queue-item-name {
    font-weight: 600;
    color: #1f2937;
}

.queue-item-time {
    font-size: 12px;
    color: #6b7280;
}

.queue-item-preview {
    font-size: 13px;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.queue-footer {
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
}

.agent-status label {
    display: block;
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 8px;
}

.agent-status select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.chat-window {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6b7280;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.chat-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.chat-window-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-info h4 {
    margin: 0 0 4px 0;
    font-size: 16px;
}

.customer-email {
    font-size: 13px;
    color: #6b7280;
}

.btn-close-chat {
    background: #ef4444;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.btn-close-chat:hover {
    background: #dc2626;
}

.chat-messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f9fafb;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.chat-message {
    padding: 12px 16px;
    border-radius: 8px;
    max-width: 70%;
}

.message-user {
    background: white;
    align-self: flex-start;
    border: 1px solid #e5e7eb;
}

.message-agent {
    background: #3b82f6;
    color: white;
    align-self: flex-end;
}

.message-system {
    background: #f3f4f6;
    color: #6b7280;
    align-self: center;
    font-size: 13px;
    font-style: italic;
}

.message-header {
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 4px;
    opacity: 0.8;
}

.message-time {
    font-size: 11px;
    opacity: 0.7;
    margin-top: 4px;
}

.chat-input-area {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    background: white;
}

#messageInput {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    resize: vertical;
    font-family: inherit;
    font-size: 14px;
}

.input-actions {
    margin-top: 12px;
    display: flex;
    justify-content: flex-end;
}

.btn-send {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.btn-send:hover {
    background: #2563eb;
}

.loading {
    padding: 20px;
    text-align: center;
    color: #6b7280;
}

@media (max-width: 1024px) {
    .chat-console-layout {
        grid-template-columns: 1fr;
        height: auto;
    }
    
    .chat-queue {
        max-height: 300px;
    }
}
</style>

<script>
let currentChatId = null;
let pollingInterval = null;

// Load active chats on page load
document.addEventListener('DOMContentLoaded', function() {
    loadActiveChats();
    setInterval(loadActiveChats, 10000); // Refresh every 10 seconds
});

async function loadActiveChats() {
    try {
        const response = await fetch('/api/chat/admin_queue.php');
        const data = await response.json();
        
        if (data.success) {
            displayChatQueue(data.chats);
            document.getElementById('activeCount').textContent = data.chats.length;
        }
    } catch (error) {
        console.error('Error loading chats:', error);
    }
}

function displayChatQueue(chats) {
    const queueList = document.getElementById('chatQueue');
    
    if (chats.length === 0) {
        queueList.innerHTML = '<div class="loading">No active chats</div>';
        return;
    }
    
    queueList.innerHTML = chats.map(chat => `
        <div class="queue-item ${chat.id === currentChatId ? 'active' : ''}" onclick="selectChat(${chat.id})">
            <div class="queue-item-header">
                <span class="queue-item-name">${escapeHtml(chat.name || chat.email || 'Guest')}</span>
                <span class="queue-item-time">${formatTime(chat.created_at)}</span>
            </div>
            <div class="queue-item-preview">${escapeHtml(chat.last_message || 'No messages yet')}</div>
        </div>
    `).join('');
}

async function selectChat(chatId) {
    currentChatId = chatId;
    document.getElementById('currentChatId').value = chatId;
    
    // Hide empty state, show chat container
    document.getElementById('noChatSelected').style.display = 'none';
    document.getElementById('chatContainer').style.display = 'flex';
    
    // Load chat details and messages
    await loadChatDetails(chatId);
    await loadChatMessages(chatId);
    
    // Start polling for new messages
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(() => loadChatMessages(chatId), 3000);
    
    // Assign chat to current agent if not assigned
    assignChat(chatId);
}

async function loadChatDetails(chatId) {
    try {
        const response = await fetch(`/api/chat/details.php?chat_id=${chatId}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('chatCustomerName').textContent = data.chat.name || 'Guest User';
            document.getElementById('chatCustomerEmail').textContent = data.chat.email || '';
        }
    } catch (error) {
        console.error('Error loading chat details:', error);
    }
}

async function loadChatMessages(chatId) {
    try {
        const response = await fetch(`/api/chat/poll.php?chat_id=${chatId}&mark_read=1`);
        const data = await response.json();
        
        if (data.success) {
            displayMessages(data.messages);
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

function displayMessages(messages) {
    const messagesArea = document.getElementById('chatMessages');
    messagesArea.innerHTML = messages.map(msg => `
        <div class="chat-message message-${msg.sender}">
            <div class="message-header">${getSenderName(msg.sender)}</div>
            <div class="message-text">${escapeHtml(msg.message)}</div>
            <div class="message-time">${formatTime(msg.created_at)}</div>
        </div>
    `).join('');
    messagesArea.scrollTop = messagesArea.scrollHeight;
}

async function sendMessage(event) {
    event.preventDefault();
    
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    const chatId = document.getElementById('currentChatId').value;
    
    if (!message || !chatId) return;
    
    try {
        const response = await fetch('/api/chat/send.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                chat_id: chatId,
                message: message,
                sender: 'agent'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            input.value = '';
            loadChatMessages(chatId);
        }
    } catch (error) {
        console.error('Error sending message:', error);
        alert('Failed to send message');
    }
}

async function assignChat(chatId) {
    try {
        const formData = new FormData();
        formData.append('action', 'assign');
        formData.append('chat_id', chatId);
        
        await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
    } catch (error) {
        console.error('Error assigning chat:', error);
    }
}

async function closeChatSession() {
    if (!currentChatId) return;
    
    if (!confirm('Are you sure you want to close this chat?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'close');
        formData.append('chat_id', currentChatId);
        
        await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        // Clear selection
        currentChatId = null;
        document.getElementById('noChatSelected').style.display = 'flex';
        document.getElementById('chatContainer').style.display = 'none';
        
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
        
        loadActiveChats();
    } catch (error) {
        console.error('Error closing chat:', error);
        alert('Failed to close chat');
    }
}

function refreshQueue() {
    loadActiveChats();
}

function updateAgentStatus(status) {
    // Implement agent status update if needed
    console.log('Agent status updated to:', status);
}

function getSenderName(sender) {
    const names = {
        'user': 'Customer',
        'agent': 'You',
        'ai': 'Feza AI',
        'system': 'System'
    };
    return names[sender] || sender;
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`;
    return date.toLocaleDateString();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
