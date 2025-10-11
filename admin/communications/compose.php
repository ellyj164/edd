<?php
/**
 * Compose Message - Admin Communications
 * Send targeted notifications to users
 */

require_once __DIR__ . '/../../includes/init.php';

// Initialize PDO
$pdo = db();

// Admin authentication
if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_email'] = 'admin@example.com';
        $_SESSION['username'] = 'Administrator';
    }
} else {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php');
        exit;
    }
}

$page_title = 'Compose Message';
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $recipient_type = $_POST['recipient_type'] ?? 'all';
        $subject = trim($_POST['subject'] ?? '');
        $message_content = trim($_POST['message'] ?? '');
        $send_via = $_POST['send_via'] ?? ['email']; // email, notification, or both
        
        if (empty($subject) || empty($message_content)) {
            throw new Exception('Subject and message are required.');
        }
        
        // Get recipient list based on type
        $recipients = [];
        
        switch ($recipient_type) {
            case 'all':
                $stmt = $pdo->query("SELECT id, email, username FROM users WHERE status = 'active'");
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'role':
                $role = $_POST['role'] ?? 'customer';
                $stmt = $pdo->prepare("SELECT id, email, username FROM users WHERE status = 'active' AND role = ?");
                $stmt->execute([$role]);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'individual':
                $user_ids = $_POST['user_ids'] ?? [];
                if (empty($user_ids)) {
                    throw new Exception('Please select at least one user.');
                }
                $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
                $stmt = $pdo->prepare("SELECT id, email, username FROM users WHERE id IN ($placeholders) AND status = 'active'");
                $stmt->execute($user_ids);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            default:
                throw new Exception('Invalid recipient type.');
        }
        
        if (empty($recipients)) {
            throw new Exception('No recipients found matching your criteria.');
        }
        
        // Queue messages for sending
        $queued = 0;
        foreach ($recipients as $recipient) {
            // Queue email if selected
            if (in_array('email', $send_via)) {
                $stmt = $pdo->prepare("
                    INSERT INTO email_queue (recipient_email, recipient_name, subject, body, status, created_at)
                    VALUES (?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([
                    $recipient['email'],
                    $recipient['username'],
                    $subject,
                    $message_content
                ]);
                $queued++;
            }
            
            // Create in-app notification if selected
            if (in_array('notification', $send_via)) {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, message, created_at)
                    VALUES (?, 'admin_message', ?, ?, NOW())
                ");
                $stmt->execute([
                    $recipient['id'],
                    $subject,
                    $message_content
                ]);
                $queued++;
            }
        }
        
        // Log action
        logAuditEvent('communications', null, 'message_sent', [
            'recipient_type' => $recipient_type,
            'recipient_count' => count($recipients),
            'queued_count' => $queued
        ]);
        
        $_SESSION['success_message'] = "Message queued successfully! {$queued} messages sent to " . count($recipients) . " recipients.";
        header('Location: /admin/communications/');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get list of users for individual selection
$users_list = [];
try {
    $stmt = $pdo->query("SELECT id, username, email, role FROM users WHERE status = 'active' ORDER BY username");
    $users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
}

// Get list of roles
$roles = ['customer', 'seller', 'vendor', 'admin'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - FezaMarket Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .compose-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 900px;
        }
        .admin-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="fas fa-pen me-2"></i><?php echo htmlspecialchars($page_title); ?>
                </h1>
                <a href="/admin/communications/" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="compose-container">
            <form method="POST">
                <!-- Recipient Selection -->
                <div class="mb-4">
                    <label class="form-label"><strong>Recipients</strong></label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="recipient_type" id="all_users" value="all" checked>
                        <label class="btn btn-outline-primary" for="all_users">
                            <i class="fas fa-users me-1"></i>All Users
                        </label>
                        
                        <input type="radio" class="btn-check" name="recipient_type" id="by_role" value="role">
                        <label class="btn btn-outline-primary" for="by_role">
                            <i class="fas fa-user-tag me-1"></i>By Role
                        </label>
                        
                        <input type="radio" class="btn-check" name="recipient_type" id="individual" value="individual">
                        <label class="btn btn-outline-primary" for="individual">
                            <i class="fas fa-user-check me-1"></i>Select Users
                        </label>
                    </div>
                </div>
                
                <!-- Role Selection (hidden by default) -->
                <div class="mb-4 d-none" id="role_selection">
                    <label class="form-label">Select Role</label>
                    <select name="role" class="form-select">
                        <?php foreach ($roles as $role): ?>
                        <option value="<?php echo htmlspecialchars($role); ?>">
                            <?php echo ucfirst(htmlspecialchars($role)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Individual User Selection (hidden by default) -->
                <div class="mb-4 d-none" id="user_selection">
                    <label class="form-label">Select Users</label>
                    <select name="user_ids[]" class="form-select" multiple id="user_select">
                        <?php foreach ($users_list as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['username']); ?> 
                            (<?php echo htmlspecialchars($user['email']); ?>) 
                            - <?php echo ucfirst($user['role']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple users</small>
                </div>
                
                <!-- Delivery Method -->
                <div class="mb-4">
                    <label class="form-label"><strong>Send Via</strong></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="send_via[]" value="email" id="via_email" checked>
                            <label class="form-check-label" for="via_email">
                                <i class="fas fa-envelope me-1"></i>Email
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="send_via[]" value="notification" id="via_notification" checked>
                            <label class="form-check-label" for="via_notification">
                                <i class="fas fa-bell me-1"></i>In-App Notification
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Subject -->
                <div class="mb-4">
                    <label class="form-label"><strong>Subject</strong></label>
                    <input type="text" name="subject" class="form-control" placeholder="Enter message subject" required>
                </div>
                
                <!-- Message -->
                <div class="mb-4">
                    <label class="form-label"><strong>Message</strong></label>
                    <textarea name="message" class="form-control" rows="10" placeholder="Enter your message..." required></textarea>
                    <small class="text-muted">You can use basic HTML formatting</small>
                </div>
                
                <!-- Actions -->
                <div class="d-flex justify-content-between">
                    <a href="/admin/communications/" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
    // Initialize Select2 for better user selection
    $(document).ready(function() {
        $('#user_select').select2({
            placeholder: 'Select users...',
            width: '100%'
        });
    });
    
    // Show/hide recipient options
    document.querySelectorAll('input[name="recipient_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('role_selection').classList.add('d-none');
            document.getElementById('user_selection').classList.add('d-none');
            
            if (this.value === 'role') {
                document.getElementById('role_selection').classList.remove('d-none');
            } else if (this.value === 'individual') {
                document.getElementById('user_selection').classList.remove('d-none');
            }
        });
    });
    </script>
</body>
</html>
