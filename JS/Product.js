function(){
  function qs(sel, el) { return (el||document).querySelector(sel); }
  function qsa(sel, el) { return Array.from((el||document).querySelectorAll(sel)); }

  // Tabs
  const tabs = qsa('.tab');
  const tabContent = qs('.tab-content');
  const tabTexts = {
    'Description': '<p>This premium whey protein is crafted for fast recovery and clean gains. Delicious chocolate flavor with 24g protein per serving.</p>',
    'Size & Fit': '<p>Available in 1kg, 2kg and 5kg. Scoop size ~35g. Mixes well with water or milk.</p>',
    'Reviews': '<p>No reviews yet — be the first to review this product.</p>'
  };
  tabs.forEach(btn => btn.addEventListener('click', ()=>{
    tabs.forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    const key = btn.textContent.trim();
    tabContent.innerHTML = tabTexts[key] || '';
  }));

  // Size selection
  let selectedSize = null;
  const sizes = qsa('.size');
  sizes.forEach(s=> s.addEventListener('click', ()=>{
    sizes.forEach(x=>x.classList.remove('selected'));
    s.classList.add('selected');
    selectedSize = s.textContent.trim();
  }));

  // Add to cart
  const addBtn = qs('.btn.add');
  addBtn && addBtn.addEventListener('click', ()=>{
    const title = qs('.prod-title')?.textContent?.trim() || 'Product';
    const priceText = qs('.price')?.textContent?.replace(/[^0-9.]/g,'') || '0';
    const price = parseFloat(priceText) || 0;
    const size = selectedSize || (sizes[0] && sizes[0].textContent.trim()) || '';

    const cart = JSON.parse(localStorage.getItem('cart')||'[]');
    cart.push({id: Date.now(), title, price, size, qty: 1});
    localStorage.setItem('cart', JSON.stringify(cart));

    showToast('Added to cart');
    // If Home.js exposes togglePanel, try opening cart panel
    try{ if(window.togglePanel) window.togglePanel('cart', true); }catch(e){}
  });



  // Small toast feedback
  function showToast(msg){
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.position = 'fixed';
    t.style.right = '20px';
    t.style.bottom = '24px';
    t.style.padding = '10px 14px';
    t.style.background = 'rgba(2,6,23,0.92)';
    t.style.color = '#fff';
    t.style.borderRadius = '8px';
    t.style.boxShadow = '0 8px 30px rgba(2,6,23,0.4)';
    t.style.zIndex = 2200;
    document.body.appendChild(t);
    setTimeout(()=>{ t.style.transition='transform .28s, opacity .28s'; t.style.transform='translateY(8px)'; },20);
    setTimeout(()=>{ t.style.opacity='0'; t.style.transform='translateY(20px)'; },1700);
    setTimeout(()=> t.remove(),2100);
  }

}
