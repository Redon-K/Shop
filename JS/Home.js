

document.addEventListener('DOMContentLoaded', () => {
  // Link header category buttons to page sections
  document.querySelectorAll('.buttons > button').forEach(btn => {
    const txt = (btn.textContent || '').trim().toLowerCase();
    let selector = null;
    if (txt.includes('protein')) selector = '.Proteins';
    else if (txt.includes('pre')) selector = '.Pre';
    else if (txt.includes('vitamin')) selector = '.Vitamins';
    else if (txt.includes('supplement')) selector = '.Supplements';

    if (selector) {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const target = document.querySelector(selector);
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    }
  });

  // Account navigation is handled by a normal link in the HTML markup.
  // To avoid accidental automatic redirects, use a JS click handler that
  // navigates only when the user actually clicks the icon.
  const accountEl = document.getElementById('account');
  if (accountEl) {
    accountEl.addEventListener('click', (e) => {
      e.preventDefault();
      const target = accountEl.getAttribute('data-target') || 'Account.html';
      window.location.href = target;
    });
  }

  // If we're on the Home page, intercept clicks on .nav-link anchors and
  // perform a smooth in-page slide/scroll to the target section instead of
  // navigating. If we're on another page, the anchor will navigate to
  // Home.html#Section and the browser will land on the target there.
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
      // resolve hash part (target id) from href
      const href = this.getAttribute('href') || '';
      const parts = href.split('#');
      const hash = parts[1];
      const onHome = location.pathname.endsWith('Home.html') || location.pathname === '/' || location.pathname === '' || location.href.toLowerCase().endsWith('/home.html');
      if (onHome && hash) {
        e.preventDefault();
        const target = document.getElementById(hash);
        if (target) {
          // smooth scroll the heading into view and briefly flash it
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          target.classList.add('flash-target');
          setTimeout(() => target.classList.remove('flash-target'), 900);
        }
      }
      // otherwise let the anchor behave normally (navigate to Home.html#...)
    });
  });

  // Logo click: if on Home, scroll to top; otherwise navigate to Home
  const logoEl = document.getElementById('logo');
  if (logoEl) {
    logoEl.addEventListener('click', (e) => {
      const onHome = location.pathname.endsWith('Home.html') || location.pathname === '/' || location.pathname === '' || location.href.toLowerCase().endsWith('/home.html');
      if (onHome) {
        // prevent any default anchor behaviour if logo is wrapped in a link
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        // If clicked from another page, ensure we navigate to Home
        // If the logo is wrapped in an <a href="Home.html"> that would happen
        // naturally; defensively set location to Home.html.
        e.preventDefault();
        window.location.href = 'Home.html';
      }
    });
  }

  // Smooth follow hide-on-scroll: translate nav proportionally to scroll delta
  // so the header moves down with the user's scrolling and snaps when scrolling stops.
  (function() {
    const nav = document.querySelector('.nav');
    if (!nav) return;

    let lastY = window.scrollY || 0;
    let translateY = 0; // how many px the nav is translated up (0..navHeight)
    let ticking = false;
    let scrollEndTimer = null;

    const IGNORE_DELTA = 2; // small deltas to ignore (px)
    const SNAP_DELAY = 150; // ms after scroll stops before snapping

    function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }

    function applyTransform() {
      // apply translate as a negative Y value so translateY=navHeight hides the nav
      nav.style.transform = `translateY(-${Math.round(translateY)}px)`;
      // add a helper class when fully hidden to remove shadow or perform final styles
      if (translateY >= nav.offsetHeight - 1) nav.classList.add('hidden');
      else nav.classList.remove('hidden');
      ticking = false;
    }

    function snapToEdge() {
      // When the user stops scrolling, snap fully hidden or fully shown depending on position
      const h = nav.offsetHeight;
      if (translateY > h * 0.5) translateY = h; else translateY = 0;
      applyTransform();
    }

    function onScroll() {
      const currentY = window.scrollY || 0;
      const dy = currentY - lastY;
      // ignore tiny jitter
      if (Math.abs(dy) < IGNORE_DELTA) {
        lastY = currentY;
        return;
      }

      // update the translate position (scrolling down -> hide more)
      const h = nav.offsetHeight || 88;
      translateY = clamp(translateY + dy, 0, h);
      lastY = currentY;

      if (!ticking) {
        window.requestAnimationFrame(applyTransform);
        ticking = true;
      }

      // debounce snap: wait until scrolling settles, then snap to fully hidden/shown
      if (scrollEndTimer) clearTimeout(scrollEndTimer);
      scrollEndTimer = setTimeout(snapToEdge, SNAP_DELAY);
    }

    // reset transform on resize to avoid stuck values when layout changes
    window.addEventListener('resize', () => {
      translateY = 0;
      nav.style.transform = '';
      nav.classList.remove('hidden');
    });

    window.addEventListener('scroll', onScroll, { passive: true });
  })();

  // Cart button -> toggle in-page cart panel if present, otherwise noop
  // Cart UI: inject a slide-out cart panel and wire add-to-cart events.
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
          <div class="cart-empty" style="display:none;opacity:.9">Your cart is empty.</div>
        </div>
        <footer class="cart-footer">
          <div class="cart-total">Total: $<span class="cart-total-amount">0.00</span></div>
          <div class="cart-actions"><button class="btn btn-primary checkout">Checkout</button></div>
        </footer>
      `;
      document.body.appendChild(panel);
      // close button
      panel.querySelector('.cart-close').addEventListener('click', () => panel.classList.remove('open'));
      // checkout button navigates to checkout page
      const checkoutBtn = panel.querySelector('.checkout');
      if (checkoutBtn) {
        checkoutBtn.addEventListener('click', (e) => {
          e.preventDefault();
          // navigate to checkout page
          window.location.href = 'CheckOut.html';
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
        empty.style.display = '';
        panel.querySelector('.cart-total-amount').textContent = '0.00';
        return;
      }
      empty.style.display = 'none';
      let total = 0;
      items.forEach((it, i) => {
        const li = document.createElement('li');
        li.className = 'cart-item';
        li.innerHTML = `
          <div class="ci-left"><div class="ci-name">${escapeHtml(it.name)}</div><div class="ci-meta">$${Number(it.price).toFixed(2)}</div></div>
          <div class="ci-right"><button class="ci-remove" data-i="${i}" aria-label="Remove">Remove</button></div>
        `;
        list.appendChild(li);
        total += Number(it.price) || 0;
      });
      panel.querySelector('.cart-total-amount').textContent = total.toFixed(2);

      // wire remove buttons
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

    function openCart() {
      const panel = createPanel();
      renderCart();
      // small delay so transform transition applies
      requestAnimationFrame(() => panel.classList.add('open'));
    }

    function closeCart() {
      const panel = document.querySelector('.cart-panel');
      if (panel) panel.classList.remove('open');
    }

    function toggleCart() {
      const panel = createPanel();
      panel.classList.toggle('open');
      if (panel.classList.contains('open')) renderCart();
    }

    // escape helper
    function escapeHtml(s) { return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c]); }

    // listen for cart-updated custom event to refresh UI
    document.addEventListener('cart-updated', (e) => {
      renderCart();
      // open briefly to show update
      const panel = createPanel();
      panel.classList.add('open');
      // keep open for a while then snap closed only if it was not already open
    });

    // also listen for storage events from other tabs
    window.addEventListener('storage', (e) => { if (e.key === 'cart') renderCart(); });

    // delegated handler for any add-to-cart buttons on the site (Home product cards or Search cards)
    document.addEventListener('click', (ev) => {
      const btn = ev.target.closest && ev.target.closest('.add-to-cart');
      if (!btn) return;
      ev.preventDefault();
      // attempt to discover product info from DOM near the button
      const card = btn.closest('.product-card') || btn.closest('.product');
      let name = '', price = 0;
      if (card) {
        const h = card.querySelector('h3');
        const p = card.querySelector('p');
        if (h) name = h.textContent.trim();
        if (p) {
          const txt = p.textContent.replace(/[^0-9.]/g, '').trim();
          price = Number(txt) || 0;
        }
      }
      // fallback to button data attributes
      if (!name) name = btn.getAttribute('data-name') || btn.getAttribute('data-title') || 'Item';
      if (!price) price = Number(btn.getAttribute('data-price')) || 0;

      // add to localStorage cart
      try {
        const raw = localStorage.getItem('cart') || '[]';
        const cart = JSON.parse(raw);
        cart.push({ name, price, addedAt: Date.now() });
        localStorage.setItem('cart', JSON.stringify(cart));
        // notify other listeners
        document.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart } }));
        // feedback on button
        const old = btn.textContent;
        btn.textContent = 'Added ✓';
        btn.disabled = true;
        setTimeout(() => { btn.textContent = old; btn.disabled = false; }, 900);
        // open cart to show item
        openCart();
      } catch (err) {
        console.error('Cart write error', err);
      }
    });

    // wire header cart button to toggle our panel
    if (cartBtn) {
      cartBtn.addEventListener('click', (e) => { e.preventDefault(); toggleCart(); });
    }
  })();

  // Favorites button -> toggle in-page favorites panel if present, otherwise noop
  const favBtn = document.getElementById('favorites');
  if (favBtn) {
    favBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const panel = document.querySelector('.fav-panel');
      if (panel) panel.classList.toggle('open');
      else console.info('Favorites panel not found in DOM.');
    });
  }
});

