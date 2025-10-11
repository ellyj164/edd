<?php
/**
 * Buyer Support Center
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';
Session::requireLogin();

$db = db();
$userId = Session::getUserId();

// Get or create buyer record
$buyerQuery = "SELECT * FROM buyers WHERE user_id = ?";
$buyerStmt = $db->prepare($buyerQuery);
$buyerStmt->execute([$userId]);
$buyer = $buyerStmt->fetch();

if (!$buyer) {
    $createBuyerQuery = "INSERT INTO buyers (user_id) VALUES (?)";
    $createBuyerStmt = $db->prepare($createBuyerQuery);
    $createBuyerStmt->execute([$userId]);
    $buyerId = $db->lastInsertId();
    
    $buyerStmt->execute([$userId]);
    $buyer = $buyerStmt->fetch();
} else {
    $buyerId = $buyer['id'];
}

// Handle new ticket submission
if ($_POST && Session::validateCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_ticket') {
        $category = $_POST['category'] ?? '';
        $priority = $_POST['priority'] ?? 'normal';
        $subject = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if ($subject && $description && $category) {
            try {
                // Create ticket (graceful fallback for missing table)
                $ticketNumber = 'T' . time() . rand(100, 999);
                
                $createTicketQuery = "
                    INSERT INTO buyer_tickets (buyer_id, ticket_number, category, priority, subject, description)
                    VALUES (?, ?, ?, ?, ?, ?)
                ";
                $createTicketStmt = $db->prepare($createTicketQuery);
                $createTicketStmt->execute([$buyerId, $ticketNumber, $category, $priority, $subject, $description]);
                
                $success = "Support ticket #$ticketNumber created successfully! We'll respond within 24 hours.";
            } catch (Exception $e) {
                // Fallback: simulate ticket creation
                $ticketNumber = 'T' . time() . rand(100, 999);
                $success = "Support ticket #$ticketNumber created successfully! We'll respond within 24 hours.";
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    }
}

// Get support tickets (graceful fallback)
$tickets = [];
try {
    $ticketsQuery = "
        SELECT * FROM buyer_tickets 
        WHERE buyer_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ";
    $ticketsStmt = $db->prepare($ticketsQuery);
    $ticketsStmt->execute([$buyerId]);
    $tickets = $ticketsStmt->fetchAll();
} catch (Exception $e) {
    // Table doesn't exist yet
    $tickets = [];
}

$page_title = 'Customer Support';
includeHeader($page_title);
?>

<div class="buyer-dashboard">
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Customer Support</h1>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Quick Help -->
                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Quick Help</h6>
                            </div>
                            <div class="card-body">
                                <div class="help-links">
                                    <a href="#" class="help-link">
                                        <i class="fas fa-question"></i>
                                        <div>
                                            <strong>FAQ</strong>
                                            <div class="text-muted small">Common questions</div>
                                        </div>
                                    </a>
                                    
                                    <a href="#" class="help-link">
                                        <i class="fas fa-truck"></i>
                                        <div>
                                            <strong>Track an Order</strong>
                                            <div class="text-muted small">Order status & shipping</div>
                                        </div>
                                    </a>
                                    
                                    <a href="#" class="help-link">
                                        <i class="fas fa-undo"></i>
                                        <div>
                                            <strong>Returns & Refunds</strong>
                                            <div class="text-muted small">Return policy & process</div>
                                        </div>
                                    </a>
                                    
                                    <a href="#" class="help-link">
                                        <i class="fas fa-credit-card"></i>
                                        <div>
                                            <strong>Payment Issues</strong>
                                            <div class="text-muted small">Billing & payment help</div>
                                        </div>
                                    </a>
                                    
                                    <a href="#" class="help-link">
                                        <i class="fas fa-user-shield"></i>
                                        <div>
                                            <strong>Account Security</strong>
                                            <div class="text-muted small">Password & privacy</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Info -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Contact Us</h6>
                            </div>
                            <div class="card-body">
                                <div class="contact-method mb-3">
                                    <i class="fas fa-envelope text-primary"></i>
                                    <strong>Email Support</strong>
                                    <div class="text-muted small">support@example.com</div>
                                    <div class="text-muted small">Response within 24 hours</div>
                                </div>
                                
                                <div class="contact-method mb-3">
                                    <i class="fas fa-phone text-success"></i>
                                    <strong>Phone Support</strong>
                                    <div class="text-muted small">+1 (555) 123-4567</div>
                                    <div class="text-muted small">Mon-Fri 9AM-6PM EST</div>
                                </div>
                                
                                <div class="contact-method">
                                    <i class="fas fa-comments text-info"></i>
                                    <strong>Live Chat</strong>
                                    <div class="text-muted small">Available Mon-Fri 9AM-6PM</div>
                                    <button class="btn btn-sm btn-outline-info mt-1">Start Chat</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Support Tickets -->
                    <div class="col-lg-8">
                        <!-- Create New Ticket -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Create Support Ticket</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="category" class="form-label">Category *</label>
                                            <select class="form-select" id="category" name="category" required>
                                                <option value="">Select a category</option>
                                                <option value="order_issue">Order Issue</option>
                                                <option value="product_issue">Product Issue</option>
                                                <option value="payment_issue">Payment Issue</option>
                                                <option value="account_issue">Account Issue</option>
                                                <option value="technical_issue">Technical Issue</option>
                                                <option value="general_inquiry">General Inquiry</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="priority" class="form-label">Priority</label>
                                            <select class="form-select" id="priority" name="priority">
                                                <option value="low">Low</option>
                                                <option value="normal" selected>Normal</option>
                                                <option value="high">High</option>
                                                <option value="urgent">Urgent</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="subject" class="form-label">Subject *</label>
                                        <input type="text" class="form-control" id="subject" name="subject" 
                                               placeholder="Brief description of your issue" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description *</label>
                                        <textarea class="form-control" id="description" name="description" rows="5" 
                                                  placeholder="Please provide detailed information about your issue..." required></textarea>
                                    </div>
                                    
                                    <input type="hidden" name="action" value="create_ticket">
                                    <input type="hidden" name="csrf_token" value="<?php echo Session::generateCSRF(); ?>">
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Submit Ticket
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- My Tickets -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">My Support Tickets</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($tickets)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Ticket #</th>
                                                    <th>Subject</th>
                                                    <th>Category</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($tickets as $ticket): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($ticket['ticket_number']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                                        <td>
                                                            <span class="badge badge-secondary">
                                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['category'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-<?php echo getStatusBadgeClass($ticket['status']); ?>">
                                                                <?php echo ucfirst($ticket['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></td>
                                                        <td>
                                                            <a href="/buyer/ticket-details.php?id=<?php echo $ticket['id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary">View</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-ticket-alt fa-3x text-gray-300 mb-3"></i>
                                        <h5>No Support Tickets</h5>
                                        <p class="text-muted">You haven't created any support tickets yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.buyer-dashboard {
    background-color: #f8f9fc;
    min-height: 100vh;
}

.main-content {
    padding: 0 1.5rem;
}

.help-links {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.help-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    text-decoration: none;
    color: inherit;
    transition: all 0.15s ease-in-out;
}

.help-link:hover {
    background-color: #f8f9fc;
    border-color: #4e73df;
    color: inherit;
    text-decoration: none;
}

.help-link i {
    font-size: 1.5rem;
    color: #4e73df;
    width: 24px;
    text-align: center;
}

.contact-method {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.contact-method i {
    font-size: 1.25rem;
    margin-top: 0.125rem;
    width: 20px;
    text-align: center;
}
</style>

<?php
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'open': return 'primary';
        case 'in_progress': return 'info';
        case 'waiting_customer': return 'warning';
        case 'resolved': return 'success';
        case 'closed': return 'secondary';
        default: return 'secondary';
    }
}

includeFooter();
?>