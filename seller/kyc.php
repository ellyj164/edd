<?php
/**
 * Seller KYC Verification Submission
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';

// Require vendor login
Session::requireLogin();

$vendor = new Vendor();
$db = getDatabase();

// Check if user is a vendor
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo) {
    redirect('/sell.php');
}

$error = '';
$success = '';

// Check existing KYC submission
$stmt = $db->prepare("SELECT * FROM seller_kyc WHERE vendor_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$vendorInfo['id']]);
$existingKyc = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $verification_type = sanitizeInput($_POST['verification_type'] ?? '');
        
        if (empty($verification_type) || !in_array($verification_type, ['individual', 'business', 'corporation'])) {
            $error = 'Please select a valid verification type.';
        } else {
            try {
                // Handle file uploads
                $identity_documents = [];
                $address_verification = [];
                $bank_verification = [];
                
                $upload_dir = __DIR__ . '/../storage/kyc/' . $vendorInfo['id'] . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Process identity document uploads
                if (isset($_FILES['identity_documents'])) {
                    foreach ($_FILES['identity_documents']['name'] as $key => $filename) {
                        if ($_FILES['identity_documents']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_tmp = $_FILES['identity_documents']['tmp_name'][$key];
                            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
                            
                            if (in_array($file_ext, $allowed_types) && $_FILES['identity_documents']['size'][$key] <= 5 * 1024 * 1024) {
                                $new_filename = uniqid('id_') . '.' . $file_ext;
                                $file_path = $upload_dir . $new_filename;
                                
                                if (move_uploaded_file($file_tmp, $file_path)) {
                                    $identity_documents[$key] = [
                                        'file_path' => '/storage/kyc/' . $vendorInfo['id'] . '/' . $new_filename,
                                        'original_name' => $filename,
                                        'document_number' => $_POST['document_numbers'][$key] ?? ''
                                    ];
                                }
                            }
                        }
                    }
                }
                
                // Process address verification uploads
                if (isset($_FILES['address_documents'])) {
                    foreach ($_FILES['address_documents']['name'] as $key => $filename) {
                        if ($_FILES['address_documents']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_tmp = $_FILES['address_documents']['tmp_name'][$key];
                            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
                            
                            if (in_array($file_ext, $allowed_types) && $_FILES['address_documents']['size'][$key] <= 5 * 1024 * 1024) {
                                $new_filename = uniqid('addr_') . '.' . $file_ext;
                                $file_path = $upload_dir . $new_filename;
                                
                                if (move_uploaded_file($file_tmp, $file_path)) {
                                    $address_verification[$key] = [
                                        'file_path' => '/storage/kyc/' . $vendorInfo['id'] . '/' . $new_filename,
                                        'original_name' => $filename
                                    ];
                                }
                            }
                        }
                    }
                }
                
                // Process bank verification uploads
                if (isset($_FILES['bank_documents'])) {
                    foreach ($_FILES['bank_documents']['name'] as $key => $filename) {
                        if ($_FILES['bank_documents']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_tmp = $_FILES['bank_documents']['tmp_name'][$key];
                            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
                            
                            if (in_array($file_ext, $allowed_types) && $_FILES['bank_documents']['size'][$key] <= 5 * 1024 * 1024) {
                                $new_filename = uniqid('bank_') . '.' . $file_ext;
                                $file_path = $upload_dir . $new_filename;
                                
                                if (move_uploaded_file($file_tmp, $file_path)) {
                                    $bank_verification[$key] = [
                                        'file_path' => '/storage/kyc/' . $vendorInfo['id'] . '/' . $new_filename,
                                        'original_name' => $filename,
                                        'account_number' => $_POST['bank_account_numbers'][$key] ?? ''
                                    ];
                                }
                            }
                        }
                    }
                }
                
                // Insert or update KYC submission
                if ($existingKyc && $existingKyc['verification_status'] === 'requires_resubmission') {
                    // Update existing submission
                    $stmt = $db->prepare("
                        UPDATE seller_kyc 
                        SET verification_type = ?, 
                            identity_documents = ?, 
                            address_verification = ?, 
                            bank_verification = ?, 
                            verification_status = 'pending',
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $verification_type,
                        json_encode($identity_documents),
                        json_encode($address_verification),
                        json_encode($bank_verification),
                        $existingKyc['id']
                    ]);
                    $success = 'Your KYC documents have been resubmitted successfully. You will be notified once they are reviewed.';
                } else {
                    // Insert new submission
                    $stmt = $db->prepare("
                        INSERT INTO seller_kyc 
                        (vendor_id, verification_type, identity_documents, address_verification, bank_verification, verification_status)
                        VALUES (?, ?, ?, ?, ?, 'pending')
                    ");
                    $stmt->execute([
                        $vendorInfo['id'],
                        $verification_type,
                        json_encode($identity_documents),
                        json_encode($address_verification),
                        json_encode($bank_verification)
                    ]);
                    $success = 'Your KYC documents have been submitted successfully. You will be notified once they are reviewed.';
                }
                
                // Refresh existing KYC data
                $stmt = $db->prepare("SELECT * FROM seller_kyc WHERE vendor_id = ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$vendorInfo['id']]);
                $existingKyc = $stmt->fetch();
                
            } catch (Exception $e) {
                error_log("KYC submission error: " . $e->getMessage());
                $error = 'An error occurred while submitting your documents. Please try again.';
            }
        }
    }
}

$page_title = 'KYC Verification';
includeHeader($page_title);
?>

<div class="container">
    <div class="kyc-container">
        <div class="kyc-header">
            <h1>Business Verification (KYC)</h1>
            <p>Complete your business verification to unlock full seller features and higher transaction limits.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($existingKyc): ?>
            <!-- Existing KYC Status -->
            <div class="kyc-status-card">
                <div class="status-header">
                    <h3>Current Verification Status</h3>
                    <span class="status-badge status-<?php echo $existingKyc['verification_status']; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $existingKyc['verification_status'])); ?>
                    </span>
                </div>
                
                <div class="status-details">
                    <div class="detail-item">
                        <label>Verification Type:</label>
                        <span><?php echo ucfirst($existingKyc['verification_type']); ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Submitted:</label>
                        <span><?php echo date('F j, Y g:i A', strtotime($existingKyc['created_at'])); ?></span>
                    </div>
                    <?php if ($existingKyc['verified_at']): ?>
                        <div class="detail-item">
                            <label>Reviewed:</label>
                            <span><?php echo date('F j, Y g:i A', strtotime($existingKyc['verified_at'])); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($existingKyc['verification_notes']): ?>
                        <div class="detail-item">
                            <label>Review Notes:</label>
                            <div class="review-notes"><?php echo nl2br(htmlspecialchars($existingKyc['verification_notes'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($existingKyc['verification_status'] === 'approved'): ?>
                    <div class="verification-complete">
                        <i class="icon-checkmark">✅</i>
                        <h4>Verification Complete!</h4>
                        <p>Your business has been successfully verified. You now have access to all seller features.</p>
                    </div>
                <?php elseif ($existingKyc['verification_status'] === 'pending' || $existingKyc['verification_status'] === 'in_review'): ?>
                    <div class="verification-pending">
                        <i class="icon-clock">⏳</i>
                        <h4>Under Review</h4>
                        <p>Your documents are currently being reviewed. We'll notify you once the review is complete (typically within 2-3 business days).</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$existingKyc || $existingKyc['verification_status'] === 'requires_resubmission' || $existingKyc['verification_status'] === 'rejected'): ?>
            <!-- KYC Submission Form -->
            <div class="kyc-form-container">
                <form method="POST" enctype="multipart/form-data" class="kyc-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <!-- Verification Type Selection -->
                    <div class="form-section">
                        <h3>1. Select Verification Type</h3>
                        <div class="verification-types">
                            <label class="verification-type-option">
                                <input type="radio" name="verification_type" value="individual" <?php echo ($existingKyc && $existingKyc['verification_type'] === 'individual') ? 'checked' : ''; ?>>
                                <div class="option-content">
                                    <strong>Individual Seller</strong>
                                    <p>For individual sellers or sole proprietors</p>
                                </div>
                            </label>
                            
                            <label class="verification-type-option">
                                <input type="radio" name="verification_type" value="business" <?php echo ($existingKyc && $existingKyc['verification_type'] === 'business') ? 'checked' : ''; ?>>
                                <div class="option-content">
                                    <strong>Business</strong>
                                    <p>For partnerships, LLCs, and other business entities</p>
                                </div>
                            </label>
                            
                            <label class="verification-type-option">
                                <input type="radio" name="verification_type" value="corporation" <?php echo ($existingKyc && $existingKyc['verification_type'] === 'corporation') ? 'checked' : ''; ?>>
                                <div class="option-content">
                                    <strong>Corporation</strong>
                                    <p>For incorporated businesses and C-corps</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Identity Documents -->
                    <div class="form-section">
                        <h3>2. Identity Documents</h3>
                        <p class="section-description">Upload clear photos or scans of your identity documents. Accepted formats: JPG, PNG, PDF (max 5MB each)</p>
                        
                        <div class="document-group">
                            <div class="document-item">
                                <label>Government-issued ID (Driver's License, Passport, etc.)</label>
                                <input type="file" name="identity_documents[government_id]" accept=".jpg,.jpeg,.png,.pdf" required>
                                <input type="text" name="document_numbers[government_id]" placeholder="Document Number (optional)" class="document-number">
                            </div>
                            
                            <div class="document-item">
                                <label>Social Security Card or Tax ID</label>
                                <input type="file" name="identity_documents[tax_id]" accept=".jpg,.jpeg,.png,.pdf">
                                <input type="text" name="document_numbers[tax_id]" placeholder="Tax ID Number (optional)" class="document-number">
                            </div>
                        </div>
                    </div>

                    <!-- Address Verification -->
                    <div class="form-section">
                        <h3>3. Address Verification</h3>
                        <p class="section-description">Upload documents that verify your business address (utility bill, bank statement, lease agreement, etc.)</p>
                        
                        <div class="document-group">
                            <div class="document-item">
                                <label>Proof of Address (Utility Bill, Bank Statement, etc.)</label>
                                <input type="file" name="address_documents[proof_of_address]" accept=".jpg,.jpeg,.png,.pdf" required>
                            </div>
                            
                            <div class="document-item">
                                <label>Additional Address Document (optional)</label>
                                <input type="file" name="address_documents[additional]" accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                        </div>
                    </div>

                    <!-- Bank Verification -->
                    <div class="form-section">
                        <h3>4. Bank Account Verification</h3>
                        <p class="section-description">Upload bank statements or voided checks to verify your business bank account</p>
                        
                        <div class="document-group">
                            <div class="document-item">
                                <label>Bank Statement or Voided Check</label>
                                <input type="file" name="bank_documents[bank_statement]" accept=".jpg,.jpeg,.png,.pdf" required>
                                <input type="text" name="bank_account_numbers[bank_statement]" placeholder="Last 4 digits of account number (optional)" class="document-number">
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Submit -->
                    <div class="form-section">
                        <div class="terms-agreement">
                            <label class="checkbox-label">
                                <input type="checkbox" required>
                                <span class="checkmark"></span>
                                I certify that all information provided is accurate and complete. I understand that providing false information may result in account suspension.
                            </label>
                        </div>
                        
                        <div class="submit-section">
                            <button type="submit" class="btn btn-primary btn-large">
                                <?php echo ($existingKyc && $existingKyc['verification_status'] === 'requires_resubmission') ? 'Resubmit Documents' : 'Submit for Verification'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Help Section -->
        <div class="kyc-help">
            <h3>Need Help?</h3>
            <div class="help-items">
                <div class="help-item">
                    <strong>Document Quality:</strong>
                    <p>Ensure all documents are clear, readable, and show all four corners. Blurry or cropped images may be rejected.</p>
                </div>
                <div class="help-item">
                    <strong>Processing Time:</strong>
                    <p>Verification typically takes 2-3 business days. You'll receive an email notification once your review is complete.</p>
                </div>
                <div class="help-item">
                    <strong>Security:</strong>
                    <p>All documents are encrypted and stored securely. They are only used for verification purposes and are not shared with third parties.</p>
                </div>
            </div>
            
            <div class="contact-support">
                <p>Still have questions? <a href="/help/contact">Contact our support team</a></p>
            </div>
        </div>
    </div>
</div>

<style>
.kyc-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.kyc-header {
    text-align: center;
    margin-bottom: 30px;
}

.kyc-header h1 {
    color: #333;
    margin-bottom: 10px;
}

.kyc-header p {
    color: #666;
    font-size: 16px;
}

.kyc-status-card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-in_review { background: #d1ecf1; color: #0c5460; }
.status-approved { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }
.status-requires_resubmission { background: #ffeaa7; color: #856404; }

.status-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.detail-item label {
    font-weight: bold;
    color: #666;
    font-size: 14px;
    display: block;
    margin-bottom: 5px;
}

.detail-item span {
    color: #333;
}

.review-notes {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 4px;
    margin-top: 5px;
    border-left: 4px solid #007bff;
}

.verification-complete, .verification-pending {
    text-align: center;
    padding: 20px;
    border-radius: 8px;
}

.verification-complete {
    background: #d4edda;
    color: #155724;
}

.verification-pending {
    background: #fff3cd;
    color: #856404;
}

.verification-complete i, .verification-pending i {
    font-size: 32px;
    display: block;
    margin-bottom: 10px;
}

.kyc-form-container {
    background: white;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h3 {
    color: #333;
    margin-bottom: 10px;
    font-size: 20px;
}

.section-description {
    color: #666;
    margin-bottom: 20px;
}

.verification-types {
    display: grid;
    gap: 15px;
}

.verification-type-option {
    display: flex;
    align-items: center;
    padding: 20px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.verification-type-option:hover {
    border-color: #007bff;
    background: #f8f9fa;
}

.verification-type-option input[type="radio"] {
    margin-right: 15px;
    transform: scale(1.2);
}

.verification-type-option input[type="radio"]:checked + .option-content {
    color: #007bff;
}

.option-content strong {
    display: block;
    margin-bottom: 5px;
    font-size: 16px;
}

.option-content p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.document-group {
    display: grid;
    gap: 20px;
}

.document-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    background: #f9f9fa;
}

.document-item label {
    display: block;
    font-weight: bold;
    margin-bottom: 10px;
    color: #333;
}

.document-item input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 2px dashed #ccc;
    border-radius: 4px;
    background: white;
    margin-bottom: 10px;
}

.document-number {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.terms-agreement {
    margin-bottom: 30px;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    font-size: 14px;
    line-height: 1.5;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 10px;
    margin-top: 2px;
    transform: scale(1.2);
}

.submit-section {
    text-align: center;
}

.btn-large {
    padding: 15px 40px;
    font-size: 16px;
    font-weight: bold;
}

.kyc-help {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.kyc-help h3 {
    color: #333;
    margin-bottom: 20px;
    text-align: center;
}

.help-items {
    display: grid;
    gap: 20px;
    margin-bottom: 25px;
}

.help-item {
    padding: 15px;
    border-left: 4px solid #007bff;
    background: #f8f9fa;
}

.help-item strong {
    display: block;
    margin-bottom: 5px;
    color: #333;
}

.help-item p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.contact-support {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.contact-support a {
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
}

.contact-support a:hover {
    text-decoration: underline;
}
</style>

<?php includeFooter(); ?>