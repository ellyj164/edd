/**
 * Purchase Flows Enhanced JavaScript
 * Provides better UX with toast notifications and loading states
 * 
 * Usage:
 * 1. Include this file in your product/cart pages
 * 2. Ensure assets/js/ui.js is loaded (for Toast notifications)
 * 3. Set global variables: productId, isLoggedIn, csrfToken
 */

class PurchaseFlowUI {
    constructor() {
        this.productId = window.productId || null;
        this.isLoggedIn = window.isLoggedIn || false;
        this.csrfToken = window.csrfToken || '';
        this.loadingStates = new Map();
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        if (typeof Toast !== 'undefined') {
            Toast.show(message, type, 3000);
        } else {
            // Fallback to alert if Toast is not available
            alert(message);
        }
    }

    /**
     * Set loading state for a button
     */
    setLoading(button, loading = true) {
        if (loading) {
            this.loadingStates.set(button, {
                text: button.textContent,
                disabled: button.disabled
            });
            button.disabled = true;
            button.classList.add('loading');
            
            // Add spinner if not present
            if (!button.querySelector('.spinner')) {
                const spinner = document.createElement('span');
                spinner.className = 'spinner inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2';
                button.insertBefore(spinner, button.firstChild);
            }
        } else {
            const state = this.loadingStates.get(button);
            if (state) {
                button.disabled = state.disabled;
                button.textContent = state.text;
                button.classList.remove('loading');
                this.loadingStates.delete(button);
            }
        }
    }

    /**
     * Check if user is logged in
     */
    requireLogin() {
        if (!this.isLoggedIn) {
            this.showToast('Please login to continue', 'warning');
            setTimeout(() => {
                const returnUrl = encodeURIComponent(window.location.href);
                window.location.href = `/login.php?redirect=${returnUrl}`;
            }, 1000);
            return false;
        }
        return true;
    }

    /**
     * Update cart count badge in header
     */
    updateCartBadge(count) {
        const badges = document.querySelectorAll('.cart-count, [data-cart-count]');
        badges.forEach(badge => {
            badge.textContent = count;
            // Add bounce animation
            badge.classList.add('animate-bounce');
            setTimeout(() => badge.classList.remove('animate-bounce'), 500);
        });
    }

    /**
     * Add item to cart
     */
    async addToCart(productId = null, quantity = 1) {
        if (!this.requireLogin()) return;

        productId = productId || this.productId;
        const button = event?.target;

        if (button) this.setLoading(button, true);

        try {
            const response = await fetch('/api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'add',
                    product_id: parseInt(productId),
                    quantity: parseInt(quantity)
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast(data.message || 'Item added to cart!', 'success');
                // Handle both response formats: data.data.count and data.count
                const count = data.data?.count || data.count;
                if (count) {
                    this.updateCartBadge(count);
                }
            } else {
                this.showToast(data.error || 'Failed to add item to cart', 'error');
            }
        } catch (error) {
            console.error('Add to cart error:', error);
            this.showToast('Network error. Please try again.', 'error');
        } finally {
            if (button) this.setLoading(button, false);
        }
    }

