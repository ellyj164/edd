<?php
/**
 * Banner Edit Modal
 * Provides admin interface for editing homepage banners
 */
?>

<!-- Banner Edit Modal -->
<div id="bannerEditModal" class="banner-modal" style="display: none;">
    <div class="modal-backdrop"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h2>Edit Banner</h2>
            <button type="button" class="modal-close">&times;</button>
        </div>
        
        <form id="bannerEditForm" enctype="multipart/form-data">
            <input type="hidden" id="editSlotKey" name="slot_key" value="">
            <input type="hidden" id="editBannerType" name="banner_type" value="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="bannerTitle">Title *</label>
                        <input type="text" id="bannerTitle" name="title" required maxlength="255" 
                               placeholder="Banner title">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bannerSubtitle">Subtitle</label>
                        <input type="text" id="bannerSubtitle" name="subtitle" maxlength="500" 
                               placeholder="Optional subtitle">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bannerDescription">Description</label>
                        <textarea id="bannerDescription" name="description" rows="3" 
                                  placeholder="Optional description"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bannerImage">Background Image (Upload)</label>
                        <input type="file" id="bannerImage" name="bg_image" 
                               accept="image/jpeg,image/jpg,image/png,image/webp">
                        <small class="form-help">Supported formats: JPG, PNG, WebP. Max size: 5MB</small>
                        
                        <!-- Current image preview -->
                        <div class="image-preview-container">
                            <img id="currentImagePreview" class="current-image-preview" style="display: none;" alt="Current image">
                            <img id="imagePreview" class="image-preview" style="display: none;" alt="Image preview">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bannerImageUrl">Foreground/Overlay Image URL</label>
                        <input type="url" id="bannerImageUrl" name="image_url" 
                               placeholder="https://example.com/overlay.jpg">
                        <small class="form-help">Optional overlay image URL</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bannerFgImage">Foreground/Overlay Image (Upload)</label>
                        <input type="file" id="bannerFgImage" name="fg_image" 
                               accept="image/jpeg,image/jpg,image/png,image/webp">
                        <small class="form-help">Optional foreground/overlay image upload</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bannerWidth">Width (pixels)</label>
                        <input type="number" id="bannerWidth" name="width" min="1" 
                               placeholder="e.g., 1200">
                        <small class="form-help">Optional width constraint</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bannerHeight">Height (pixels)</label>
                        <input type="number" id="bannerHeight" name="height" min="1" 
                               placeholder="e.g., 400">
                        <small class="form-help">Optional height constraint</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bannerLinkUrl">Link URL</label>
                        <input type="url" id="bannerLinkUrl" name="link_url" 
                               placeholder="https://example.com">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bannerButtonText">Button Text</label>
                        <input type="text" id="bannerButtonText" name="button_text" maxlength="100" 
                               placeholder="Shop Now">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideBannerModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Banner</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Banner Modal Styles */
.banner-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
}

.modal-container {
    position: relative;
    background: white;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
    transition: color 0.2s ease;
}

.modal-close:hover {
    color: #374151;
}

.modal-body {
    padding: 24px;
}

.form-row {
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
    font-size: 14px;
}

.form-group input,
.form-group textarea {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
    color: #6b7280;
    font-size: 12px;
    margin-top: 4px;
}

.image-preview-container {
    margin-top: 12px;
}

.current-image-preview,
.image-preview {
    max-width: 200px;
    max-height: 120px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    object-fit: cover;
    margin-top: 8px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px 24px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
    border-radius: 0 0 12px 12px;
}

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

body.modal-open {
    overflow: hidden;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .modal-container {
        width: 95%;
        margin: 20px;
        max-height: 95vh;
    }
    
    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 16px;
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>