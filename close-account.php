<?php
/**
 * Close Account Page
 * Request account closure
 */

require_once __DIR__ . '/includes/init.php';

// Require user to be logged in
Session::requireLogin();

$error = '';
$success = '';
$user = new User();
$userInfo = $user->find(Session::getUserId());

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $reason = sanitizeInput($_POST['reason'] ?? '');
        $additional_comments = sanitizeInput($_POST['additional_comments'] ?? '');
        $confirm = isset($_POST['confirm_closure']);
        
        if (!$confirm) {
            $error = 'Please confirm that you understand the consequences of closing your account.';
        } elseif (empty($reason)) {
            $error = 'Please select a reason for closing your account.';
        } else {
            try {
                $db = db();
                $stmt = $db->prepare("INSERT INTO account_closure_requests (user_id, reason, additional_comments, status) VALUES (?, ?, ?, 'pending')");
                $stmt->execute([Session::getUserId(), $reason, $additional_comments]);
                
                // Send notification email to admin
                $adminEmail = defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : 'admin@fezamarket.com';
                $fromEmail = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'no-reply@fezamarket.com';
                $subject = "Account Closure Request - User ID: " . Session::getUserId();
                $message = "A user has requested to close their account.\n\n";
                $message .= "User: {$userInfo['first_name']} {$userInfo['last_name']} ({$userInfo['email']})\n";
                $message .= "Reason: {$reason}\n";
                $message .= "Comments: {$additional_comments}\n\n";
                $message .= "Please review this request in the admin panel.";
                
                @mail($adminEmail, $subject, $message, "From: {$fromEmail}");
                
                $success = 'Your account closure request has been submitted. Our team will review it within 1-2 business days and contact you via email.';
            } catch (Exception $e) {
                $error = 'Failed to submit closure request. Please try again or contact support.';
                error_log("Account closure error: " . $e->getMessage());
            }
        }
    }
}

$page_title = 'Close Account';
includeHeader($page_title);
?>

<div class="container">
    <div class="page-header">
        <h1>Close Your Account</h1>
        <p class="subtitle">We're sorry to see you go</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
            <p class="mt-3"><a href="/account.php" class="btn btn-outline">Return to Account</a></p>
        </div>
    <?php else: ?>

    <div class="warning-box">
        <h2>⚠️ Important Warning</h2>
        <p>Before you proceed, please understand the consequences of closing your account:</p>
        <ul>
            <li><strong>Permanent Deletion:</strong> Your account and all associated data will be permanently deleted after 30 days.</li>
            <li><strong>Order History:</strong> You will lose access to your complete order history and receipts.</li>
            <li><strong>Saved Items:</strong> Your wishlist, saved searches, and cart items will be lost.</li>
            <li><strong>Seller Account:</strong> If you're a seller, all your listings will be removed.</li>
            <li><strong>Active Orders:</strong> You cannot close your account if you have pending or active orders.</li>
            <li><strong>Payment Information:</strong> All saved payment methods will be removed.</li>
            <li><strong>Reviews & Ratings:</strong> Your reviews and seller ratings will be anonymized but not deleted.</li>
            <li><strong>Email Address:</strong> Your email address will be released and can be re-registered after 90 days.</li>
        </ul>
    </div>

    <div class="content-card">
        <h2>Before You Go</h2>
        <p>Have you considered these alternatives?</p>
        <div class="alternatives">
            <div class="alternative-item">
                <h3>📧 Update Email Preferences</h3>
                <p>Reduce email notifications instead of closing your account.</p>
                <a href="/account.php?section=notifications" class="btn btn-outline">Manage Notifications</a>
            </div>
            <div class="alternative-item">
                <h3>🔒 Take a Break</h3>
                <p>Deactivate your account temporarily instead of permanent deletion.</p>
                <a href="/account.php?section=security" class="btn btn-outline">Account Settings</a>
            </div>
            <div class="alternative-item">
                <h3>💬 Contact Support</h3>
                <p>Talk to our team about any issues you're experiencing.</p>
                <a href="/contact.php" class="btn btn-outline">Contact Us</a>
            </div>
        </div>
    </div>

    <div class="content-card">
        <h2>Request Account Closure</h2>
        <p>If you still wish to close your account, please fill out this form:</p>
        
        <form method="post" class="closure-form">
            <?php echo csrfTokenInput(); ?>
            
            <div class="form-group">
                <label for="reason">Reason for Closing Account *</label>
                <select name="reason" id="reason" class="form-control" required>
                    <option value="">Please select a reason</option>
                    <option value="Not using anymore">I'm not using FezaMarket anymore</option>
                    <option value="Privacy concerns">Privacy concerns</option>
                    <option value="Too many emails">Receiving too many emails</option>
                    <option value="Found alternative">Found an alternative service</option>
                    <option value="Poor experience">Poor customer experience</option>
                    <option value="Technical issues">Technical issues with the platform</option>
                    <option value="Other">Other reason</option>
                </select>
            </div>

            <div class="form-group">
                <label for="additional_comments">Additional Comments</label>
                <textarea name="additional_comments" id="additional_comments" class="form-control" rows="4" placeholder="Please tell us more about why you're leaving (optional)"></textarea>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="confirm_closure" required>
                    <span>I understand that this action is permanent and my account will be deleted after 30 days. I will lose access to all my data, order history, and saved items.</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-danger">Request Account Closure</button>
                <a href="/account.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>

    <?php endif; ?>
</div>

<style>
.page-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e5e7eb;
}

.page-header h1 {
    margin: 0 0 10px 0;
    color: #1f2937;
}

.subtitle {
    color: #6b7280;
    font-size: 16px;
    margin: 0;
}

.warning-box {
    background: #fef3c7;
    border: 2px solid #f59e0b;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 30px;
}

.warning-box h2 {
    color: #92400e;
    margin-top: 0;
    margin-bottom: 15px;
}

.warning-box ul {
    margin: 15px 0;
    padding-left: 20px;
}

.warning-box li {
    margin-bottom: 10px;
    color: #78350f;
}

.content-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 30px;
    margin-bottom: 30px;
}

.content-card h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #1f2937;
}

.alternatives {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.alternative-item {
    padding: 20px;
    background: #f9fafb;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

.alternative-item h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #374151;
    font-size: 16px;
}

.alternative-item p {
    margin-bottom: 15px;
    color: #6b7280;
    font-size: 14px;
}

.closure-form {
    max-width: 600px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #374151;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin-top: 3px;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-label span {
    color: #374151;
    line-height: 1.5;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    gap: 10px;
}

.btn-danger {
    background: #ef4444;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
}

.btn-danger:hover {
    background: #dc2626;
}

.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.mt-3 {
    margin-top: 15px;
}

@media (max-width: 768px) {
    .alternatives {
        grid-template-columns: 1fr;
    }
}
</style>

<?php includeFooter(); ?>
