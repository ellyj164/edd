<!-- Feza AI Assistant Widget - Floating Chat Button -->
<?php if (!defined('FEZA_AI_ENABLED') || FEZA_AI_ENABLED): ?>
<div id="fezaAiWidget" class="feza-ai-widget">
    <!-- Floating Button -->
    <button id="fezaAiButton" class="feza-ai-button" onclick="toggleFezaAi()">
        <span class="feza-ai-question-mark">?</span>
        <span class="feza-ai-badge" id="fezaAiBadge" style="display: none;">1</span>
    </button>
    
    <!-- Chat Window -->
    <div id="fezaAiChat" class="feza-ai-chat" style="display: none;">
        <div class="feza-ai-header">
            <div class="feza-ai-header-info">
                <h4>Feza AI Assistant</h4>
                <span class="feza-ai-status">Online</span>
            </div>
            <button onclick="closeFezaAi()" class="feza-ai-close">✕</button>
        </div>
        
        <div id="fezaAiMessages" class="feza-ai-messages">
            <div class="feza-ai-message feza-ai-message-ai">
                <div class="feza-ai-avatar">🤖</div>
                <div class="feza-ai-message-content">
                    <strong>Feza AI</strong>
                    <p>Hi! I'm Feza AI, your virtual shopping assistant. How can I help you today?</p>
                </div>
            </div>
        </div>
        
        <div class="feza-ai-suggestions" id="fezaAiSuggestions">
            <button onclick="askFezaAi('How do I track my order?')">📦 Track Order</button>
            <button onclick="askFezaAi('What is your return policy?')">🔄 Return Policy</button>
            <button onclick="askFezaAi('How do I contact support?')">💬 Contact Support</button>
        </div>
        
        <form id="fezaAiForm" class="feza-ai-input-form" onsubmit="sendFezaAiMessage(event)">
            <input type="text" id="fezaAiInput" placeholder="Ask me anything..." autocomplete="off" required>
            <button type="submit" id="fezaAiSendBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </form>
        
        <div class="feza-ai-footer">
            <small>Powered by Feza AI • <a href="#" onclick="connectToHuman(); return false;">Talk to a human</a></small>
        </div>
    </div>
</div>

<style>
/* Feza AI Widget Styles */
.feza-ai-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.feza-ai-button {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    position: relative;
}

.feza-ai-button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.feza-ai-button .feza-ai-question-mark {
    font-size: 2.25rem; /* 36px equivalent but responsive */
    font-weight: 700;
    line-height: 1;
    max-height: 38px; /* Prevent overflow on zoom */
}

.feza-ai-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
}

.feza-ai-chat {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 380px;
    max-width: calc(100vw - 40px);
    height: 600px;
    max-height: calc(100vh - 120px);
    background: white;
    border-radius: 16px;
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.feza-ai-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.feza-ai-header-info h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.feza-ai-status {
    font-size: 12px;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 6px;
}

.feza-ai-status::before {
    content: '';
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.feza-ai-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: background 0.2s;
}

.feza-ai-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.feza-ai-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: #f9fafb;
}

.feza-ai-message {
    display: flex;
    gap: 12px;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.feza-ai-message-user {
    flex-direction: row-reverse;
    align-self: flex-end;
}

.feza-ai-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.feza-ai-message-content {
    max-width: 75%;
    background: white;
    padding: 12px 16px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.feza-ai-message-user .feza-ai-message-content {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.feza-ai-message-content strong {
    display: block;
    font-size: 13px;
    margin-bottom: 4px;
    opacity: 0.8;
}

.feza-ai-message-user .feza-ai-message-content strong {
    display: none;
}

.feza-ai-message-content p {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    white-space: pre-wrap;
}

.feza-ai-suggestions {
    padding: 12px 20px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    background: white;
    border-top: 1px solid #e5e7eb;
}

.feza-ai-suggestions button {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    padding: 8px 14px;
    border-radius: 20px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.feza-ai-suggestions button:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
}

.feza-ai-input-form {
    display: flex;
    padding: 16px 20px;
    background: white;
    border-top: 1px solid #e5e7eb;
    gap: 12px;
}

.feza-ai-input-form input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 24px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}

.feza-ai-input-form input:focus {
    border-color: #667eea;
}

.feza-ai-input-form button {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.feza-ai-input-form button:hover:not(:disabled) {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.feza-ai-input-form button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.feza-ai-footer {
    padding: 12px 20px;
    text-align: center;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}

.feza-ai-footer small {
    color: #6b7280;
    font-size: 12px;
}

.feza-ai-footer a {
    color: #667eea;
    text-decoration: none;
}

.feza-ai-footer a:hover {
    text-decoration: underline;
}

.feza-ai-typing {
    display: flex;
    gap: 4px;
    padding: 12px 16px;
}

.feza-ai-typing span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #9ca3af;
    animation: typing 1.4s infinite;
}

.feza-ai-typing span:nth-child(2) {
    animation-delay: 0.2s;
}

.feza-ai-typing span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-8px); }
}