    /**
     * Buy now - add to cart and redirect to checkout
     */
    async buyNow(productId = null, quantity = 1) {
        if (!this.requireLogin()) return;

        productId = productId || this.productId;
        const button = event?.target;

        if (button) this.setLoading(button, true);

        try {
            const response = await fetch(`/product.php?id=${productId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=buy_now&quantity=${quantity}&csrf_token=${encodeURIComponent(this.csrfToken)}`
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Redirecting to checkout...', 'success');
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                }
            } else {
                this.showToast(data.message || 'Failed to process purchase', 'error');
                if (button) this.setLoading(button, false);
            }
        } catch (error) {
            console.error('Buy now error:', error);
            this.showToast('Network error. Please try again.', 'error');
            if (button) this.setLoading(button, false);
        }
    }

    /**
     * Toggle wishlist
     */
    async toggleWishlist(productId = null) {
        if (!this.requireLogin()) return;

        productId = productId || this.productId;
        const button = event?.target;
        const isWishlisted = button?.textContent.includes('Remove') || 
                            button?.textContent.includes('In Wishlist') ||
                            button?.textContent.includes('❤️');

        if (button) this.setLoading(button, true);

        try {
            const response = await fetch('/api/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: isWishlisted ? 'remove' : 'add',
                    product_id: parseInt(productId)
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast(data.message, 'success');
                
                // Update button text
                if (button) {
                    if (isWishlisted) {
                        button.innerHTML = '🤍 Add to Wishlist';
                    } else {
                        button.innerHTML = '❤️ Remove from Wishlist';
                    }
                }
            } else {
                this.showToast(data.error || 'Failed to update wishlist', 'error');
            }
        } catch (error) {
            console.error('Wishlist error:', error);
            this.showToast('Network error. Please try again.', 'error');
        } finally {
            if (button) this.setLoading(button, false);
        }
    }

    /**
     * Toggle watchlist
     */
    async toggleWatchlist(productId = null) {
        if (!this.requireLogin()) return;

        productId = productId || this.productId;
        const button = event?.target;
        const isWatchlisted = button?.textContent.includes('Remove') || 
                             button?.textContent.includes('Watching') ||
                             button?.textContent.includes('👁');

        if (button) this.setLoading(button, true);

        try {
            const response = await fetch('/api/watchlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: isWatchlisted ? 'remove' : 'add',
                    product_id: parseInt(productId)
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast(data.message, 'success');
                
                // Update button text
                if (button) {
                    if (isWatchlisted) {
                        button.innerHTML = '👁️ Watch';
                    } else {
                        button.innerHTML = '👁️ Watching';
                    }
                }
            } else {
                this.showToast(data.error || 'Failed to update watchlist', 'error');
            }
        } catch (error) {
            console.error('Watchlist error:', error);
            this.showToast('Network error. Please try again.', 'error');
        } finally {
            if (button) this.setLoading(button, false);
        }
    }

    /**
     * Update cart item quantity
     */
    async updateCartQuantity(productId, quantity) {
        try {
            const response = await fetch('/api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'update',
                    product_id: parseInt(productId),
                    quantity: parseInt(quantity)
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Cart updated', 'success');
                if (data.data?.count) {
                    this.updateCartBadge(data.data.count);
                }
                return true;
            } else {
                this.showToast(data.error || 'Failed to update cart', 'error');
                return false;
            }
        } catch (error) {
            console.error('Update cart error:', error);
            this.showToast('Network error. Please try again.', 'error');
            return false;
        }
    }

    /**
     * Remove item from cart
     */
    async removeFromCart(productId) {
        if (!confirm('Remove this item from your cart?')) return;

        try {
            const response = await fetch('/api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'remove',
                    product_id: parseInt(productId)
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Item removed from cart', 'success');
                if (data.data?.count) {
                    this.updateCartBadge(data.data.count);
                }
                
                // Remove the row from the table if on cart page
                const row = document.querySelector(`tr[data-product-id="${productId}"]`);
                if (row) {
                    row.remove();
                }
                
                // Reload page to update totals
                setTimeout(() => location.reload(), 500);
                return true;
            } else {
                this.showToast(data.error || 'Failed to remove item', 'error');
                return false;
            }
        } catch (error) {
            console.error('Remove from cart error:', error);
            this.showToast('Network error. Please try again.', 'error');
            return false;
        }
    }

    /**
     * Clear entire cart
     */
    async clearCart() {
        if (!confirm('Are you sure you want to clear your entire cart?')) return;

        try {
            const response = await fetch('/api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'clear'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Cart cleared', 'success');
                this.updateCartBadge(0);
                setTimeout(() => location.reload(), 500);
                return true;
            } else {
                this.showToast(data.error || 'Failed to clear cart', 'error');
                return false;
            }
        } catch (error) {
            console.error('Clear cart error:', error);
            this.showToast('Network error. Please try again.', 'error');
            return false;
        }
    }
}

// Initialize global instance
const purchaseFlow = new PurchaseFlowUI();

// Expose global functions for backward compatibility
function addToCart(event, productId, quantity = 1) {
    // Prevent default button behavior
    if (event && event.preventDefault) {
        event.preventDefault();
    }
    return purchaseFlow.addToCart(productId, quantity);
}

function buyNow(productId, quantity) {
    return purchaseFlow.buyNow(productId, quantity);
}

function toggleWishlist(productId) {
    return purchaseFlow.toggleWishlist(productId);
}

function toggleWatchlist(productId) {
    return purchaseFlow.toggleWatchlist(productId);
}

function updateCartQuantity(productId, quantity) {
    return purchaseFlow.updateCartQuantity(productId, quantity);
}

function removeFromCart(productId) {
    return purchaseFlow.removeFromCart(productId);
}

function clearCart() {
    return purchaseFlow.clearCart();
}

// Export for ES6 modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PurchaseFlowUI, purchaseFlow };
}
