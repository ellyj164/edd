<?php
/**
 * Admin Footer Include - Required in all admin pages
 * Standardized admin page footer with scripts
 */
?>
            </div> <!-- End admin-content -->
        </div> <!-- End row -->
    </div> <!-- End container-fluid -->

    <!-- JavaScript Libraries -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Admin Common Scripts -->
    <script>
        $(document).ready(function() {
            // Initialize DataTables with common options
            $('.data-table').DataTable({
                "pageLength": 25,
                "order": [[ 0, "desc" ]],
                "responsive": true,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
            
            // CSRF token for AJAX requests
            $.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                        xhr.setRequestHeader("X-CSRFToken", $('meta[name=csrf-token]').attr('content'));
                    }
                }
            });
            
            // Auto-hide alerts after 5 seconds
            $('.alert').each(function() {
                const alert = $(this);
                setTimeout(function() {
                    alert.fadeOut();
                }, 5000);
            });
            
            // Confirmation dialogs for dangerous actions
            $('.confirm-action').on('click', function(e) {
                const message = $(this).data('confirm-message') || 'Are you sure you want to perform this action?';
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Status badge updates
            $('.status-select').on('change', function() {
                const select = $(this);
                const form = select.closest('form');
                if (form.length) {
                    form.submit();
                }
            });
            
            // File upload progress
            $('input[type="file"]').on('change', function() {
                const file = this.files[0];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                if (file && file.size > maxSize) {
                    alert('File size must be less than 10MB');
                    $(this).val('');
                    return false;
                }
            });
            
            // Form validation
            $('.admin-form').on('submit', function() {
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                
                // Disable submit button to prevent double submission
                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Processing...');
                
                // Re-enable after 3 seconds as fallback
                setTimeout(function() {
                    submitBtn.prop('disabled', false);
                    submitBtn.html(submitBtn.data('original-text') || 'Submit');
                }, 3000);
            });
            
            // Store original button text
            $('button[type="submit"]').each(function() {
                $(this).data('original-text', $(this).html());
            });
            
            // Tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Auto-refresh elements (for real-time updates)
            $('.auto-refresh').each(function() {
                const element = $(this);
                const interval = element.data('refresh-interval') || 30000; // 30 seconds default
                const url = element.data('refresh-url');
                
                if (url) {
                    setInterval(function() {
                        $.get(url, function(data) {
                            element.html(data);
                        }).fail(function() {
                            console.log('Auto-refresh failed for element');
                        });
                    }, interval);
                }
            });
        });
        
        // Common admin functions
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
        
        // Bulk actions handler
        function handleBulkActions() {
            $('.bulk-action-form').on('submit', function(e) {
                const form = $(this);
                const action = form.find('select[name="bulk_action"]').val();
                const selected = form.find('input[name="selected_items[]"]:checked');
                
                if (!action) {
                    e.preventDefault();
                    alert('Please select an action');
                    return false;
                }
                
                if (selected.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one item');
                    return false;
                }
                
                const confirmMessage = `Are you sure you want to ${action} ${selected.length} item(s)?`;
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Select all checkbox
            $('.select-all').on('change', function() {
                const checked = $(this).prop('checked');
                $('.item-select').prop('checked', checked);
            });
            
            // Update select all when individual items change
            $('.item-select').on('change', function() {
                const total = $('.item-select').length;
                const checked = $('.item-select:checked').length;
                $('.select-all').prop('checked', total === checked);
            });
        }
        
        // Initialize bulk actions
        $(document).ready(function() {
            handleBulkActions();
        });
    </script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>
    
    <!-- Debug information (only in development) -->
    <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
    <div class="debug-info" style="position: fixed; bottom: 0; right: 0; background: #f8f9fa; padding: 0.5rem; font-size: 0.75rem; border: 1px solid #dee2e6;">
        <strong>Debug Info:</strong><br>
        Memory: <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?>MB<br>
        Time: <?php echo round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 2); ?>ms<br>
        User: <?php echo htmlspecialchars(getCurrentUserRole()); ?> (ID: <?php echo getCurrentUserId(); ?>)
    </div>
    <?php endif; ?>
    
    <!-- Mobile Bottom Navigation Bar (eBay-style) -->
    <nav class="mobile-bottom-nav" id="mobileBottomNav">
        <a href="/" class="mobile-bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home mobile-bottom-nav-icon"></i>
            <span class="mobile-bottom-nav-label">Home</span>
        </a>
        <a href="/account.php" class="mobile-bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'account.php' ? 'active' : ''; ?>">
            <i class="fas fa-user mobile-bottom-nav-icon"></i>
            <span class="mobile-bottom-nav-label">My Feza</span>
        </a>
        <a href="/search.php" class="mobile-bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : ''; ?>">
            <i class="fas fa-search mobile-bottom-nav-icon"></i>
            <span class="mobile-bottom-nav-label">Search</span>
        </a>
        <a href="/messages.php" class="mobile-bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : ''; ?>">
            <div class="mobile-bottom-nav-icon-wrapper">
                <i class="fas fa-envelope mobile-bottom-nav-icon"></i>
                <?php
                // Show badge if user has unread messages
                if (Session::isLoggedIn()) {
                    try {
                        $db = db();
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = 0");
                        $stmt->execute([Session::getUserId()]);
                        $result = $stmt->fetch();
                        $unreadCount = $result['count'] ?? 0;
                        if ($unreadCount > 0) {
                            echo '<span class="mobile-bottom-nav-badge">' . min($unreadCount, 99) . '</span>';
                        }
                    } catch (Exception $e) {
                        // Silently fail if messages table doesn't exist
                    }
                }
                ?>
            </div>
            <span class="mobile-bottom-nav-label">Inbox</span>
        </a>
        <?php
        $userRole = getCurrentUserRole();
        $isSellerOrAdmin = $userRole === 'seller' || $userRole === 'admin';
        $sellingUrl = Session::isLoggedIn() && $isSellerOrAdmin ? getSellingUrl() : '/sell.php';
        ?>
        <a href="<?php echo $sellingUrl; ?>" class="mobile-bottom-nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'sell') !== false || strpos($_SERVER['PHP_SELF'], 'seller') !== false ? 'active' : ''; ?>">
            <i class="fas fa-tag mobile-bottom-nav-icon"></i>
            <span class="mobile-bottom-nav-label">Selling</span>
        </a>
    </nav>
    
    <style>
        /* Mobile Bottom Navigation Bar - eBay Style */
        .mobile-bottom-nav {
            display: none; /* Hidden by default, shown on mobile */
        }
        
        @media (max-width: 768px) {
            .mobile-bottom-nav {
                display: flex;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                border-top: 1px solid #e5e5e5;
                justify-content: space-around;
                align-items: center;
                padding: 8px 0 6px;
                z-index: 1000;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
            }
            
            /* Hide bottom nav when scrolling down */
            .mobile-bottom-nav.hide {
                transform: translateY(100%);
            }
            
            .mobile-bottom-nav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                color: #333;
                font-size: 10px;
                min-width: 60px;
                padding: 4px 8px;
                transition: color 0.2s ease;
                touch-action: manipulation;
                position: relative;
            }
            
            .mobile-bottom-nav-item:hover,
            .mobile-bottom-nav-item.active {
                color: #0654ba;
            }
            
            .mobile-bottom-nav-icon {
                font-size: 22px;
                margin-bottom: 2px;
            }
            
            .mobile-bottom-nav-label {
                font-weight: 400;
                white-space: nowrap;
            }
            
            .mobile-bottom-nav-item.active .mobile-bottom-nav-label {
                font-weight: 600;
            }
            
            .mobile-bottom-nav-icon-wrapper {
                position: relative;
                display: inline-block;
            }
            
            .mobile-bottom-nav-badge {
                position: absolute;
                top: -5px;
                right: -8px;
                background-color: #e53238;
                color: white;
                border-radius: 10px;
                min-width: 18px;
                height: 18px;
                font-size: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                padding: 0 4px;
            }
            
            /* Add padding to body to prevent content from being hidden behind bottom nav */
            body {
                padding-bottom: 60px;
            }
        }
    </style>
    
    <script>
        // Mobile bottom navigation scroll behavior - only on mobile devices
        function initMobileBottomNavScroll() {
            // Check if mobile
            if (window.innerWidth > 768) return;
            
            let lastBottomNavScrollTop = 0;
            let bottomNavScrollThreshold = 5; // Minimum scroll distance to trigger
            let bottomNavTicking = false;
            
            function handleBottomNavScroll() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const bottomNav = document.getElementById('mobileBottomNav');
                
                if (!bottomNav) {
                    bottomNavTicking = false;
                    return;
                }
                
                // Only trigger if scroll is beyond threshold
                if (Math.abs(scrollTop - lastBottomNavScrollTop) < bottomNavScrollThreshold) {
                    bottomNavTicking = false;
                    return;
                }
                
                if (scrollTop > lastBottomNavScrollTop && scrollTop > 50) {
                    // Scrolling down - hide bottom nav
                    bottomNav.classList.add('hide');
                } else {
                    // Scrolling up - show bottom nav
                    bottomNav.classList.remove('hide');
                }
                
                lastBottomNavScrollTop = scrollTop <= 0 ? 0 : scrollTop;
                bottomNavTicking = false;
            }
            
            window.addEventListener('scroll', function() {
                if (!bottomNavTicking) {
                    window.requestAnimationFrame(handleBottomNavScroll);
                    bottomNavTicking = true;
                }
            });
        }
        
        // Initialize on load
        initMobileBottomNavScroll();
        
        // Re-initialize on resize (handles device rotation)
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                initMobileBottomNavScroll();
            }, 250);
        });
    </script>
    
</body>
</html>