@media (max-width: 768px) {
    .feza-ai-widget {
        bottom: 10px;
        right: 10px;
    }
    
    .feza-ai-chat {
        width: calc(100vw - 20px);
        height: calc(100vh - 100px);
        bottom: 70px;
        right: -10px;
    }
    
    .feza-ai-button {
        width: 56px;
        height: 56px;
    }
}
</style>

<script>
// Feza AI Widget JavaScript
let fezaAiSessionId = null;
let fezaAiChatId = null;

function toggleFezaAi() {
    const chat = document.getElementById('fezaAiChat');
    const button = document.getElementById('fezaAiButton');
    
    if (chat.style.display === 'none') {
        chat.style.display = 'flex';
        button.style.display = 'none';
        document.getElementById('fezaAiInput').focus();
        
        // Initialize session if first time
        if (!fezaAiSessionId) {
            initFezaAiSession();
        }
    } else {
        chat.style.display = 'none';
        button.style.display = 'flex';
    }
}

function initFezaAiSession() {
    fezaAiSessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

async function sendFezaAiMessage(event) {
    event.preventDefault();
    
    const input = document.getElementById('fezaAiInput');
    const message = input.value.trim();
    const sendBtn = document.getElementById('fezaAiSendBtn');
    
    if (!message) return;
    
    // Add user message to UI
    addFezaAiMessage('user', message);
    input.value = '';
    sendBtn.disabled = true;
    
    // Hide suggestions after first message
    document.getElementById('fezaAiSuggestions').style.display = 'none';
    
    // Show typing indicator
    showFezaAiTyping();
    
    try {
        // If we're in human chat mode, send to the chat API instead
        if (fezaAiChatId) {
            const response = await fetch('/api/chat/send.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    chat_id: fezaAiChatId,
                    message: message
                })
            });
            
            const data = await response.json();
            hideFezaAiTyping();
            
            if (data.success) {
                // Message sent successfully - agent will respond via polling
                // No need to add a response here, wait for polling
            } else {
                addFezaAiMessage('system', 'Failed to send message. Please try again.');
            }
        } else {
            // AI mode - send to Feza AI
            const response = await fetch('/api/feza_ai.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    message: message,
                    session_id: fezaAiSessionId,
                    chat_id: fezaAiChatId
                })
            });
            
            const data = await response.json();
            
            hideFezaAiTyping();
            
            if (data.success && data.response) {
                addFezaAiMessage('ai', data.response);
            } else {
                addFezaAiMessage('ai', data.response || 'Sorry, I encountered an error. Please try again.');
            }
        }
    } catch (error) {
        hideFezaAiTyping();
        addFezaAiMessage('ai', 'Sorry, I\'m having trouble connecting. Please try again in a moment.');
    }
    
    sendBtn.disabled = false;
}

function askFezaAi(question) {
    document.getElementById('fezaAiInput').value = question;
    sendFezaAiMessage(new Event('submit'));
}

