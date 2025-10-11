    </div> <!-- End main content container -->
    
    <!-- Seller Dashboard Footer -->
    <footer class="seller-footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="footer-section">
                        <h6>Seller Resources</h6>
                        <ul class="list-unstyled">
                            <li><a href="/help/selling.php">Selling Guide</a></li>
                            <li><a href="/help/fees.php">Fee Structure</a></li>
                            <li><a href="/help/policies.php">Seller Policies</a></li>
                            <li><a href="/help/support.php">Get Support</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="footer-section">
                        <h6>Quick Links</h6>
                        <ul class="list-unstyled">
                            <li><a href="/seller/products/add.php">Add Product</a></li>
                            <li><a href="/seller/bulk-upload.php">Bulk Upload</a></li>
                            <li><a href="/seller/performance.php">Performance</a></li>
                            <li><a href="/seller/payments.php">Payments</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row align-items-center">
                <div class="col-md-8">
                    <p class="text-muted mb-0">
                        © <?php echo date('Y'); ?> FezaMarket Seller Dashboard. 
                        <a href="/privacy.php">Privacy</a> | 
                        <a href="/terms.php">Terms</a> | 
                        <a href="/help.php">Help</a>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <small class="text-muted">
                        Version 2.1 | 
                        <a href="/seller/feedback.php">Send Feedback</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Seller Dashboard Scripts -->
    <script>
        $(document).ready(function() {
            // User dropdown toggle
            $('.user-menu-toggle').on('click', function(e) {
                e.preventDefault();
                const dropdown = $(this).siblings('.user-dropdown-menu');
                $('.user-dropdown-menu').not(dropdown).removeClass('show');
                dropdown.toggleClass('show');
            });
            
            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.account-dropdown').length) {
                    $('.user-dropdown-menu').removeClass('show');
                }
            });
            
            // Auto-hide alerts after 5 seconds
            $('.alert').each(function() {
                const alert = $(this);
                setTimeout(function() {
                    alert.fadeOut();
                }, 5000);
            });
            
            // Form validation and submission
            $('.seller-form').on('submit', function() {
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                
                // Disable submit button to prevent double submission
                submitBtn.prop('disabled', true);
                const originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...');
                
                // Re-enable after 3 seconds as fallback
                setTimeout(function() {
                    submitBtn.prop('disabled', false);
                    submitBtn.html(originalText);
                }, 3000);
            });
            
            // File upload preview
            $('input[type="file"][accept*="image"]').on('change', function() {
                const file = this.files[0];
                const preview = $(this).siblings('.image-preview, .file-preview');
                
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (preview.length) {
                            preview.html(`<img src="${e.target.result}" alt="Preview" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 4px;">`);
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Confirmation dialogs for dangerous actions
            $('.confirm-action').on('click', function(e) {
                const message = $(this).data('confirm-message') || 'Are you sure you want to perform this action?';
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Auto-save functionality for forms
            $('.auto-save-form input, .auto-save-form textarea, .auto-save-form select').on('change', function() {
                const form = $(this).closest('form');
                const autosaveUrl = form.data('autosave-url');
                
                if (autosaveUrl) {
                    const formData = new FormData(form[0]);
                    formData.append('autosave', '1');
                    
                    $.ajax({
                        url: autosaveUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                showToast('Changes saved automatically', 'success');
                            }
                        },
                        error: function() {
                            // Silently fail for autosave
                        }
                    });
                }
            });
            
            // Tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
        
        // Utility functions
        function showToast(message, type = 'info') {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            let toastContainer = $('.toast-container');
            if (toastContainer.length === 0) {
                toastContainer = $('<div class="toast-container position-fixed top-0 end-0 p-3"></div>');
                $('body').append(toastContainer);
            }
            
            const toast = $(toastHtml);
            toastContainer.append(toast);
            
            const bsToast = new bootstrap.Toast(toast[0]);
            bsToast.show();
            
            toast.on('hidden.bs.toast', function() {
                toast.remove();
            });
        }
        
        function formatCurrency(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>
    
    <!-- Debug information (only in development) -->
    <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
    <div class="debug-info" style="position: fixed; bottom: 0; right: 0; background: #f8f9fa; padding: 0.5rem; font-size: 0.75rem; border: 1px solid #dee2e6; z-index: 9999;">
        <strong>Seller Debug Info:</strong><br>
        Memory: <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?>MB<br>
        Time: <?php echo round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 2); ?>ms<br>
        User: <?php echo htmlspecialchars(Session::getUserRole() ?? 'Guest'); ?> (ID: <?php echo Session::getUserId() ?? 'N/A'; ?>)
    </div>
    <?php endif; ?>
    
    <style>
        .seller-footer {
            background-color: #f8fafc;
            border-top: 1px solid #e5e7eb;
            padding: 2rem 0 1rem;
            margin-top: auto;
        }
        
        .footer-section h6 {
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .footer-section ul li {
            margin-bottom: 0.25rem;
        }
        
        .footer-section a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .footer-section a:hover {
            color: var(--seller-primary, #0654ba);
        }
        
        .toast-container {
            z-index: 9999;
        }
    </style>

</body>
</html>