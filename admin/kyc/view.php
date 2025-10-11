<?php
/**
 * KYC Document Review Page for Admin
 * E-Commerce Platform
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/auth.php';

// Require admin authentication
requireAdminAuth();

$db = getDatabase();
$kyc_id = (int)($_GET['id'] ?? 0);

if (!$kyc_id) {
    redirect('/admin/kyc/');
}

// Get KYC submission details
$stmt = $db->prepare("
    SELECT 
        sk.*,
        v.business_name,
        u.first_name,
        u.last_name,
        u.email,
        verifier.first_name as verifier_first_name,
        verifier.last_name as verifier_last_name
    FROM seller_kyc sk
    JOIN vendors v ON sk.vendor_id = v.id
    JOIN users u ON v.user_id = u.id
    LEFT JOIN users verifier ON sk.verified_by = verifier.id
    WHERE sk.id = ?
");
$stmt->execute([$kyc_id]);
$kyc = $stmt->fetch();

if (!$kyc) {
    Session::setFlash('error', 'KYC submission not found');
    redirect('/admin/kyc/');
}

$page_title = 'KYC Review - ' . ($kyc['business_name'] ?: $kyc['first_name'] . ' ' . $kyc['last_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin</title>
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="stylesheet" href="/css/forms.css">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <h2>Admin Panel</h2>
            <nav class="admin-nav">
                <a href="/admin/">Dashboard</a>
                <a href="/admin/users/">Users</a>
                <a href="/admin/kyc/" class="active">KYC Management</a>
                <a href="/admin/products/">Products</a>
                <a href="/admin/orders/">Orders</a>
                <a href="/admin/finance/">Finance</a>
                <a href="/admin/settings/">Settings</a>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
                <div class="admin-actions">
                    <a href="/admin/kyc/" class="btn btn-secondary">← Back to KYC List</a>
                </div>
            </div>

            <div class="kyc-review-container">
                <!-- Business/Seller Information -->
                <div class="review-section">
                    <h3>Business Information</h3>
                    <div class="business-info-grid">
                        <div class="info-item">
                            <label>Business Name:</label>
                            <span><?php echo htmlspecialchars($kyc['business_name'] ?: 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Owner Name:</label>
                            <span><?php echo htmlspecialchars($kyc['first_name'] . ' ' . $kyc['last_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Email:</label>
                            <span><?php echo htmlspecialchars($kyc['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Verification Type:</label>
                            <span class="verification-type"><?php echo ucfirst($kyc['verification_type']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Current Status:</label>
                            <span class="status status-<?php echo $kyc['verification_status']; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $kyc['verification_status'])); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <label>Submitted:</label>
                            <span><?php echo date('F j, Y g:i A', strtotime($kyc['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Identity Documents -->
                <div class="review-section">
                    <h3>Identity Verification</h3>
                    <?php 
                    $identity_docs = json_decode($kyc['identity_documents'] ?? '{}', true);
                    if (!empty($identity_docs)): 
                    ?>
                        <div class="documents-grid">
                            <?php foreach ($identity_docs as $doc_type => $doc_info): ?>
                                <div class="document-item">
                                    <h4><?php echo ucwords(str_replace('_', ' ', $doc_type)); ?></h4>
                                    <?php if (isset($doc_info['file_path']) && file_exists($doc_info['file_path'])): ?>
                                        <div class="document-preview">
                                            <?php 
                                            $file_ext = strtolower(pathinfo($doc_info['file_path'], PATHINFO_EXTENSION));
                                            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                                            ?>
                                                <img src="<?php echo htmlspecialchars($doc_info['file_path']); ?>" alt="<?php echo $doc_type; ?>" class="document-image">
                                            <?php else: ?>
                                                <div class="document-file">
                                                    <i class="file-icon">📄</i>
                                                    <span><?php echo htmlspecialchars($doc_info['original_name'] ?? basename($doc_info['file_path'])); ?></span>
                                                    <a href="<?php echo htmlspecialchars($doc_info['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary">Download</a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="document-missing">
                                            <span class="text-muted">Document not found</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($doc_info['document_number'])): ?>
                                        <div class="document-details">
                                            <small><strong>Document Number:</strong> <?php echo htmlspecialchars($doc_info['document_number']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No identity documents uploaded</p>
                    <?php endif; ?>
                </div>

                <!-- Address Verification -->
                <?php 
                $address_docs = json_decode($kyc['address_verification'] ?? '{}', true);
                if (!empty($address_docs)): 
                ?>
                <div class="review-section">
                    <h3>Address Verification</h3>
                    <div class="documents-grid">
                        <?php foreach ($address_docs as $doc_type => $doc_info): ?>
                            <div class="document-item">
                                <h4><?php echo ucwords(str_replace('_', ' ', $doc_type)); ?></h4>
                                <?php if (isset($doc_info['file_path']) && file_exists($doc_info['file_path'])): ?>
                                    <div class="document-preview">
                                        <?php 
                                        $file_ext = strtolower(pathinfo($doc_info['file_path'], PATHINFO_EXTENSION));
                                        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                                        ?>
                                            <img src="<?php echo htmlspecialchars($doc_info['file_path']); ?>" alt="<?php echo $doc_type; ?>" class="document-image">
                                        <?php else: ?>
                                            <div class="document-file">
                                                <i class="file-icon">📄</i>
                                                <span><?php echo htmlspecialchars($doc_info['original_name'] ?? basename($doc_info['file_path'])); ?></span>
                                                <a href="<?php echo htmlspecialchars($doc_info['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary">Download</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Bank Verification -->
                <?php 
                $bank_docs = json_decode($kyc['bank_verification'] ?? '{}', true);
                if (!empty($bank_docs)): 
                ?>
                <div class="review-section">
                    <h3>Bank Account Verification</h3>
                    <div class="documents-grid">
                        <?php foreach ($bank_docs as $doc_type => $doc_info): ?>
                            <div class="document-item">
                                <h4><?php echo ucwords(str_replace('_', ' ', $doc_type)); ?></h4>
                                <?php if (isset($doc_info['file_path']) && file_exists($doc_info['file_path'])): ?>
                                    <div class="document-preview">
                                        <?php 
                                        $file_ext = strtolower(pathinfo($doc_info['file_path'], PATHINFO_EXTENSION));
                                        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                                        ?>
                                            <img src="<?php echo htmlspecialchars($doc_info['file_path']); ?>" alt="<?php echo $doc_type; ?>" class="document-image">
                                        <?php else: ?>
                                            <div class="document-file">
                                                <i class="file-icon">📄</i>
                                                <span><?php echo htmlspecialchars($doc_info['original_name'] ?? basename($doc_info['file_path'])); ?></span>
                                                <a href="<?php echo htmlspecialchars($doc_info['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary">Download</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($doc_info['account_number'])): ?>
                                    <div class="document-details">
                                        <small><strong>Account Number:</strong> <?php echo htmlspecialchars($doc_info['account_number']); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Review History -->
                <?php if ($kyc['verification_notes'] || $kyc['verifier_first_name']): ?>
                <div class="review-section">
                    <h3>Review History</h3>
                    <div class="review-history">
                        <?php if ($kyc['verifier_first_name']): ?>
                            <div class="review-item">
                                <strong>Reviewed by:</strong> <?php echo htmlspecialchars($kyc['verifier_first_name'] . ' ' . $kyc['verifier_last_name']); ?>
                                <?php if ($kyc['verified_at']): ?>
                                    <span class="review-date"> on <?php echo date('F j, Y g:i A', strtotime($kyc['verified_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($kyc['verification_notes']): ?>
                            <div class="review-notes">
                                <strong>Notes:</strong>
                                <p><?php echo nl2br(htmlspecialchars($kyc['verification_notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <?php if ($kyc['verification_status'] === 'pending' || $kyc['verification_status'] === 'in_review'): ?>
                <div class="review-actions">
                    <button class="btn btn-success" onclick="showActionModal(<?php echo $kyc['id']; ?>, 'approve')">
                        ✅ Approve Verification
                    </button>
                    <button class="btn btn-danger" onclick="showActionModal(<?php echo $kyc['id']; ?>, 'reject')">
                        ❌ Reject Verification
                    </button>
                    <button class="btn btn-warning" onclick="showActionModal(<?php echo $kyc['id']; ?>, 'request_resubmission')">
                        📝 Request Resubmission
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Action Modal -->
    <div id="actionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3 id="modalTitle">Action</h3>
            <form method="POST" action="/admin/kyc/">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" id="modalAction">
                <input type="hidden" name="kyc_id" id="modalKycId">
                
                <div class="form-group">
                    <label for="modalNotes">Notes:</label>
                    <textarea name="notes" id="modalNotes" rows="4" class="form-control" placeholder="Enter notes for this action..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Confirm</button>
                    <button type="button" class="btn btn-secondary" onclick="hideActionModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showActionModal(kycId, action) {
            const modal = document.getElementById('actionModal');
            const title = document.getElementById('modalTitle');
            const actionInput = document.getElementById('modalAction');
            const kycIdInput = document.getElementById('modalKycId');
            const notesField = document.getElementById('modalNotes');
            
            actionInput.value = action;
            kycIdInput.value = kycId;
            
            switch (action) {
                case 'approve':
                    title.textContent = 'Approve KYC Verification';
                    notesField.placeholder = 'Optional notes for approval...';
                    notesField.required = false;
                    break;
                case 'reject':
                    title.textContent = 'Reject KYC Verification';
                    notesField.placeholder = 'Required: Please specify reasons for rejection...';
                    notesField.required = true;
                    break;
                case 'request_resubmission':
                    title.textContent = 'Request Resubmission';
                    notesField.placeholder = 'Required: Please specify what needs to be corrected...';
                    notesField.required = true;
                    break;
            }
            
            modal.style.display = 'block';
        }
        
        function hideActionModal() {
            document.getElementById('actionModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('actionModal');
            if (event.target === modal) {
                hideActionModal();
            }
        }
    </script>

    <style>
        .kyc-review-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .review-section {
            background: white;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .review-section h3 {
            margin: 0 0 20px 0;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .business-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-item label {
            font-weight: bold;
            color: #666;
            font-size: 14px;
        }

        .info-item span {
            font-size: 16px;
            color: #333;
        }

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .document-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            background: #f9f9f9;
        }

        .document-item h4 {
            margin: 0 0 12px 0;
            color: #333;
            font-size: 16px;
        }

        .document-preview {
            text-align: center;
            margin-bottom: 12px;
        }

        .document-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .document-file {
            padding: 20px;
            border: 2px dashed #ccc;
            border-radius: 4px;
            text-align: center;
        }

        .file-icon {
            font-size: 24px;
            display: block;
            margin-bottom: 8px;
        }

        .document-details {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #eee;
        }

        .review-history {
            background: #f5f5f5;
            padding: 16px;
            border-radius: 4px;
        }

        .review-item {
            margin-bottom: 8px;
        }

        .review-date {
            color: #666;
            font-weight: normal;
        }

        .review-notes {
            margin-top: 12px;
        }

        .review-notes p {
            background: white;
            padding: 12px;
            border-radius: 4px;
            margin: 8px 0 0 0;
        }

        .review-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            padding: 24px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-in_review { background: #d1ecf1; color: #0c5460; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-requires_resubmission { background: #ffeaa7; color: #856404; }
        
        .verification-type {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            text-transform: uppercase;
        }

        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 5px;
        }
    </style>
</body>
</html>