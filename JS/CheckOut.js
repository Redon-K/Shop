document.addEventListener('DOMContentLoaded', () => {
  const orderItemsEl = document.getElementById('orderItems');
  const subtotalEl = document.getElementById('summarySubtotal');
  const shippingEl = document.getElementById('summaryShipping');
  const taxEl = document.getElementById('summaryTax');
  const totalEl = document.getElementById('summaryTotal');
  const statusEl = document.getElementById('checkoutStatus');
  const confirmation = document.getElementById('orderConfirmation');
  const ocClose = document.getElementById('oc-close');

  function readCart(){
    try { return JSON.parse(localStorage.getItem('cart') || '[]'); }
    catch(e){ return []; }
  }

  function calcTotals(items){
    const subtotal = items.reduce((s,it)=> s + (Number(it.price)||0), 0);
    const shipping = subtotal > 0 ? 4.99 : 0;
    const tax = +(subtotal * 0.07).toFixed(2);
    const total = +(subtotal + shipping + tax).toFixed(2);
    return { subtotal, shipping, tax, total };
  }

  function render() {
    const items = readCart();
    orderItemsEl.innerHTML = '';
    if (!items || items.length === 0) {
      orderItemsEl.innerHTML = '<li class="muted">Your cart is empty.</li>';
    } else {
      items.forEach((it, i) => {
        const li = document.createElement('li');
        li.innerHTML = `<div class="oi-left"><div class="oi-name">${escapeHtml(it.name)}</div><div class="muted">Added ${new Date(it.addedAt||Date.now()).toLocaleString()}</div></div><div class="oi-right">$${Number(it.price).toFixed(2)}</div>`;
        orderItemsEl.appendChild(li);
      });
    }
    const t = calcTotals(items);
    subtotalEl.textContent = t.subtotal.toFixed(2);
    shippingEl.textContent = t.shipping.toFixed(2);
    taxEl.textContent = t.tax.toFixed(2);
    totalEl.textContent = t.total.toFixed(2);
  }

  function escapeHtml(s){ return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c]); }

  // try to prefill contact from apexProfile if present
  try {
    const prof = JSON.parse(localStorage.getItem('apexProfile') || '{}');
    if (prof) {
      if (prof.firstName && prof.lastName) document.getElementById('co-name').value = (prof.firstName + ' ' + (prof.lastName||'')).trim();
      if (prof.email) document.getElementById('co-email').value = prof.email;
      if (prof.phone) document.getElementById('co-phone').value = prof.phone;
      if (prof.address) document.getElementById('co-address').value = prof.address;
    }
  } catch(e){ /* ignore */ }

  render();

  // re-render when cart updates
  document.addEventListener('cart-updated', () => render());
  window.addEventListener('storage', (e)=> { if (e.key==='cart') render(); });

  // form actions
  document.getElementById('placeOrder').addEventListener('click', () => {
    statusEl.textContent = '';
    const name = document.getElementById('co-name').value.trim();
    const email = document.getElementById('co-email').value.trim();
    const address = document.getElementById('co-address').value.trim();
    if (!name || !email || !address) {
      statusEl.textContent = 'Please fill name, email and shipping address.';
      return;
    }
    const items = readCart();
    if (!items || items.length === 0) {
      statusEl.textContent = 'Your cart is empty.';
      return;
    }
    const totals = calcTotals(items);
    // simulate order placement
    const orderId = 'APX' + Date.now().toString().slice(-6);
    // clear cart
    localStorage.removeItem('cart');
    document.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart: [] } }));
    // show confirmation
    document.getElementById('oc-id').textContent = orderId;
    document.getElementById('oc-message').innerHTML = `Your order <strong>${orderId}</strong> totaling <strong>$${totals.total.toFixed(2)}</strong> has been placed.`;
    confirmation.setAttribute('aria-hidden', 'false');
    // also reset form lightly
    // keep contact for convenience
  });

  document.getElementById('cancelOrder').addEventListener('click', () => { window.location.href = 'Home.html'; });

  ocClose.addEventListener('click', () => { confirmation.setAttribute('aria-hidden','true'); window.location.href = 'Home.html'; });
});
