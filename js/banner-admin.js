/**
 * Banner Admin - Homepage Banner Editing Functionality
 * Handles modal display, file uploads, and AJAX form submission
 */

// Modal management
function showBannerModal(slotKey, bannerType) {
    const modal = document.getElementById('bannerEditModal');
    if (!modal) {
        console.error('Banner edit modal not found');
        return;
    }
    
    // Show loading state
    const form = document.getElementById('bannerEditForm');
    form.reset();
    
    // Set slot key and banner type in hidden fields
    document.getElementById('editSlotKey').value = slotKey;
    document.getElementById('editBannerType').value = bannerType;
    
    // Load current banner data if editing existing banner
    if (slotKey && slotKey !== 'new') {
        loadBannerData(slotKey);
    }
    
    // Show modal
    modal.style.display = 'block';
    document.body.classList.add('modal-open');
}

function hideBannerModal() {
    const modal = document.getElementById('bannerEditModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
}

// Edit banner function called from HTML
function editBanner(slotKey, bannerType) {
    showBannerModal(slotKey, bannerType);
}

// Load current banner data for editing
async function loadBannerData(slotKey) {
    try {
        const response = await fetch(`/api/banners/get.php?slot_key=${encodeURIComponent(slotKey)}`);
        const data = await response.json();
        
        if (data.success && data.banner) {
            const banner = data.banner;
            
            // Populate form fields
            document.getElementById('bannerTitle').value = banner.title || '';
            document.getElementById('bannerSubtitle').value = banner.subtitle || '';
            document.getElementById('bannerDescription').value = banner.description || '';
            document.getElementById('bannerLinkUrl').value = banner.link_url || '';
            document.getElementById('bannerButtonText').value = banner.button_text || '';
            document.getElementById('bannerImageUrl').value = banner.image_url || '';
            document.getElementById('bannerWidth').value = banner.width || '';
            document.getElementById('bannerHeight').value = banner.height || '';
            
            // Show current background image preview if exists
            if (banner.bg_image_path) {
                const preview = document.getElementById('currentImagePreview');
                if (preview) {
                    preview.src = banner.bg_image_path;
                    preview.style.display = 'block';
                }
            }
        }
    } catch (error) {
        console.error('Error loading banner data:', error);
        showNotification('Error loading banner data', 'error');
    }
}

// Handle form submission
async function saveBanner(event) {
    event.preventDefault();
    
    const form = document.getElementById('bannerEditForm');
    const formData = new FormData(form);
    
    // Show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Saving...';
    submitButton.disabled = true;
    
    try {
        const response = await fetch('/admin/banner-save.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.ok) {
            showNotification('Banner saved successfully!', 'success');
            hideBannerModal();
            
            // Reload the page to show updated banner
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.error || 'Error saving banner', 'error');
        }
    } catch (error) {
        console.error('Error saving banner:', error);
        showNotification('Error saving banner', 'error');
    } finally {
        // Reset button state
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    }
}

// File upload preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Notification system
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Initialize event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Modal close handlers
    const modal = document.getElementById('bannerEditModal');
    if (modal) {
        // Close on background click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideBannerModal();
            }
        });
        
        // Close button
        const closeBtn = modal.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', hideBannerModal);
        }
    }
    
    // Form submission
    const form = document.getElementById('bannerEditForm');
    if (form) {
        form.addEventListener('submit', saveBanner);
    }
    
    // File input change handler
    const fileInput = document.getElementById('bannerImage');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            previewImage(this);
        });
    }
    
    // ESC key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideBannerModal();
        }
    });
});