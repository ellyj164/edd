/**
 * Account Management JavaScript
 * Handles CRUD operations for user account dashboard
 */

// Get CSRF token
function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        return metaTag.getAttribute('content');
    }
    // Fallback: try to get from hidden input
    const input = document.querySelector('input[name="csrf_token"]');
    return input ? input.value : '';
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        min-width: 300px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        animation: slideInRight 0.3s ease-out;
    `;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Profile editing
function editProfile() {
    const modal = createModal('Edit Profile', `
        <form id="editProfileForm" class="modal-form">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone">
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="">Select...</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                    <option value="prefer_not_to_say">Prefer not to say</option>
                </select>
            </div>
            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    `);
    
    // Populate with current data
    fetch('/api/account/get-profile.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('first_name').value = data.data.first_name || '';
                document.getElementById('last_name').value = data.data.last_name || '';
                document.getElementById('email').value = data.data.email || '';
                document.getElementById('phone').value = data.data.phone || '';
                document.getElementById('gender').value = data.data.gender || '';
                document.getElementById('date_of_birth').value = data.data.date_of_birth || '';
            }
        })
        .catch(err => console.error('Error loading profile:', err));
    
    document.getElementById('editProfileForm').addEventListener('submit', handleProfileUpdate);
}

async function handleProfileUpdate(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.csrf_token = getCsrfToken();
    
    try {
        const response = await fetch('/api/account/update-profile.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.error, 'error');
        }
    } catch (error) {
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Address management
function showAddressModal(addressId = null) {
    const isEdit = addressId !== null;
    const modal = createModal(isEdit ? 'Edit Address' : 'Add New Address', `
        <form id="addressForm" class="modal-form">
            <div class="form-group">
                <label for="address_type">Address Type *</label>
                <select id="address_type" name="address_type" required>
                    <option value="both">Both (Billing & Shipping)</option>
                    <option value="billing">Billing Only</option>
                    <option value="shipping">Shipping Only</option>
                </select>
            </div>
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone">
            </div>
            <div class="form-group">
                <label for="address_line1">Address Line 1 *</label>
                <input type="text" id="address_line1" name="address_line1" required>
            </div>
            <div class="form-group">
                <label for="address_line2">Address Line 2</label>
                <input type="text" id="address_line2" name="address_line2">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="city">City *</label>
                    <input type="text" id="city" name="city" required>
                </div>
                <div class="form-group">
                    <label for="state">State/Province *</label>
                    <input type="text" id="state" name="state" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="postal_code">Postal Code *</label>
                    <input type="text" id="postal_code" name="postal_code" required>
                </div>
                <div class="form-group">
                    <label for="country">Country *</label>
                    <select id="country" name="country" required>
                        <option value="US">United States</option>
                        <option value="RW">Rwanda</option>
                        <option value="KE">Kenya</option>
                        <option value="UG">Uganda</option>
                        <option value="TZ">Tanzania</option>
                        <option value="CA">Canada</option>
                        <option value="GB">United Kingdom</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="is_default" name="is_default">
                    Set as default address
                </label>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">${isEdit ? 'Update' : 'Add'} Address</button>
            </div>
        </form>
    `);
    
    if (isEdit) {
        // Load address data
        loadAddressData(addressId);
    }
    
    document.getElementById('addressForm').addEventListener('submit', (e) => handleAddressSubmit(e, addressId));
}

async function loadAddressData(addressId) {
    // Implementation would fetch address data and populate form
    // For now, placeholder
}

async function handleAddressSubmit(e, addressId = null) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.csrf_token = getCsrfToken();
    data.is_default = document.getElementById('is_default').checked;
    
    if (addressId) {
        data.address_id = addressId;
    }
    
    const endpoint = addressId ? '/api/account/update-address.php' : '/api/account/add-address.php';
    
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.error, 'error');
        }
    } catch (error) {
        showNotification('An error occurred. Please try again.', 'error');
    }
}

async function deleteAddress(addressId) {
    if (!confirm('Are you sure you want to delete this address?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/account/delete-address.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                csrf_token: getCsrfToken(),
                address_id: addressId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.error, 'error');
        }
    } catch (error) {
        showNotification('An error occurred. Please try again.', 'error');
    }
}

async function editAddress(addressId) {
    showAddressModal(addressId);
}

async function makeDefaultAddress(addressId) {
    try {
        const response = await fetch('/api/addresses/set-default.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                csrf_token: getCsrfToken(),
                address_id: addressId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Default address updated', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.error, 'error');
        }
    } catch (error) {
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Wallet transfer
function showTransferModal() {
    const modal = createModal('Transfer to FezaMarket User', `
        <form id="transferForm" class="modal-form">
            <div class="form-group">
                <label for="recipient_email">Recipient Email *</label>
                <input type="email" id="recipient_email" name="recipient_email" required 
                       placeholder="user@example.com">
                <small>Enter the email of the FezaMarket user you want to send money to</small>
            </div>
            <div class="form-group">
                <label for="amount">Amount (USD) *</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label for="note">Note (Optional)</label>
                <textarea id="note" name="note" rows="3" placeholder="Add a message..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Transfer Funds</button>
            </div>
        </form>
    `);
    
    document.getElementById('transferForm').addEventListener('submit', handleWalletTransfer);
}

async function handleWalletTransfer(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.csrf_token = getCsrfToken();
    
    try {
        const response = await fetch('/api/account/wallet-transfer.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.error, 'error');
        }
    } catch (error) {
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Order management
async function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/account/cancel-order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                csrf_token: getCsrfToken(),
                order_id: orderId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.error, 'error');
        }
    } catch (error) {
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Modal utilities
function createModal(title, content) {
    // Remove existing modal if any
    const existing = document.getElementById('accountModal');
    if (existing) {
        existing.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = 'accountModal';
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>${title}</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                ${content}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Close on outside click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    return modal;
}

function closeModal() {
    const modal = document.getElementById('accountModal');
    if (modal) {
        modal.remove();
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    color: #6b7280;
    cursor: pointer;
    line-height: 1;
}

.modal-body {
    padding: 1.5rem;
}

.modal-form .form-group {
    margin-bottom: 1.25rem;
}

.modal-form label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.modal-form input[type="text"],
.modal-form input[type="email"],
.modal-form input[type="tel"],
.modal-form input[type="number"],
.modal-form input[type="date"],
.modal-form select,
.modal-form textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
}

.modal-form input:focus,
.modal-form select:focus,
.modal-form textarea:focus {
    outline: none;
    border-color: #2563eb;
}

.modal-form small {
    display: block;
    margin-top: 0.25rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
`;
document.head.appendChild(style);
