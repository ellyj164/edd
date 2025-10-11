<?php
/**
 * Create Ad Campaign - Seller Interface
 */

require_once __DIR__ . '/../../includes/init.php';

Session::requireLogin();

$userId = Session::getUserId();
$userRole = Session::get('user_role');

// Check if user is seller
if ($userRole !== 'seller' && $userRole !== 'vendor') {
    redirect('/seller/dashboard.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = trim($_POST['title'] ?? '');
        $budget = floatval($_POST['budget'] ?? 0);
        $currency = $_POST['currency'] ?? 'USD';
        $startsAt = $_POST['starts_at'] ?? '';
        $endsAt = $_POST['ends_at'] ?? '';
        $targetCategory = $_POST['target_category'] ?? '';
        $targetProduct = $_POST['target_product'] ?? '';
        
        // Validation
        if (empty($title)) {
            throw new Exception('Ad title is required');
        }
        
        if ($budget <= 0) {
            throw new Exception('Budget must be greater than 0');
        }
        
        if (empty($startsAt) || empty($endsAt)) {
            throw new Exception('Start and end dates are required');
        }
        
        // Prepare target JSON
        $target = [
            'category' => $targetCategory,
            'product' => $targetProduct
        ];
        
        // Insert ad campaign
        $db = db();
        $stmt = $db->prepare("
            INSERT INTO seller_ads 
            (seller_id, title, budget, currency, starts_at, ends_at, target, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([
            $userId,
            $title,
            $budget,
            $currency,
            $startsAt,
            $endsAt,
            json_encode($target)
        ]);
        
        $success = 'Ad campaign created successfully! Pending admin review.';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get user's existing ads
$db = db();
$stmt = $db->prepare("
    SELECT * FROM seller_ads 
    WHERE seller_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$userId]);
$myAds = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Create Ad Campaign';
include __DIR__ . '/../../includes/header.php';
?>

<style>
.ads-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.ad-form {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-textarea {
    min-height: 100px;
    resize: vertical;
}

.btn-primary {
    background: #635bff;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
}

.btn-primary:hover {
    background: #5046e4;
}

.ads-list {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.ad-item {
    border-bottom: 1px solid #eee;
    padding: 15px 0;
}

.ad-item:last-child {
    border-bottom: none;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-running {
    background: #d1ecf1;
    color: #0c5460;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<div class="ads-container">
    <h1>Create Ad Campaign</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <div class="ad-form">
        <h2>New Campaign</h2>
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="title">Campaign Title *</label>
                <input type="text" id="title" name="title" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="budget">Budget *</label>
                <input type="number" id="budget" name="budget" class="form-input" step="0.01" min="10" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="currency">Currency</label>
                <select id="currency" name="currency" class="form-select">
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="GBP">GBP</option>
                    <option value="RWF">RWF</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="starts_at">Start Date *</label>
                <input type="datetime-local" id="starts_at" name="starts_at" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="ends_at">End Date *</label>
                <input type="datetime-local" id="ends_at" name="ends_at" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="target_category">Target Category (Optional)</label>
                <input type="text" id="target_category" name="target_category" class="form-input" placeholder="E.g., Electronics">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="target_product">Target Product ID (Optional)</label>
                <input type="number" id="target_product" name="target_product" class="form-input" placeholder="Leave blank for all products">
            </div>
            
            <button type="submit" class="btn-primary">Submit for Review</button>
        </form>
    </div>
    
    <div class="ads-list">
        <h2>My Ad Campaigns</h2>
        <?php if (empty($myAds)): ?>
            <p style="color: #666;">You haven't created any ad campaigns yet.</p>
        <?php else: ?>
            <?php foreach ($myAds as $ad): ?>
                <div class="ad-item">
                    <h3><?= htmlspecialchars($ad['title']) ?></h3>
                    <p>
                        Budget: <?= htmlspecialchars($ad['currency']) ?> <?= number_format($ad['budget'], 2) ?><br>
                        Period: <?= date('M j, Y', strtotime($ad['starts_at'])) ?> - <?= date('M j, Y', strtotime($ad['ends_at'])) ?><br>
                        Status: <span class="status-badge status-<?= htmlspecialchars($ad['status']) ?>"><?= ucfirst($ad['status']) ?></span>
                    </p>
                    <?php if ($ad['status'] === 'running'): ?>
                        <p>
                            <small>Impressions: <?= number_format($ad['impressions'] ?? 0) ?> | 
                            Clicks: <?= number_format($ad['clicks'] ?? 0) ?> | 
                            Spend: <?= number_format($ad['spend'] ?? 0, 2) ?></small>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
