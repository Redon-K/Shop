document.addEventListener('DOMContentLoaded', () => {
  console.log('Home.js loaded');
  
  // Link header category buttons to page sections
  document.querySelectorAll('.buttons > button:not(#favorites):not(#cart):not(.nav-link)').forEach(btn => {
    const txt = (btn.textContent || '').trim().toLowerCase();
    let selector = null;
    if (txt.includes('protein')) selector = '#Proteins';
    else if (txt.includes('pre')) selector = '#Pre';
    else if (txt.includes('vitamin')) selector = '#Vitamins';
    else if (txt.includes('supplement')) selector = '#Supplements';

    if (selector) {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const target = document.querySelector(selector);
        if (target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          // Add flash effect
          target.classList.add('flash-target');
          setTimeout(() => target.classList.remove('flash-target'), 900);
        }
      });
    }
  });

  // Account navigation - check login status
  const accountEl = document.getElementById('account');
  if (accountEl) {
    accountEl.addEventListener('click', (e) => {
      e.preventDefault();
      // Check if user is logged in
      const isLoggedIn = document.cookie.split(';').some(cookie => {
        return cookie.trim().startsWith('user=') || 
               cookie.trim().startsWith('PHPSESSID=') || 
               cookie.includes('user_id');
      });
      
      const target = isLoggedIn ? './Account.php' : './login.php';
      window.location.href = target;
    });
  }

  // Favorites/Wishlist navigation - UPDATED
  const favoritesEl = document.getElementById('favorites');
  if (favoritesEl) {
    favoritesEl.addEventListener('click', (e) => {
      e.preventDefault();
      // Check if user is logged in
      const isLoggedIn = document.cookie.split(';').some(cookie => {
        return cookie.trim().startsWith('user=') || 
               cookie.trim().startsWith('PHPSESSID=') || 
               cookie.includes('user_id');
      });
      
      if (isLoggedIn) {
        // If already on wishlist page, scroll to top
        if (window.location.pathname.includes('Wishlist.php')) {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          window.location.href = './Wishlist.php';
        }
      } else {
        window.location.href = './login.php';
      }
    });
  }

  // In-page navigation for anchor links on Home page
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
      const href = this.getAttribute('href') || '';
      const parts = href.split('#');
      const hash = parts[1];
      const onHome = location.pathname.endsWith('Home.php') || 
                     location.pathname === '/' || 
                     location.pathname === '' || 
                     location.href.toLowerCase().endsWith('/home.php');
      
      if (onHome && hash) {
        e.preventDefault();
        const target = document.getElementById(hash);
        if (target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          target.classList.add('flash-target');
          setTimeout(() => target.classList.remove('flash-target'), 900);
        }
      }
    });
  });

  // Logo click behavior
  const logoEl = document.getElementById('logo');
  if (logoEl) {
    logoEl.addEventListener('click', (e) => {
      const onHome = location.pathname.endsWith('Home.php') || 
                     location.pathname === '/' || 
                     location.pathname === '' || 
                     location.href.toLowerCase().endsWith('/home.php');
      if (onHome) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  }

  // Shop Now button
  const shopNowBtn = document.getElementById('shop-now');
  if (shopNowBtn) {
    shopNowBtn.addEventListener('click', () => {
      window.location.href = './Search.php';
    });
  }

  // Smooth hide-on-scroll navigation
  (function() {
    const nav = document.querySelector('.nav');
    if (!nav) return;

    let lastY = window.scrollY || 0;
    let translateY = 0;
    let ticking = false;
    let scrollEndTimer = null;

    const IGNORE_DELTA = 2;
    const SNAP_DELAY = 150;

    function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }

    function applyTransform() {
      nav.style.transform = `translateY(-${Math.round(translateY)}px)`;
      if (translateY >= nav.offsetHeight - 1) nav.classList.add('hidden');
      else nav.classList.remove('hidden');
      ticking = false;
    }

    function snapToEdge() {
      const h = nav.offsetHeight;
      if (translateY > h * 0.5) translateY = h;
      else translateY = 0;
      applyTransform();
    }

    function onScroll() {
      const currentY = window.scrollY || 0;
      const dy = currentY - lastY;
      
      if (Math.abs(dy) < IGNORE_DELTA) {
        lastY = currentY;
        return;
      }

      const h = nav.offsetHeight || 88;
      translateY = clamp(translateY + dy, 0, h);
      lastY = currentY;

      if (!ticking) {
        window.requestAnimationFrame(applyTransform);
        ticking = true;
      }

      if (scrollEndTimer) clearTimeout(scrollEndTimer);
      scrollEndTimer = setTimeout(snapToEdge, SNAP_DELAY);
    }

    window.addEventListener('resize', () => {
      translateY = 0;
      nav.style.transform = '';
      nav.classList.remove('hidden');
    });

    window.addEventListener('scroll', onScroll, { passive: true });
  })();

  // Cart system
  (function() {
    const cartBtn = document.getElementById('cart');

    function createPanel() {
      if (document.querySelector('.cart-panel')) return document.querySelector('.cart-panel');
      const panel = document.createElement('aside');
      panel.className = 'cart-panel';
      panel.innerHTML = `
        <header class="cart-header">
          <strong>Your Cart</strong>
          <button class="cart-close" aria-label="Close cart">×</button>
        </header>
        <div class="cart-body">
          <ul class="cart-items" role="list"></ul>
          <div class="cart-empty" style="display:none;opacity:.9;text-align:center;padding:40px 20px;color:rgba(248,250,252,0.6);">
            Your cart is empty.
          </div>
        </div>
        <footer class="cart-footer">
          <div class="cart-total">Total: $<span class="cart-total-amount">0.00</span></div>
          <div class="cart-actions">
            <button class="btn btn-primary checkout">Checkout</button>
          </div>
        </footer>
      `;
      document.body.appendChild(panel);
      
      // Close button
      panel.querySelector('.cart-close').addEventListener('click', () => panel.classList.remove('open'));
      
      // Checkout button
      const checkoutBtn = panel.querySelector('.checkout');
      if (checkoutBtn) {
        checkoutBtn.addEventListener('click', (e) => {
          e.preventDefault();
          window.location.href = 'CheckOut.php';
        });
      }
      
      return panel;
    }

    function readCart() {
      try {
        return JSON.parse(localStorage.getItem('cart') || '[]');
      } catch (e) { return []; }
    }

    function renderCart() {
      const panel = createPanel();
      const items = readCart();
      const list = panel.querySelector('.cart-items');
      const empty = panel.querySelector('.cart-empty');
      list.innerHTML = '';
      
      if (!items || items.length === 0) {
        empty.style.display = 'block';
        panel.querySelector('.cart-total-amount').textContent = '0.00';
        return;
      }
      
      empty.style.display = 'none';
      let total = 0;
      
      items.forEach((it, i) => {
        const li = document.createElement('li');
        li.className = 'cart-item';
        li.innerHTML = `
          <div class="ci-left">
            <div class="ci-name">${escapeHtml(it.name || 'Product')}</div>
            <div class="ci-meta">$${Number(it.price || 0).toFixed(2)}</div>
          </div>
          <div class="ci-right">
            <button class="ci-remove" data-i="${i}" aria-label="Remove">Remove</button>
          </div>
        `;
        list.appendChild(li);
        total += Number(it.price) || 0;
      });
      
      panel.querySelector('.cart-total-amount').textContent = total.toFixed(2);

      // Wire remove buttons
      panel.querySelectorAll('.ci-remove').forEach(btn => {
        btn.addEventListener('click', (e) => {
          const idx = Number(btn.getAttribute('data-i'));
          const arr = readCart();
          arr.splice(idx, 1);
          localStorage.setItem('cart', JSON.stringify(arr));
          document.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart: arr } }));
        });
      });
    }

    function toggleCart() {
      const panel = createPanel();
      panel.classList.toggle('open');
      if (panel.classList.contains('open')) renderCart();
    }

    function escapeHtml(s) { 
      return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c]); 
    }

    // Listen for cart updates
    document.addEventListener('cart-updated', (e) => {
      renderCart();
    });

    // Listen for storage events from other tabs
    window.addEventListener('storage', (e) => { 
      if (e.key === 'cart') renderCart(); 
    });

    // Add to cart button handler
    document.addEventListener('click', (ev) => {
      const btn = ev.target.closest && ev.target.closest('.add-to-cart');
      if (!btn) return;
      ev.preventDefault();
      
      // Get product info
      const card = btn.closest('.product-card');
      let name = '', price = 0, id = '';
      
      if (card) {
        const h = card.querySelector('h3');
        const p = card.querySelector('p');
        if (h) name = h.textContent.trim();
        if (p) {
          const txt = p.textContent.replace(/[^0-9.]/g, '').trim();
          price = Number(txt) || 0;
        }
        id = card.getAttribute('data-id') || btn.getAttribute('data-id') || Date.now().toString();
      }
      
      // Fallback to button attributes
      if (!name) name = btn.getAttribute('data-name') || btn.getAttribute('data-title') || 'Product';
      if (!price) price = Number(btn.getAttribute('data-price')) || 0;
      if (!id) id = btn.getAttribute('data-id') || Date.now().toString();

      // Add to localStorage cart
      try {
        const raw = localStorage.getItem('cart') || '[]';
        const cart = JSON.parse(raw);
        cart.push({ 
          id: id,
          name: name, 
          price: price, 
          addedAt: Date.now() 
        });
        localStorage.setItem('cart', JSON.stringify(cart));
        
        // Notify other listeners
        document.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart } }));
        
        // Button feedback
        const old = btn.textContent;
        btn.textContent = 'Added ✓';
        btn.disabled = true;
        btn.style.background = '#4CAF50';
        
        setTimeout(() => { 
          btn.textContent = old; 
          btn.disabled = false;
          btn.style.background = '';
        }, 1200);
        
        // Open cart panel
        const panel = createPanel();
        panel.classList.add('open');
        renderCart();
        
      } catch (err) {
        console.error('Cart write error', err);
      }
    });

    // Wire cart button
    if (cartBtn) {
      cartBtn.addEventListener('click', (e) => { 
        e.preventDefault(); 
        toggleCart(); 
      });
    }
  })();

  // Initialize cart on page load
  if (document.getElementById('cart')) {
    const panel = document.querySelector('.cart-panel');
    if (panel) {
      panel.classList.remove('open');
    }
  }
});