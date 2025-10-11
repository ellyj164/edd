<?php
/**
 * Contact Us Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!validateEmail($email)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // Save to database
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO contact_messages (name, email, subject, message, category, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'unread', NOW())
            ");
            $stmt->execute([$name, $email, $subject, $message, $category]);
            
            // Send email notification to support team
            try {
                $mailer = new Mailer();
                $mailer->send(
                    SUPPORT_EMAIL,
                    'New Contact Form Submission',
                    "New contact form submission from {$name} ({$email})\n\nSubject: {$subject}\n\nMessage:\n{$message}"
                );
            } catch (Exception $e) {
                Logger::error('Failed to send contact notification email: ' . $e->getMessage());
            }
            
            $success_message = 'Thank you for contacting us! We\'ll get back to you within 24 hours.';
            
            // Clear form data on success
            $name = $email = $subject = $message = $category = '';
        } catch (Exception $e) {
            Logger::error('Contact form error: ' . $e->getMessage());
            $error_message = 'An error occurred while submitting your message. Please try again.';
        }
    }
}

$page_title = 'Contact Us - FezaMarket Support';
includeHeader($page_title);
?>

<div class="container">
    <!-- Contact Header -->
    <div class="contact-header">
        <h1>Contact Us</h1>
        <p>We're here to help! Get in touch with our support team</p>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✅</span>
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <span class="alert-icon">❌</span>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="contact-content">
        <!-- Contact Form -->
        <div class="contact-form-section">
            <div class="form-card">
                <h2>Send us a message</h2>
                <form method="POST" class="contact-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                                   required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                   required class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" class="form-control">
                                <option value="">Select a category</option>
                                <option value="order_inquiry" <?php echo ($category ?? '') === 'order_inquiry' ? 'selected' : ''; ?>>Order Inquiry</option>
                                <option value="product_question" <?php echo ($category ?? '') === 'product_question' ? 'selected' : ''; ?>>Product Question</option>
                                <option value="return_refund" <?php echo ($category ?? '') === 'return_refund' ? 'selected' : ''; ?>>Return/Refund</option>
                                <option value="technical_issue" <?php echo ($category ?? '') === 'technical_issue' ? 'selected' : ''; ?>>Technical Issue</option>
                                <option value="seller_inquiry" <?php echo ($category ?? '') === 'seller_inquiry' ? 'selected' : ''; ?>>Seller Inquiry</option>
                                <option value="account_help" <?php echo ($category ?? '') === 'account_help' ? 'selected' : ''; ?>>Account Help</option>
                                <option value="feedback" <?php echo ($category ?? '') === 'feedback' ? 'selected' : ''; ?>>Feedback</option>
                                <option value="other" <?php echo ($category ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" 
                                   value="<?php echo htmlspecialchars($subject ?? ''); ?>" 
                                   required class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" rows="6" 
                                  required class="form-control"><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        <div class="form-help">Please provide as much detail as possible to help us assist you better.</div>
                    </div>
                    
                    <!-- Additional Information for Logged-in Users -->
                    <?php if (Session::isLoggedIn()): ?>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="include_account_info" value="1" checked>
                                Include my account information to help resolve this issue faster
                            </label>
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary btn-large">Send Message</button>
                </form>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="contact-info-section">
            <!-- Quick Contact -->
            <div class="info-card">
                <h3>Quick Contact</h3>
                <div class="contact-methods">
                    <div class="contact-method">
                        <span class="method-icon">📞</span>
                        <div class="method-info">
                            <strong>Phone Support</strong>
                            <p>1-800-FEZA-HELP (1-800-339-2435)</p>
                            <span class="hours">Mon-Fri: 9AM-6PM EST</span>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <span class="method-icon">💬</span>
                        <div class="method-info">
                            <strong>Live Chat</strong>
                            <p>Chat with our support team</p>
                            <button class="chat-btn" onclick="startLiveChat()">Start Chat</button>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <span class="method-icon">📧</span>
                        <div class="method-info">
                            <strong>Email Support</strong>
                            <p>support@fezamarket.com</p>
                            <span class="response-time">Response within 24 hours</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Frequently Asked Questions -->
            <div class="info-card">
                <h3>Quick Answers</h3>
                <div class="quick-faqs">
                    <div class="quick-faq">
                        <strong>How do I track my order?</strong>
                        <p>Go to "My Orders" in your account or use our <a href="/help.php">order tracking tool</a>.</p>
                    </div>
                    <div class="quick-faq">
                        <strong>What's your return policy?</strong>
                        <p>Most items can be returned within 30 days. <a href="/returns.php">Learn more</a>.</p>
                    </div>
                    <div class="quick-faq">
                        <strong>How do I become a seller?</strong>
                        <p><a href="<?php echo getSellingUrl(); ?>">Start selling</a> - it's free to get started!</p>
                    </div>
                </div>
                <a href="/help.php" class="help-center-link">Visit Help Center →</a>
            </div>

            <!-- Business Information -->
            <div class="info-card">
                <h3>Business Information</h3>
                <div class="business-info">
                    <p><strong>FezaMarket Inc.</strong></p>
                    <p>123 Commerce Street<br>
                    Business City, BC 12345<br>
                    United States</p>
                    
                    <div class="business-hours">
                        <h4>Customer Service Hours</h4>
                        <ul>
                            <li>Monday - Friday: 9:00 AM - 6:00 PM EST</li>
                            <li>Saturday: 10:00 AM - 4:00 PM EST</li>
                            <li>Sunday: Closed</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Help Resources -->
    <section class="help-resources">
        <h2>Other Ways to Get Help</h2>
        <div class="resources-grid">
            <a href="/help.php" class="resource-card">
                <span class="resource-icon">📚</span>
                <h3>Help Center</h3>
                <p>Browse our comprehensive help articles and guides</p>
            </a>
            
            <a href="/community.php" class="resource-card">
                <span class="resource-icon">👥</span>
                <h3>Community Forum</h3>
                <p>Connect with other users and get community support</p>
            </a>
            
            <a href="/seller-center.php" class="resource-card">
                <span class="resource-icon">🏪</span>
                <h3>Seller Center</h3>
                <p>Resources and support specifically for sellers</p>
            </a>
            
            <a href="/safety.php" class="resource-card">
                <span class="resource-icon">🛡️</span>
                <h3>Safety Center</h3>
                <p>Learn about safe shopping and selling practices</p>
            </a>
        </div>
    </section>
</div>

<!-- Live Chat Widget -->
<div id="liveChatWidget" class="live-chat-widget" style="display: none;">
    <div class="chat-header">
        <h4>Live Chat Support</h4>
        <button onclick="closeLiveChat()" class="chat-close-btn">✕</button>
    </div>
    <div id="chatMessages" class="chat-messages"></div>
    <form id="chatForm" class="chat-input-form">
        <input type="text" id="chatInput" placeholder="Type your message..." required>
        <button type="submit">Send</button>
    </form>
</div>

<style>
.contact-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 0;
}

.contact-header h1 {
    font-size: 36px;
    color: #1f2937;
    margin-bottom: 10px;
}

.contact-header p {
    font-size: 18px;
    color: #6b7280;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-icon {
    font-size: 18px;
}

.contact-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    margin-bottom: 60px;
}

.form-card, .info-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-bottom: 25px;
}

.form-card h2, .info-card h3 {
    color: #1f2937;
    margin-bottom: 25px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: #374151;
    font-weight: 600;
    margin-bottom: 8px;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #0654ba;
    box-shadow: 0 0 0 3px rgba(6, 84, 186, 0.1);
}

.form-help {
    font-size: 14px;
    color: #6b7280;
    margin-top: 5px;
}

.btn-primary {
    background: #0654ba;
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-primary:hover {
    background: #1e40af;
}

.btn-large {
    width: 100%;
}

.contact-methods {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.contact-method {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
}

.method-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.method-info strong {
    display: block;
    color: #1f2937;
    margin-bottom: 5px;
}

.method-info p {
    color: #374151;
    margin-bottom: 5px;
}

.hours, .response-time {
    font-size: 14px;
    color: #6b7280;
}

.chat-btn {
    background: #10b981;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
}

.quick-faqs {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.quick-faq strong {
    display: block;
    color: #1f2937;
    margin-bottom: 5px;
}

.quick-faq p {
    color: #6b7280;
    font-size: 14px;
}

.help-center-link {
    color: #0654ba;
    text-decoration: none;
    font-weight: 600;
    display: inline-block;
    margin-top: 15px;
}

.business-info p {
    color: #374151;
    margin-bottom: 5px;
}

.business-hours {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.business-hours h4 {
    color: #1f2937;
    margin-bottom: 10px;
}

.business-hours ul {
    list-style: none;
    padding: 0;
}

.business-hours li {
    color: #374151;
    margin-bottom: 5px;
    font-size: 14px;
}

.help-resources {
    text-align: center;
}

.help-resources h2 {
    font-size: 28px;
    color: #1f2937;
    margin-bottom: 30px;
}

.resources-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.resource-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-decoration: none;
    color: inherit;
    text-align: center;
    transition: transform 0.3s ease;
}

.resource-card:hover {
    transform: translateY(-3px);
}

.resource-icon {
    font-size: 36px;
    display: block;
    margin-bottom: 15px;
}

.resource-card h3 {
    color: #1f2937;
    margin-bottom: 8px;
}

.resource-card p {
    color: #6b7280;
    font-size: 14px;
}

/* Live Chat Widget Styles */
.live-chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 350px;
    height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    overflow: hidden;
}

