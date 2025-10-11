<!-- Password Change Modal -->
<div id="passwordModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Change Password</h3>
            <button class="modal-close" onclick="closePasswordModal()">&times;</button>
        </div>
        <form method="post" class="modal-form">
            <?php echo csrfTokenInput(); ?>
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" name="current_password" id="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" name="new_password" id="new_password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closePasswordModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </div>
        </form>
    </div>
</div>

<!-- 2FA Modal -->
<div id="twoFactorModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="twoFactorTitle">Enable Two-Factor Authentication</h3>
            <button class="modal-close" onclick="close2FAModal()">&times;</button>
        </div>
        <div id="twoFactorContent">
            <!-- Content will be filled by JavaScript -->
        </div>
    </div>
</div>

<!-- Login Devices Modal -->
<div id="loginDevicesModal" class="modal" style="display: none;">
    <div class="modal-content large-modal">
        <div class="modal-header">
            <h3>Manage Login Devices</h3>
            <button class="modal-close" onclick="closeLoginDevicesModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>These are the devices currently logged into your account. You can revoke access from any device.</p>
            
            <div class="devices-list">
                <?php foreach ($loginDevices as $device): ?>
                    <?php
                    $currentSession = Session::get('session_token', 'current_session_token_123') === $device['session_token'];
                    $userAgent = $device['user_agent'] ?? 'Unknown Browser';
                    $deviceInfo = parseUserAgent($userAgent);
                    ?>
                    <div class="device-item <?php echo $currentSession ? 'current-device' : ''; ?>">
                        <div class="device-info">
                            <div class="device-icon">
                                <?php echo getDeviceIcon($deviceInfo['device_type']); ?>
                            </div>
                            <div class="device-details">
                                <h4><?php echo htmlspecialchars($deviceInfo['browser'] . ' on ' . $deviceInfo['os']); ?></h4>
                                <p>IP: <?php echo htmlspecialchars($device['ip_address']); ?></p>
                                <p>Last active: <?php echo date('M j, Y \a\t g:i A', strtotime($device['created_at'])); ?></p>
                                <?php if ($currentSession): ?>
                                    <span class="current-label">Current Device</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$currentSession): ?>
                            <div class="device-actions">
                                <form method="post" style="display: inline;">
                                    <?php echo csrfTokenInput(); ?>
                                    <input type="hidden" name="action" value="revoke_session">
                                    <input type="hidden" name="session_id" value="<?php echo $device['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to log out this device?')">
                                        Log Out
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Login Alerts Modal -->
<div id="loginAlertsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Login Alert Settings</h3>
            <button class="modal-close" onclick="closeLoginAlertsModal()">&times;</button>
        </div>
        <form method="post" class="modal-form">
            <?php echo csrfTokenInput(); ?>
            <input type="hidden" name="action" value="update_login_alerts">
            
            <div class="modal-body">
                <p>Configure how you want to be notified about account activity.</p>
                
                <div class="alert-options">
                    <div class="alert-option">
                        <label class="checkbox-label">
                            <input type="checkbox" name="email_alerts" <?php echo $current_user['login_email_alerts'] ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Email notifications for new logins
                        </label>
                        <p class="option-description">Get an email when someone logs into your account</p>
                    </div>
                    
                    <div class="alert-option">
                        <label class="checkbox-label">
                            <input type="checkbox" name="sms_alerts" <?php echo $current_user['login_sms_alerts'] ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            SMS notifications for new logins
                        </label>
                        <p class="option-description">Get a text message when someone logs into your account</p>
                    </div>
                    
                    <div class="alert-option">
                        <label class="checkbox-label">
                            <input type="checkbox" name="new_device_alerts" <?php echo $current_user['new_device_alerts'] ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            New device alerts
                        </label>
                        <p class="option-description">Get notified when your account is accessed from a new device</p>
                    </div>
                    
                    <div class="alert-option">
                        <label class="checkbox-label">
                            <input type="checkbox" name="suspicious_activity_alerts" <?php echo $current_user['suspicious_activity_alerts'] ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Suspicious activity alerts
                        </label>
                        <p class="option-description">Get notified about unusual login patterns or potential security threats</p>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeLoginAlertsModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Additional styles for demo */
.alert {
    padding: 1rem;
    margin: 1rem 0;
    border-radius: 8px;
    border: 1px solid;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.alert-info {
    background: #e3f2fd;
    color: #0d47a1;
    border-color: #2196f3;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 4px;
    margin-right: 0.5rem;
}

.badge-enabled {
    background: #d4edda;
    color: #155724;
}

.badge-disabled {
    background: #f8d7da;
    color: #721c24;
}

.badge-info {
    background: #e3f2fd;
    color: #0d47a1;
}

.btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 6px;
    border: 1px solid;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.btn-outline {
    background: transparent;
    color: #374151;
    border-color: #d1d5db;
}

.btn-danger {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.btn:hover {
    opacity: 0.9;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-group input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
}
</style>

<script>
// Password Modal Functions
function openPasswordModal() {
    document.getElementById('passwordModal').style.display = 'flex';
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
    document.getElementById('passwordModal').querySelector('form').reset();
}

// 2FA Modal Functions
function open2FAModal(action) {
    const modal = document.getElementById('twoFactorModal');
    const title = document.getElementById('twoFactorTitle');
    const content = document.getElementById('twoFactorContent');
    
    if (action === 'enable') {
        title.textContent = 'Enable Two-Factor Authentication';
        content.innerHTML = `
            <div class="modal-body">
                <p>Two-Factor Authentication adds an extra layer of security to your account by requiring a verification code from your phone in addition to your password.</p>
                <div class="twofa-benefits">
                    <h4>Benefits:</h4>
                    <ul>
                        <li>Protects against password theft</li>
                        <li>Prevents unauthorized access</li>
                        <li>Required for sensitive operations</li>
                    </ul>
                </div>
            </div>
            <form method="post" style="margin: 0;">
                ${document.querySelector('input[name="csrf_token"]').outerHTML}
                <input type="hidden" name="action" value="enable_2fa">
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="close2FAModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Enable 2FA</button>
                </div>
            </form>
        `;
    } else {
        title.textContent = 'Disable Two-Factor Authentication';
        content.innerHTML = `
            <div class="modal-body">
                <p>Are you sure you want to disable Two-Factor Authentication? This will make your account less secure.</p>
                <div class="warning">
                    <strong>Warning:</strong> Disabling 2FA reduces your account security.
                </div>
            </div>
            <form method="post" style="margin: 0;">
                ${document.querySelector('input[name="csrf_token"]').outerHTML}
                <input type="hidden" name="action" value="disable_2fa">
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="close2FAModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Disable 2FA</button>
                </div>
            </form>
        `;
    }
    
    modal.style.display = 'flex';
}

function close2FAModal() {
    document.getElementById('twoFactorModal').style.display = 'none';
}

// Login Devices Modal Functions
function openLoginDevicesModal() {
    document.getElementById('loginDevicesModal').style.display = 'flex';
}

function closeLoginDevicesModal() {
    document.getElementById('loginDevicesModal').style.display = 'none';
}

// Login Alerts Modal Functions
function openLoginAlertsModal() {
    document.getElementById('loginAlertsModal').style.display = 'flex';
}

function closeLoginAlertsModal() {
    document.getElementById('loginAlertsModal').style.display = 'none';
}

// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== newPasswordInput.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});
</script>