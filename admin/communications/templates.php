<?php
/**
 * Communications - Template Management
 * Admin Panel - Message Template Editor
 */

require_once __DIR__ . '/../../includes/init.php';

// Admin Bypass Mode - Skip all authentication when enabled
if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_email'] = 'admin@example.com';
        $_SESSION['username'] = 'Administrator';
        $_SESSION['admin_bypass'] = true;
    }
} else {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

$page_title = 'Message Templates';
$page_subtitle = 'Create and manage communication templates';

// Sample template data
$templates = [
    [
        'id' => 1,
        'name' => 'Welcome Email',
        'type' => 'Email',
        'category' => 'Onboarding',
        'language' => 'English',
        'status' => 'Active',
        'usage_count' => 2350,
        'last_used' => '2024-09-14 10:30:00',
        'variables' => ['{{first_name}}', '{{email}}', '{{platform_name}}']
    ],
    [
        'id' => 2,
        'name' => 'Order Shipped SMS',
        'type' => 'SMS',
        'category' => 'Transactional',
        'language' => 'English',
        'status' => 'Active',
        'usage_count' => 1890,
        'last_used' => '2024-09-14 11:45:00',
        'variables' => ['{{first_name}}', '{{order_number}}', '{{tracking_url}}']
    ],
    [
        'id' => 3,
        'name' => 'Payment Reminder',
        'type' => 'Email',
        'category' => 'Billing',
        'language' => 'English',
        'status' => 'Draft',
        'usage_count' => 0,
        'last_used' => null,
        'variables' => ['{{first_name}}', '{{amount_due}}', '{{due_date}}']
    ],
    [
        'id' => 4,
        'name' => 'Flash Sale Alert',
        'type' => 'Push',
        'category' => 'Marketing',
        'language' => 'English',
        'status' => 'Active',
        'usage_count' => 8920,
        'last_used' => '2024-09-13 16:00:00',
        'variables' => ['{{product_name}}', '{{discount_percent}}', '{{sale_end_time}}']
    ],
    [
        'id' => 5,
        'name' => 'Account Verification',
        'type' => 'Email',
        'category' => 'Security',
        'language' => 'English',
        'status' => 'Active',
        'usage_count' => 1245,
        'last_used' => '2024-09-14 09:15:00',
        'variables' => ['{{first_name}}', '{{verification_link}}', '{{expires_in}}']
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
            --admin-light: #ecf0f1;
            --admin-dark: #2c3e50;
        }
        
        body {
            background-color: var(--admin-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .badge-outline {
            border: 1px solid;
            background: transparent;
        }
        
        .message-type-email {
            color: var(--admin-primary);
            border-color: var(--admin-primary);
        }
        
        .message-type-sms {
            color: var(--admin-success);
            border-color: var(--admin-success);
        }
        
        .message-type-push {
            color: var(--admin-warning);
            border-color: var(--admin-warning);
        }
        
        .template-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .variable-tag {
            background: var(--admin-accent);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            margin: 0.1rem;
            display: inline-block;
        }
        
        .template-editor {
            min-height: 400px;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        Message Templates
                    </h1>
                    <small class="text-white-50">Create and manage communication templates</small>
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-inline-block">
                        <span class="me-3">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?>
                        </span>
                        <span class="badge bg-light text-dark me-3">
                            <i class="fas fa-circle text-success me-1"></i>
                            Online
                        </span>
                        <span class="text-white-50">
                            <?php echo date('M d, Y H:i'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">
        <!-- Admin Bypass Notice -->
        <?php if (defined('ADMIN_BYPASS') && ADMIN_BYPASS && isset($_SESSION['admin_bypass'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Admin Bypass Mode Active!</strong> Authentication is disabled for development.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Navigation Breadcrumb -->
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="/admin/" class="text-decoration-none">
                                <i class="fas fa-tachometer-alt me-1"></i>Admin Dashboard
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="/admin/communications/" class="text-decoration-none">Communications</a>
                        </li>
                        <li class="breadcrumb-item active">Message Templates</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Template Library</h5>
                <p class="text-muted">Manage reusable message templates for all communication channels</p>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#newTemplateModal">
                    <i class="fas fa-plus me-1"></i>
                    Create Template
                </button>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" data-filter="all">All Templates</a></li>
                        <li><a class="dropdown-item" href="#" data-filter="email">Email Only</a></li>
                        <li><a class="dropdown-item" href="#" data-filter="sms">SMS Only</a></li>
                        <li><a class="dropdown-item" href="#" data-filter="push">Push Only</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" data-filter="active">Active Only</a></li>
                        <li><a class="dropdown-item" href="#" data-filter="draft">Drafts Only</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Templates Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Template</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <th>Usage</th>
                                        <th>Variables</th>
                                        <th>Status</th>
                                        <th>Last Used</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($templates as $template): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($template['name']); ?></strong><br>
                                                <small class="text-muted">ID: <?php echo $template['id']; ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-outline message-type-<?php echo strtolower($template['type']); ?>">
                                                <i class="fas fa-<?php echo $template['type'] === 'Email' ? 'envelope' : ($template['type'] === 'SMS' ? 'sms' : 'bell'); ?> me-1"></i>
                                                <?php echo $template['type']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?php echo $template['category']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($template['usage_count']); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <?php foreach ($template['variables'] as $variable): ?>
                                                    <span class="variable-tag"><?php echo htmlspecialchars($variable); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $template['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                                <?php echo $template['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($template['last_used']): ?>
                                                <small><?php echo date('M d, Y H:i', strtotime($template['last_used'])); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Never</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" title="Edit" 
                                                    data-bs-toggle="modal" data-bs-target="#editTemplateModal"
                                                    data-template-id="<?php echo $template['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-info" title="Preview">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-success" title="Use Template">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                                <button class="btn btn-outline-warning" title="Duplicate">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <nav aria-label="Template pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                                <li class="page-item active">
                                    <span class="page-link">1</span>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">2</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">3</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Template Modal -->
    <div class="modal fade" id="newTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Create New Template
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newTemplateForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="templateName" class="form-label">Template Name</label>
                                <input type="text" class="form-control" id="templateName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="templateType" class="form-label">Type</label>
                                <select class="form-select" id="templateType" required>
                                    <option value="">Select Type</option>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                    <option value="push">Push Notification</option>
                                    <option value="in_app">In-App Message</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="templateCategory" class="form-label">Category</label>
                                <select class="form-select" id="templateCategory" required>
                                    <option value="">Select Category</option>
                                    <option value="onboarding">Onboarding</option>
                                    <option value="transactional">Transactional</option>
                                    <option value="marketing">Marketing</option>
                                    <option value="security">Security</option>
                                    <option value="billing">Billing</option>
                                    <option value="support">Support</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="templateLanguage" class="form-label">Language</label>
                                <select class="form-select" id="templateLanguage" required>
                                    <option value="en">English</option>
                                    <option value="es">Spanish</option>
                                    <option value="fr">French</option>
                                    <option value="de">German</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="templateDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="templateDescription" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="templateSubject" class="form-label">Subject/Title <small class="text-muted">(Email/Push only)</small></label>
                            <input type="text" class="form-control" id="templateSubject">
                        </div>
                        
                        <div class="mb-3">
                            <label for="templateContent" class="form-label">Content</label>
                            <textarea class="form-control template-editor" id="templateContent" rows="10" required></textarea>
                            <div class="form-text">
                                Use variables like {{first_name}}, {{order_number}}, {{total_amount}} for personalization.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="templateVariables" class="form-label">Available Variables</label>
                            <input type="text" class="form-control" id="templateVariables" placeholder="first_name, email, order_number, total_amount">
                            <div class="form-text">
                                Comma-separated list of variables available for this template.
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Template type change handler
        document.getElementById('templateType').addEventListener('change', function() {
            const subjectField = document.getElementById('templateSubject');
            const subjectLabel = subjectField.previousElementSibling;
            
            if (this.value === 'sms') {
                subjectField.style.display = 'none';
                subjectLabel.style.display = 'none';
            } else {
                subjectField.style.display = 'block';
                subjectLabel.style.display = 'block';
            }
        });
        
        // Filter functionality
        document.querySelectorAll('[data-filter]').forEach(function(filterBtn) {
            filterBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.getAttribute('data-filter');
                // Implement filtering logic here
                console.log('Filtering by:', filter);
            });
        });
        
        // Edit template modal
        document.querySelectorAll('[data-template-id]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const templateId = this.getAttribute('data-template-id');
                // Load template data for editing
                console.log('Editing template:', templateId);
            });
        });
    </script>
</body>
</html>