.chat-header {
    background: #0654ba;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-header h4 {
    margin: 0;
    font-size: 16px;
}

.chat-close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    line-height: 1;
}

.chat-messages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #f9fafb;
}

.chat-message {
    padding: 10px 15px;
    border-radius: 8px;
    max-width: 80%;
    word-wrap: break-word;
}

.chat-message-user {
    background: #0654ba;
    color: white;
    align-self: flex-end;
    margin-left: auto;
}

.chat-message-agent,
.chat-message-ai,
.chat-message-system {
    background: white;
    color: #1f2937;
    align-self: flex-start;
    border: 1px solid #e5e7eb;
}

.chat-input-form {
    display: flex;
    padding: 15px;
    background: white;
    border-top: 1px solid #e5e7eb;
}

.chat-input-form input {
    flex: 1;
    padding: 10px 15px;
    border: 1px solid #d1d5db;
    border-radius: 20px;
    margin-right: 10px;
    font-size: 14px;
}

.chat-input-form button {
    background: #0654ba;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
}

.chat-input-form button:hover {
    background: #1e40af;
}

@media (max-width: 768px) {
    .contact-header h1 {
        font-size: 28px;
    }
    
    .contact-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .contact-method {
        flex-direction: column;
        text-align: center;
    }
    
    .resources-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function startLiveChat() {
    alert('Live chat will be available soon! For now, please use our contact form or phone support.');
}

// Live Chat Widget
function startLiveChat() {
    // Show chat widget
    const chatWidget = document.getElementById('liveChatWidget');
    if (chatWidget) {
        chatWidget.style.display = 'flex';
        initializeLiveChat();
    }
}

function closeLiveChat() {
    document.getElementById('liveChatWidget').style.display = 'none';
}

async function initializeLiveChat() {
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    
    // Get user info if logged in
    const userName = '<?php echo Session::isLoggedIn() ? $_SESSION['user_name'] ?? '' : ''; ?>';
    const userEmail = '<?php echo Session::isLoggedIn() ? $_SESSION['user_email'] ?? '' : ''; ?>';
    
    // Start chat session
    try {
        const response = await fetch('/api/chat/start.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                name: userName || document.getElementById('chatName')?.value || 'Guest',
                email: userEmail || document.getElementById('chatEmail')?.value || null,
                type: 'support'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.currentChatId = data.chat_id;
            // Start polling for messages
            pollChatMessages();
        }
    } catch (error) {
        console.error('Failed to start chat:', error);
        addChatMessage('system', 'Failed to connect. Please refresh and try again.');
    }
    
    // Handle message sending
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        
        if (!message || !window.currentChatId) return;
        
        // Add message to UI
        addChatMessage('user', message);
        input.value = '';
        
        // Send to server
        try {
            await fetch('/api/chat/send.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    chat_id: window.currentChatId,
                    message: message,
                    sender: 'user'
                })
            });
        } catch (error) {
            console.error('Failed to send message:', error);
        }
    });
}

