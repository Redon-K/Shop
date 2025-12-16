

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

  // Cart button -> toggle in-page cart panel if present, otherwise noop
  const cartBtn = document.getElementById('cart');
  if (cartBtn) {
    cartBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const panel = document.querySelector('.cart-panel');
      if (panel) panel.classList.toggle('open');
      else console.info('Cart panel not found in DOM.');
    });
  }

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