function addFezaAiMessage(sender, message) {
    const messagesContainer = document.getElementById('fezaAiMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `feza-ai-message feza-ai-message-${sender}`;
    
    const avatar = document.createElement('div');
    avatar.className = 'feza-ai-avatar';
    avatar.textContent = sender === 'user' ? '👤' : '🤖';
    
    const content = document.createElement('div');
    content.className = 'feza-ai-message-content';
    
    if (sender === 'ai') {
        const name = document.createElement('strong');
        name.textContent = 'Feza AI';
        content.appendChild(name);
    }
    
    const text = document.createElement('p');
    text.textContent = message;
    content.appendChild(text);
    
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(content);
    messagesContainer.appendChild(messageDiv);
    
    // Scroll to bottom
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function showFezaAiTyping() {
    const messagesContainer = document.getElementById('fezaAiMessages');
    const typingDiv = document.createElement('div');
    typingDiv.id = 'fezaAiTypingIndicator';
    typingDiv.className = 'feza-ai-message feza-ai-message-ai';
    
    const avatar = document.createElement('div');
    avatar.className = 'feza-ai-avatar';
    avatar.textContent = '🤖';
    
    const typing = document.createElement('div');
    typing.className = 'feza-ai-typing';
    typing.innerHTML = '<span></span><span></span><span></span>';
    
    typingDiv.appendChild(avatar);
    typingDiv.appendChild(typing);
    messagesContainer.appendChild(typingDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function hideFezaAiTyping() {
    const typing = document.getElementById('fezaAiTypingIndicator');
    if (typing) typing.remove();
}

async function connectToHuman() {
    try {
        showFezaAiTyping();
        
        const response = await fetch('/api/chat/start.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                type: 'support',
                message: 'User requested human agent from Feza AI'
            })
        });
        
        hideFezaAiTyping();
        const data = await response.json();
        
        if (data.success) {
            // Store the chat ID for human support
            fezaAiChatId = data.chat_id;
            
            // Update UI to show human support mode
            const header = document.querySelector('.feza-ai-header-info h4');
            const status = document.querySelector('.feza-ai-status');
            const footer = document.querySelector('.feza-ai-footer small');
            
            if (header) header.textContent = 'Live Support';
            if (status) status.textContent = 'Connecting...';
            if (footer) footer.innerHTML = 'Powered by FezaMarket Support • <a href="#" onclick="refreshHumanChat(); return false;">Refresh</a>';
            
            addFezaAiMessage('system', 'You are now connected to our support team. A human agent will respond shortly.');
            
            // Start polling for human agent messages
            startHumanChatPolling();
        } else {
            addFezaAiMessage('ai', 'Unable to connect to support right now. Please try our contact form or email support@fezamarket.com');
        }
    } catch (error) {
        hideFezaAiTyping();
        addFezaAiMessage('ai', 'Unable to connect to support right now. Please try our contact form or email support@fezamarket.com');
    }
}

let humanChatPollingInterval = null;

function startHumanChatPolling() {
    if (humanChatPollingInterval) {
        clearInterval(humanChatPollingInterval);
    }
    
    // Poll every 3 seconds for new messages
    humanChatPollingInterval = setInterval(async () => {
        if (!fezaAiChatId) return;
        
        try {
            const response = await fetch(`/api/chat/poll.php?chat_id=${fezaAiChatId}`, {
                method: 'GET',
                headers: {'Content-Type': 'application/json'}
            });
            
            const data = await response.json();
            
            if (data.success && data.messages && data.messages.length > 0) {
                // Add new messages from agent
                data.messages.forEach(msg => {
                    if (msg.sender === 'agent' || msg.sender === 'admin') {
                        addFezaAiMessage('agent', msg.message);
                    }
                });
                
                // Update status if agent is active
                if (data.status === 'active') {
                    const status = document.querySelector('.feza-ai-status');
                    if (status) status.textContent = 'Agent Online';
                }
            }
        } catch (error) {
            console.error('Failed to poll for messages:', error);
        }
    }, 3000);
}

function refreshHumanChat() {
    if (!fezaAiChatId) return;
    
    // Immediately fetch latest messages
    fetch(`/api/chat/poll.php?chat_id=${fezaAiChatId}`, {
        method: 'GET',
        headers: {'Content-Type': 'application/json'}
    }).then(response => response.json())
    .then(data => {
        if (data.success && data.messages) {
            data.messages.forEach(msg => {
                if (msg.sender === 'agent' || msg.sender === 'admin') {
                    addFezaAiMessage('agent', msg.message);
                }
            });
        }
    }).catch(error => {
        console.error('Failed to refresh chat:', error);
    });
}

function addFezaAiMessage(sender, message) {
    const messagesContainer = document.getElementById('fezaAiMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `feza-ai-message feza-ai-message-${sender}`;
    
    const avatar = document.createElement('div');
    avatar.className = 'feza-ai-avatar';
    if (sender === 'user') {
        avatar.textContent = '👤';
    } else if (sender === 'agent') {
        avatar.textContent = '👨‍💼';
    } else if (sender === 'system') {
        avatar.textContent = '🔔';
    } else {
        avatar.textContent = '🤖';
    }
    
    const content = document.createElement('div');
    content.className = 'feza-ai-message-content';
    
    if (sender === 'ai') {
        const name = document.createElement('strong');
        name.textContent = 'Feza AI';
        content.appendChild(name);
    } else if (sender === 'agent') {
        const name = document.createElement('strong');
        name.textContent = 'Support Agent';
        content.appendChild(name);
    } else if (sender === 'system') {
        const name = document.createElement('strong');
        name.textContent = 'System';
        content.appendChild(name);
    }
    
    const text = document.createElement('p');
    text.textContent = message;
    content.appendChild(text);
    
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(content);
    messagesContainer.appendChild(messageDiv);
    
    // Scroll to bottom
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Clean up polling when chat is closed
function closeFezaAi() {
    document.getElementById('fezaAiChat').style.display = 'none';
    document.getElementById('fezaAiButton').style.display = 'flex';
    
    // Stop polling if active
    if (humanChatPollingInterval) {
        clearInterval(humanChatPollingInterval);
        humanChatPollingInterval = null;
    }
}

// Show welcome message after a delay if user hasn't interacted
setTimeout(() => {
    if (!document.getElementById('fezaAiChat').style.display || document.getElementById('fezaAiChat').style.display === 'none') {
        const badge = document.getElementById('fezaAiBadge');
        badge.style.display = 'flex';
    }
}, 5000);
</script>
<?php endif; ?>