function addChatMessage(sender, message) {
    const chatMessages = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message chat-message-${sender}`;
    messageDiv.textContent = message;
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

async function pollChatMessages() {
    if (!window.currentChatId) return;
    
    const lastMessageId = window.lastMessageId || 0;
    
    try {
        const response = await fetch(`/api/chat/poll.php?chat_id=${window.currentChatId}&last_message_id=${lastMessageId}&wait=20`);
        const data = await response.json();
        
        if (data.success && data.messages.length > 0) {
            data.messages.forEach(msg => {
                if (msg.sender !== 'user') { // Don't show our own messages again
                    addChatMessage(msg.sender, msg.message);
                }
            });
            window.lastMessageId = data.last_message_id;
        }
        
        // Continue polling if chat is active
        if (data.status === 'active') {
            setTimeout(pollChatMessages, 1000);
        }
    } catch (error) {
        console.error('Polling error:', error);
        setTimeout(pollChatMessages, 5000); // Retry after 5 seconds
    }
}

// Form validation
document.querySelector('.contact-form').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const subject = document.getElementById('subject').value.trim();
    const message = document.getElementById('message').value.trim();
    
    if (!name || !email || !subject || !message) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address.');
        return;
    }
});

// Auto-fill subject based on category selection
document.getElementById('category').addEventListener('change', function() {
    const subject = document.getElementById('subject');
    const category = this.value;
    
    if (category && !subject.value) {
        const subjects = {
            'order_inquiry': 'Question about my order',
            'product_question': 'Product inquiry',
            'return_refund': 'Return or refund request',
            'technical_issue': 'Technical problem',
            'seller_inquiry': 'Seller account question',
            'account_help': 'Account assistance needed',
            'feedback': 'Feedback about FezaMarket',
            'other': 'General inquiry'
        };
        
        if (subjects[category]) {
            subject.value = subjects[category];
        }
    }
});
</script>

<?php includeFooter(); ?>