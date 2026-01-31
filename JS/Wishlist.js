// Wishlist Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Wishlist.js loaded');
    
    // Make favorites button work
    const favoritesBtn = document.getElementById('favorites');
    if (favoritesBtn) {
        favoritesBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // If already on wishlist page, scroll to top
            if (window.location.pathname.includes('Wishlist.php')) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                window.location.href = './Wishlist.php';
            }
        });
    }
    
    // Handle remove from wishlist buttons
    const removeButtons = document.querySelectorAll('.remove-btn');
    removeButtons.forEach(button => {
        button.addEventListener('click', handleRemoveFromWishlist);
    });
    
    // Toast notification function
    window.showToast = function(message, isError = false) {
        const existing = document.querySelector('.toast-notification');
        if (existing) existing.remove();
        
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            right: 20px;
            bottom: 24px;
            padding: 12px 18px;
            background: ${isError ? 'rgba(244,67,54,0.95)' : 'rgba(87,87,243,0.95)'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 2000;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        // Animate in
        requestAnimationFrame(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        });
        
        // Remove after delay
        setTimeout(() => {
            toast.style.transform = 'translateY(20px)';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    };
});

// Handle remove from wishlist
async function handleRemoveFromWishlist(event) {
    const button = event.currentTarget;
    const productId = button.dataset.productId;
    const wishlistCard = button.closest('.wishlist-card');
    
    try {
        const response = await fetch('../PHP/wishlist_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'remove', 
                product_id: parseInt(productId) 
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Remove the card from the UI with animation
            wishlistCard.style.opacity = '0';
            wishlistCard.style.transform = 'translateY(20px)';
            wishlistCard.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                wishlistCard.remove();
                
                // Update wishlist count
                updateWishlistCount();
                
                // Show notification
                if (window.showToast) {
                    window.showToast('Removed from wishlist');
                }
            }, 300);
        } else {
            if (window.showToast) {
                window.showToast(result.message || 'Error removing from wishlist', true);
            }
        }
    } catch (error) {
        console.error('Wishlist error:', error);
        if (window.showToast) {
            window.showToast('Error removing from wishlist', true);
        }
    }
}

// Update wishlist count after removal
function updateWishlistCount() {
    const wishlistCount = document.querySelector('.wishlist-count');
    const wishlistGrid = document.querySelector('.wishlist-grid');
    const emptyState = document.querySelector('.empty-state');
    
    if (!wishlistCount || !wishlistGrid || !emptyState) return;
    
    // Count remaining wishlist items
    const remainingItems = document.querySelectorAll('.wishlist-card').length;
    
    if (remainingItems === 0) {
        // Hide grid and show empty state
        wishlistGrid.style.display = 'none';
        emptyState.style.display = 'block';
        wishlistCount.textContent = '0 items';
    } else {
        // Update count
        wishlistCount.textContent = remainingItems + ' item' + (remainingItems !== 1 ? 's' : '');
    }
}
