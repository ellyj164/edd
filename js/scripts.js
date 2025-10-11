/**
 * FezaMarket E-Commerce Platform JavaScript - Extensions
 * Additional frontend functionality and interactions
 */

// Only add extensions if FezaMarket already exists
if (typeof FezaMarket !== 'undefined') {
    // Extend existing FezaMarket object with additional methods
    Object.assign(FezaMarket, {
        // Enhanced cart functionality
        enhancedCartUpdate: function() {
            // Additional cart update logic here
            console.log('Enhanced cart update functionality');
        },
        
        // Advanced search features  
        advancedSearch: function() {
            // Advanced search logic here
            console.log('Advanced search functionality');
        },
        
        // Additional initialization
        initExtensions: function() {
            this.enhancedCartUpdate();
            this.advancedSearch();
        }
    });
    
    // Initialize extensions
    document.addEventListener('DOMContentLoaded', function() {
        if (FezaMarket.initExtensions) {
            FezaMarket.initExtensions();
        }
    });
} else {
    console.log('FezaMarket core object not found - scripts.js extensions not loaded');
}
