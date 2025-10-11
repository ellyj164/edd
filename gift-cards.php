<?php
/**
 * Gift Cards Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'FezaMarket Gift Cards';
includeHeader($page_title);
?>

<div class="container">
    <!-- Gift Cards Header -->
    <div class="gift-cards-header">
        <h1>FezaMarket Gift Cards</h1>
        <p>The perfect gift for any occasion</p>
    </div>

    <!-- Gift Card Options -->
    <section class="gift-card-options">
        <div class="gift-card-types">
            <div class="gift-card-type active" data-type="digital">
                <h3>🎁 Digital Gift Card</h3>
                <p>Delivered instantly via email</p>
            </div>
            <div class="gift-card-type" data-type="physical">
                <h3>💳 Physical Gift Card</h3>
                <p>Mailed to recipient (3-5 business days)</p>
            </div>
        </div>

        <!-- Digital Gift Card Form -->
        <div class="gift-card-form" id="digitalForm">
            <div class="form-section">
                <h2>Choose Amount</h2>
                <div class="amount-options">
                    <button class="amount-btn" data-amount="25">$25</button>
                    <button class="amount-btn" data-amount="50">$50</button>
                    <button class="amount-btn active" data-amount="100">$100</button>
                    <button class="amount-btn" data-amount="150">$150</button>
                    <button class="amount-btn" data-amount="200">$200</button>
                    <div class="custom-amount">
                        <input type="number" id="customAmount" placeholder="Custom amount" min="5" max="1000">
                        <span class="amount-range">$5 - $1,000</span>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2>Design Your Gift Card</h2>
                <div class="design-options">
                    <div class="design-card active" data-design="birthday">
                        <div class="design-preview birthday-design">
                            <span>🎂</span>
                            <p>Happy Birthday</p>
                        </div>
                    </div>
                    <div class="design-card" data-design="holiday">
                        <div class="design-preview holiday-design">
                            <span>🎄</span>
                            <p>Happy Holidays</p>
                        </div>
                    </div>
                    <div class="design-card" data-design="congratulations">
                        <div class="design-preview congrats-design">
                            <span>🎉</span>
                            <p>Congratulations</p>
                        </div>
                    </div>
                    <div class="design-card" data-design="thankyou">
                        <div class="design-preview thankyou-design">
                            <span>💐</span>
                            <p>Thank You</p>
                        </div>
                    </div>
                    <div class="design-card" data-design="generic">
                        <div class="design-preview generic-design">
                            <span>🎁</span>
                            <p>For You</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2>Recipient Information</h2>
                <div class="recipient-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="recipientName">Recipient Name *</label>
                            <input type="text" id="recipientName" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="recipientEmail">Recipient Email *</label>
                            <input type="email" id="recipientEmail" required class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="personalMessage">Personal Message (Optional)</label>
                        <textarea id="personalMessage" rows="4" class="form-control" 
                                  placeholder="Write a personal message to the recipient..."></textarea>
                        <div class="char-count">0/500 characters</div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="senderName">Your Name *</label>
                            <input type="text" id="senderName" required class="form-control"
                                   value="<?php echo Session::isLoggedIn() ? htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="senderEmail">Your Email *</label>
                            <input type="email" id="senderEmail" required class="form-control"
                                   value="<?php echo Session::isLoggedIn() ? htmlspecialchars($current_user['email']) : ''; ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="deliveryDate">Delivery Date</label>
                            <input type="date" id="deliveryDate" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                            <small>Leave blank to send immediately</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gift Card Preview -->
            <div class="form-section">
                <h2>Preview</h2>
                <div class="gift-card-preview">
                    <div class="preview-card birthday-theme">
                        <div class="card-header">
                            <span class="card-icon">🎂</span>
                            <h3>Happy Birthday</h3>
                        </div>
                        <div class="card-amount">$100.00</div>
                        <div class="card-message">
                            <p id="previewMessage">Hope you find something amazing!</p>
                        </div>
                        <div class="card-footer">
                            <p>From: <span id="previewSender">Your Name</span></p>
                            <p>To: <span id="previewRecipient">Recipient Name</span></p>
                        </div>
                        <div class="feza-branding">FezaMarket</div>
                    </div>
                </div>
            </div>

            <!-- Purchase Button -->
            <div class="purchase-section">
                <div class="total-amount">
                    <span>Total: $<span id="totalAmount">100.00</span></span>
                </div>
                <?php if (Session::isLoggedIn()): ?>
                    <button class="btn btn-large btn-primary" onclick="purchaseGiftCard()">
                        Purchase Gift Card
                    </button>
                <?php else: ?>
                    <a href="/login.php?return=/gift-cards.php" class="btn btn-large btn-primary">
                        Sign In to Purchase
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Gift Card Benefits -->
    <section class="gift-card-benefits">
        <h2>Why Choose FezaMarket Gift Cards?</h2>
        <div class="benefits-grid">
            <div class="benefit-item">
                <div class="benefit-icon">⚡</div>
                <h3>Instant Delivery</h3>
                <p>Digital gift cards are delivered instantly to the recipient's email</p>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon">🛒</div>
                <h3>Shop Anywhere</h3>
                <p>Use gift cards on millions of products across all FezaMarket categories</p>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon">📅</div>
                <h3>Never Expires</h3>
                <p>FezaMarket gift cards never expire and have no hidden fees</p>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon">💳</div>
                <h3>Easy to Use</h3>
                <p>Simply enter the gift card code at checkout for instant savings</p>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="gift-card-faq">
        <h2>Frequently Asked Questions</h2>
        <div class="faq-list">
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    How do I redeem a gift card?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    Enter your gift card code during checkout in the "Gift Cards & Promotional Codes" section. The balance will be applied to your order automatically.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    Do gift cards expire?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    No, FezaMarket gift cards never expire and there are no fees for purchasing or using them.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    Can I check my gift card balance?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    Yes, you can check your gift card balance anytime by visiting our Gift Card Balance Check page or during checkout.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    Can I return a gift card?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    Gift cards are generally non-returnable, but contact our customer service if you have concerns about your purchase.
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.gift-cards-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 0;
}

.gift-cards-header h1 {
    font-size: 36px;
    color: #1f2937;
    margin-bottom: 10px;
}

.gift-cards-header p {
    font-size: 18px;
    color: #6b7280;
}

.gift-card-types {
    display: flex;
    gap: 20px;
    margin-bottom: 40px;
    justify-content: center;
}

.gift-card-type {
    background: white;
    padding: 20px 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    cursor: pointer;
    text-align: center;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.gift-card-type.active {
    border-color: #0654ba;
    background: #f0f7ff;
}

.gift-card-type h3 {
    color: #1f2937;
    margin-bottom: 5px;
}

.gift-card-type p {
    color: #6b7280;
    font-size: 14px;
}

.gift-card-form {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.form-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e5e7eb;
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h2 {
    color: #1f2937;
    margin-bottom: 20px;
}

.amount-options {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.amount-btn {
    background: white;
    border: 2px solid #d1d5db;
    padding: 15px 25px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.amount-btn:hover,
.amount-btn.active {
    border-color: #0654ba;
    background: #f0f7ff;
    color: #0654ba;
}

.custom-amount {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.custom-amount input {
    width: 150px;
    padding: 15px;
    border: 2px solid #d1d5db;
    border-radius: 8px;
}

.amount-range {
    font-size: 12px;
    color: #6b7280;
}

.design-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.design-card {
    cursor: pointer;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.design-card.active {
    border-color: #0654ba;
}

.design-preview {
    padding: 20px;
    text-align: center;
    color: white;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 10px;
}

.birthday-design { background: linear-gradient(135deg, #ff6b9d, #ff8a9b); }
.holiday-design { background: linear-gradient(135deg, #00a651, #4caf50); }
.congrats-design { background: linear-gradient(135deg, #ffd700, #ffb300); }
.thankyou-design { background: linear-gradient(135deg, #e91e63, #ff5722); }
.generic-design { background: linear-gradient(135deg, #0654ba, #1e40af); }

.design-preview span {
    font-size: 24px;
}

.design-preview p {
    font-weight: 600;
    margin: 0;
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
}

.char-count {
    text-align: right;
    font-size: 12px;
    color: #6b7280;
    margin-top: 5px;
}

.gift-card-preview {
    display: flex;
    justify-content: center;
}

.preview-card {
    width: 350px;
    padding: 30px;
    border-radius: 12px;
    color: white;
    text-align: center;
    position: relative;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}

.birthday-theme { background: linear-gradient(135deg, #ff6b9d, #ff8a9b); }

.card-header {
    margin-bottom: 20px;
}

.card-icon {
    font-size: 36px;
    display: block;
    margin-bottom: 10px;
}

.card-header h3 {
    font-size: 24px;
    margin: 0;
}

.card-amount {
    font-size: 48px;
    font-weight: bold;
    margin: 20px 0;
}

.card-message {
    background: rgba(255,255,255,0.2);
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
    font-style: italic;
}

.card-footer p {
    margin: 5px 0;
    font-size: 14px;
}

.feza-branding {
    position: absolute;
    bottom: 15px;
    right: 20px;
    font-size: 12px;
    opacity: 0.7;
    font-weight: bold;
}

.purchase-section {
    text-align: center;
    padding-top: 30px;
    border-top: 1px solid #e5e7eb;
}

.total-amount {
    font-size: 24px;
    font-weight: bold;
    color: #1f2937;
    margin-bottom: 20px;
}

.gift-card-benefits {
    margin: 60px 0;
}

.gift-card-benefits h2 {
    text-align: center;
    color: #1f2937;
    margin-bottom: 40px;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.benefit-item {
    text-align: center;
    padding: 30px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.benefit-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.benefit-item h3 {
    color: #1f2937;
    margin-bottom: 10px;
}

.benefit-item p {
    color: #6b7280;
}

.gift-card-faq h2 {
    color: #1f2937;
    margin-bottom: 30px;
    text-align: center;
}

.faq-list {
    max-width: 800px;
    margin: 0 auto;
}

.faq-item {
    border-bottom: 1px solid #e5e7eb;
    padding: 20px 0;
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    font-weight: 600;
    color: #1f2937;
}

.faq-toggle {
    font-size: 20px;
    color: #0654ba;
    font-weight: bold;
}

.faq-answer {
    display: none;
    color: #6b7280;
    margin-top: 15px;
    line-height: 1.6;
}

.faq-answer.active {
    display: block;
}

@media (max-width: 768px) {
    .gift-card-types {
        flex-direction: column;
    }
    
    .amount-options {
        justify-content: center;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .design-options {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .preview-card {
        width: 300px;
    }
}
</style>

<script>
let selectedAmount = 100;
let selectedDesign = 'birthday';

// Amount selection
document.querySelectorAll('.amount-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        selectedAmount = parseInt(this.dataset.amount);
        updatePreview();
    });
});

// Custom amount input
document.getElementById('customAmount').addEventListener('input', function() {
    document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('active'));
    selectedAmount = parseFloat(this.value) || 0;
    updatePreview();
});

// Design selection
document.querySelectorAll('.design-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.design-card').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        selectedDesign = this.dataset.design;
        updatePreview();
    });
});

// Form inputs
document.getElementById('personalMessage').addEventListener('input', updatePreview);
document.getElementById('senderName').addEventListener('input', updatePreview);
document.getElementById('recipientName').addEventListener('input', updatePreview);

// Character count
document.getElementById('personalMessage').addEventListener('input', function() {
    const count = this.value.length;
    document.querySelector('.char-count').textContent = `${count}/500 characters`;
});

function updatePreview() {
    const preview = document.querySelector('.preview-card');
    const amount = document.getElementById('totalAmount');
    const message = document.getElementById('previewMessage');
    const sender = document.getElementById('previewSender');
    const recipient = document.getElementById('previewRecipient');
    
    // Update amount
    amount.textContent = selectedAmount.toFixed(2);
    document.querySelector('.card-amount').textContent = `$${selectedAmount.toFixed(2)}`;
    
    // Update message
    const personalMsg = document.getElementById('personalMessage').value;
    message.textContent = personalMsg || 'Hope you find something amazing!';
    
    // Update sender
    const senderName = document.getElementById('senderName').value;
    sender.textContent = senderName || 'Your Name';
    
    // Update recipient
    const recipientName = document.getElementById('recipientName').value;
    recipient.textContent = recipientName || 'Recipient Name';
    
    // Update design theme
    const themes = {
        'birthday': { class: 'birthday-theme', icon: '🎂', title: 'Happy Birthday' },
        'holiday': { class: 'holiday-theme', icon: '🎄', title: 'Happy Holidays' },
        'congratulations': { class: 'congrats-theme', icon: '🎉', title: 'Congratulations' },
        'thankyou': { class: 'thankyou-theme', icon: '💐', title: 'Thank You' },
        'generic': { class: 'generic-theme', icon: '🎁', title: 'For You' }
    };
    
    const theme = themes[selectedDesign];
    preview.className = `preview-card ${theme.class}`;
    document.querySelector('.card-icon').textContent = theme.icon;
    document.querySelector('.card-header h3').textContent = theme.title;
}

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

function purchaseGiftCard() {
    // Validate form data
    const recipientName = document.getElementById('recipientName')?.value.trim();
    const recipientEmail = document.getElementById('recipientEmail')?.value.trim();
    const senderName = document.getElementById('senderName')?.value.trim();
    const senderEmail = document.getElementById('senderEmail')?.value.trim();
    const personalMessage = document.getElementById('personalMessage')?.value.trim() || '';
    
    // Validation
    if (!recipientName || !recipientEmail) {
        alert('Please fill in all recipient information.');
        return;
    }
    
    if (!senderName || !senderEmail) {
        alert('Please fill in all sender information.');
        return;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(recipientEmail)) {
        alert('Please enter a valid recipient email address.');
        return;
    }
    if (!emailRegex.test(senderEmail)) {
        alert('Please enter a valid sender email address.');
        return;
    }
    
    // Get selected amount and design
    const selectedAmount = document.querySelector('.amount-btn.active')?.dataset.amount || 
                          document.getElementById('customAmount')?.value;
    
    if (!selectedAmount || selectedAmount < 5 || selectedAmount > 1000) {
        alert('Please select a valid amount between $5 and $1000.');
        return;
    }
    
    const selectedDesign = document.querySelector('.design-card.active')?.dataset.design || 'generic';
    const cardType = document.querySelector('.gift-card-type.active')?.dataset.type || 'digital';
    
    // Create form data
    const formData = {
        card_type: cardType,
        amount: selectedAmount,
        design: selectedDesign,
        recipient_name: recipientName,
        recipient_email: recipientEmail,
        sender_name: senderName,
        sender_email: senderEmail,
        personal_message: personalMessage
    };
    
    // Show loading state
    const purchaseBtn = document.querySelector('.purchase-btn');
    const originalText = purchaseBtn.innerHTML;
    purchaseBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    purchaseBtn.disabled = true;
    
    // Send to server
    fetch('/api/gift-cards/purchase.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to payment page
            window.location.href = data.payment_url || '/checkout.php?type=gift-card&id=' + data.gift_card_id;
        } else {
            alert(data.error || 'An error occurred. Please try again.');
            purchaseBtn.innerHTML = originalText;
            purchaseBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        purchaseBtn.innerHTML = originalText;
        purchaseBtn.disabled = false;
    });
}

// Initialize preview
updatePreview();
</script>

<?php includeFooter(); ?>