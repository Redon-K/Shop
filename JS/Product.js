(function(){
  console.log('Product page loaded');
  
  function $(selector) { return document.querySelector(selector); }
  function $$(selector) { return document.querySelectorAll(selector); }

  const tabs = $$('.tab');
  const tabContents = $$('.tab-content');
  
  if (tabs.length > 0) {
    tabs.forEach(tab => {
      tab.addEventListener('click', function() {
        tabs.forEach(t => t.classList.remove('active'));
        tabContents.forEach(content => content.style.display = 'none');
        
        this.classList.add('active');
        const tabId = this.getAttribute('data-tab') || this.textContent.toLowerCase();
        const targetContent = document.getElementById(tabId);
        if (targetContent) {
          targetContent.style.display = 'block';
        }
      });
    });
    
    if (tabs[0]) tabs[0].click();
  }

  let selectedSize = null;
  const sizeButtons = $$('.size');
  sizeButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      sizeButtons.forEach(b => b.classList.remove('selected'));
      this.classList.add('selected');
      selectedSize = this.textContent.trim();
    });
    
    if (!selectedSize && sizeButtons[0]) {
      sizeButtons[0].click();
    }
  });

  const addToCartBtn = $('.btn.add');
  if (addToCartBtn) {
    addToCartBtn.addEventListener('click', addToCart);
  }

  const buyNowBtn = $('.btn.buy');
  if (buyNowBtn) {
    buyNowBtn.addEventListener('click', function() {
      if (addToCartBtn) {
        addToCartBtn.click();
        setTimeout(() => {
          window.location.href = 'CheckOut.php';
        }, 300);
      }
    });
  }

  const wishlistBtn = $('.wishlist-btn');
  if (wishlistBtn) {
    checkWishlistStatus();
    
    wishlistBtn.addEventListener('click', toggleWishlist);
  }

  function addToCart() {
    const product = {
      id: addToCartBtn.getAttribute('data-id') || Date.now().toString(),
      name: $('.prod-title')?.textContent?.trim() || 'Product',
      price: parseFloat($('.price')?.textContent?.replace(/[^0-9.]/g, '') || 0),
      size: selectedSize || '1 kg',
      quantity: 1
    };

    try {
      const cart = JSON.parse(localStorage.getItem('cart') || '[]');
      
      cart.push({
        ...product,
        addedAt: Date.now()
      });
      
      localStorage.setItem('cart', JSON.stringify(cart));
      
      document.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart } }));
      
      showMessage('Added to cart');
      
      const originalText = addToCartBtn.textContent;
      addToCartBtn.textContent = 'Added ✓';
      addToCartBtn.disabled = true;
      addToCartBtn.style.background = '#4CAF50';
      
      setTimeout(() => {
        addToCartBtn.textContent = originalText;
        addToCartBtn.disabled = false;
        addToCartBtn.style.background = '';
      }, 1500);
      
    } catch (error) {
      console.error('Error adding to cart:', error);
      showMessage('Error adding to cart', true);
    }
  }

  async function checkWishlistStatus() {
    const productId = wishlistBtn.getAttribute('data-id');
    if (!productId) return;
    
    const isLoggedIn = document.cookie.includes('PHPSESSID');
    
    if (!isLoggedIn) {
      wishlistBtn.textContent = '♡';
      wishlistBtn.title = 'Add to wishlist (login required)';
      return;
    }
    
    try {
      const response = await fetch('../PHP/wishlist_check.php?product_id=' + productId);
      const data = await response.json();
      
      if (data.loggedIn && data.inWishlist) {
        wishlistBtn.textContent = '❤️';
        wishlistBtn.style.color = '#ff4444';
        wishlistBtn.title = 'Remove from wishlist';
      } else {
        wishlistBtn.textContent = '♡';
        wishlistBtn.style.color = '';
        wishlistBtn.title = 'Add to wishlist';
      }
    } catch (error) {
      console.error('Error checking wishlist status:', error);
    }
  }

  async function toggleWishlist() {
    const productId = wishlistBtn.getAttribute('data-id');
    if (!productId) return;
    
    const isLoggedIn = document.cookie.includes('PHPSESSID');
    
    if (!isLoggedIn) {
      showMessage('Please login to use wishlist', true);
      setTimeout(() => {
        window.location.href = 'login.php';
      }, 1500);
      return;
    }
    
    try {
      const response = await fetch('../PHP/wishlist_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          action: 'toggle', 
          product_id: parseInt(productId) 
        })
      });
      
      const result = await response.json();
      
      if (result.success) {
        if (result.action === 'added') {
          wishlistBtn.textContent = '❤️';
          wishlistBtn.style.color = '#ff4444';
          wishlistBtn.title = 'Remove from wishlist';
          showMessage('Added to wishlist');
        } else if (result.action === 'removed') {
          wishlistBtn.textContent = '♡';
          wishlistBtn.style.color = '';
          wishlistBtn.title = 'Add to wishlist';
          showMessage('Removed from wishlist');
        } else if (result.action === 'already_exists') {
          wishlistBtn.textContent = '❤️';
          wishlistBtn.style.color = '#ff4444';
          wishlistBtn.title = 'Remove from wishlist';
          showMessage('Already in wishlist');
        }
      } else {
        showMessage(result.message || 'Error updating wishlist', true);
      }
    } catch (error) {
      console.error('Wishlist error:', error);
      showMessage('Error updating wishlist', true);
    }
  }

  function showMessage(text, isError = false) {
    const existingMessage = $('.product-message');
    if (existingMessage) existingMessage.remove();
    
    const message = document.createElement('div');
    message.className = 'product-message';
    message.textContent = text;
    message.style.cssText = `
      position: fixed;
      bottom: 20px;
      right: 20px;
      padding: 12px 20px;
      background: ${isError ? '#f44336' : '#5757f3'};
      color: white;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 1000;
      font-weight: 500;
      transform: translateY(20px);
      opacity: 0;
      transition: transform 0.3s, opacity 0.3s;
    `;
    
    document.body.appendChild(message);
    
    requestAnimationFrame(() => {
      message.style.transform = 'translateY(0)';
      message.style.opacity = '1';
    });
    
    setTimeout(() => {
      message.style.transform = 'translateY(20px)';
      message.style.opacity = '0';
      setTimeout(() => {
        if (message.parentNode) message.remove();
      }, 300);
    }, 2000);
  }

  console.log('Product page initialized');
})();