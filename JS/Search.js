document.addEventListener('DOMContentLoaded', () => {
  const products = [
    { name: "Whey Protein", price: 29.99, image: "../Images/Prote+na+whey+OPTIMUN+NUTRITION+Gold+Standard+chocolate+908+g-1159974388.jpg", category: 'Proteins' },
    { name: "Casein Night Protein", price: 34.99, image: "../Images/casein.jpg", category: 'Proteins' },
    { name: "Plant Protein Blend", price: 27.50, image: "../Images/plant-protein.jpg", category: 'Plant' },
    { name: "Explosive Pre-Workout", price: 39.99, image: "../Images/preworkout1.jpg", category: 'Pre' },
    { name: "Endurance Pre-Workout", price: 31.00, image: "../Images/preworkout2.jpg", category: 'Pre' },
    { name: "Daily Multivitamin", price: 19.99, image: "../Images/multivitamin.jpg", category: 'Vitamins' },
    { name: "Vitamin D3 2000IU", price: 9.99, image: "../Images/vitamin-d.jpg", category: 'Vitamins' },
    { name: "Creatine Monohydrate", price: 15.99, image: "../Images/creatine.jpg", category: 'Creatine' },
    { name: "BCAA Recovery", price: 22.99, image: "../Images/bcaa.jpg", category: 'BCAA' },
    { name: "Omega-3 Fish Oil", price: 17.50, image: "../Images/omega3.jpg", category: 'Omega' },
    { name: "Creatine + BCAA Stack", price: 45.00, image: "../Images/stack.jpg", category: 'Supplements' }
  ];

  // per-category containers (added to Search.html)
  const proteinsList = document.getElementById('ProteinsList');
  const preList = document.getElementById('PreList');
  const vitaminsList = document.getElementById('VitaminsList');
  const supplementsList = document.getElementById('SupplementsList');
  // searchInput removed from page; use URL param 'q' provided by header search
  const catalogChecks = document.querySelectorAll('.catalog input[name="cat"]');
  const priceRadios = document.querySelectorAll('.catalog input[name="price"]');

  // Render products
  function mapToMainCategory(cat) {
    if (!cat) return 'Supplements';
    const c = cat.toLowerCase();
    if (c === 'proteins' || c === 'plant' || c.includes('protein')) return 'Proteins';
    if (c === 'pre') return 'Pre';
    if (c === 'vitamins' || c === 'vitamin') return 'Vitamins';
    // treat these as supplements bucket
    if (['creatine','bcaa','omega','supplements'].some(x => x === c)) return 'Supplements';
    return 'Supplements';
  }

  function clearCategoryContainers() {
    [proteinsList, preList, vitaminsList, supplementsList].forEach(el => { if (el) el.innerHTML = ''; });
  }

  function renderProducts(items) {
    clearCategoryContainers();

    if (!items || items.length === 0) {
      // nothing found: show helpful message in proteinsList
      if (proteinsList) proteinsList.innerHTML = '<p>No products found.</p>';
      const cntElEmpty = document.getElementById('resultCount'); if (cntElEmpty) cntElEmpty.textContent = '(0)';
      return;
    }

    // render into category buckets
    let total = 0;
    items.forEach((product) => {
      const div = document.createElement('div');
      div.className = 'product-card';
      // find master index for this product in products[] (match by name+price)
      const masterIdx = products.findIndex(p => p.name === product.name && Number(p.price) === Number(product.price));
      if (masterIdx >= 0) div.setAttribute('data-id', masterIdx);
      const imgSrc = product.image || '../Images/Prote+na+whey+OPTIMUN+NUTRITION+Gold+Standard+chocolate+908+g-1159974388.jpg';
      div.innerHTML = `
        <img src="${imgSrc}" alt="${product.name}" />
        <h3>${product.name}</h3>
        <p>$${product.price.toFixed(2)}</p>
        <button class="add-to-cart" data-id="${masterIdx}">Add to Cart</button>
      `;

      const mainCat = mapToMainCategory(product.category);
      if (mainCat === 'Proteins' && proteinsList) proteinsList.appendChild(div);
      else if (mainCat === 'Pre' && preList) preList.appendChild(div);
      else if (mainCat === 'Vitamins' && vitaminsList) vitaminsList.appendChild(div);
      else if (supplementsList) supplementsList.appendChild(div);

      total += 1;
    });

    const cntEl = document.getElementById('resultCount');
    if (cntEl) cntEl.textContent = `(${total})`;
  }

  // Filter helper
  function getSelectedCategories() {
    const selected = [];
    catalogChecks.forEach(cb => { if (cb.checked) selected.push(cb.value); });
    return selected;
  }

  function getSelectedPriceRange() {
    const picked = Array.from(priceRadios).find(r => r.checked);
    return picked ? picked.value : '';
  }

  function applyFilter(query) {
    const q = (query || "").toLowerCase();
  const selected = getSelectedCategories();
  const priceRange = getSelectedPriceRange();
    // if no category selected, treat as all selected
    const filtered = products.filter(p => {
      const matchesQuery = p.name.toLowerCase().includes(q);
      const matchesCategory = selected.length === 0 ? true : selected.includes(p.category);
      // price matching
      let matchesPrice = true;
      if (priceRange && priceRange.indexOf('-') > -1) {
        const [lowStr, highStr] = priceRange.split('-');
        const low = Number(lowStr);
        const high = Number(highStr);
        matchesPrice = p.price >= low && p.price <= high;
      }
      return matchesQuery && matchesCategory && matchesPrice;
    });
    renderProducts(filtered);
  }

  function getQuery() {
    const paramsLocal = new URLSearchParams(window.location.search);
    return paramsLocal.get('q') || '';
  }

  // wire catalog and price inputs to re-run filter using current URL query
  catalogChecks.forEach(cb => cb.addEventListener('change', () => applyFilter(getQuery())));
  priceRadios.forEach(rb => rb.addEventListener('change', () => applyFilter(getQuery())));

  // sidebar search input that updates the URL q param and re-runs filters
  const sidebarSearch = document.getElementById('sidebarSearch');
  if (sidebarSearch) {
    // initialize value from URL
    sidebarSearch.value = getQuery();
    // debounce helper
    let debounceTimer = null;
    sidebarSearch.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      const val = e.target.value || '';
      debounceTimer = setTimeout(() => {
        // update URL q param without navigating
        const url = new URL(window.location.href);
        if (val) url.searchParams.set('q', val);
        else url.searchParams.delete('q');
        const newUrl = url.pathname + (url.search ? url.search : '');
        window.history.replaceState(null, '', newUrl);
        // also update header search input if present
        const headerSearch = document.getElementById('SearchBar');
        if (headerSearch) headerSearch.value = val;
        // apply filter
        applyFilter(val);
      }, 300);
    });
  }

  // Clear search button (in sidebar) — remove 'q' from URL and re-run filters
  const clearBtn = document.getElementById('clearSearchBtn');
  if (clearBtn) {
    clearBtn.addEventListener('click', (e) => {
      e.preventDefault();
      // remove q param from URL without reloading
      const url = new URL(window.location.href);
      url.searchParams.delete('q');
      const newUrl = url.pathname + (url.search ? url.search : '');
      window.history.replaceState(null, '', newUrl);
      // clear header search input if present
      const headerSearch = document.getElementById('SearchBar');
      if (headerSearch) headerSearch.value = '';
      // clear sidebar search input if present
      if (sidebarSearch) sidebarSearch.value = '';
      // reset all category checkboxes (clear all selected filters)
      catalogChecks.forEach(cb => { cb.checked = false; });
      // reset price radios to 'any'
      const anyRadio = document.querySelector('.catalog input[name="price"][value=""]');
      if (anyRadio) anyRadio.checked = true;
      // re-run filter with empty query (show all)
      applyFilter('');
    });
  }

  // delegated click handler for Add to Cart buttons (document-level now that we render into multiple containers)
  document.addEventListener('click', (e) => {
    const btn = e.target.closest && e.target.closest('.add-to-cart');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    const item = products[Number(id)];
    if (!item) return;

    try {
      const raw = localStorage.getItem('cart') || '[]';
      const cart = JSON.parse(raw);
      cart.push({ name: item.name, price: item.price, addedAt: Date.now() });
      localStorage.setItem('cart', JSON.stringify(cart));
      try { document.dispatchEvent(new CustomEvent('cart-updated', { detail: { cart } })); } catch(e) { }
      const old = btn.textContent;
      btn.textContent = 'Added ✓';
      btn.disabled = true;
      setTimeout(() => { btn.textContent = old; btn.disabled = false; }, 1200);
    } catch (err) { console.error('Cart error', err); }
  });

  // If page loaded with ?q=, apply that query
  const params = new URLSearchParams(window.location.search);
  const q = params.get('q') || '';
  if (q) {
    applyFilter(q);
  } else {
    // initial render all
    renderProducts(products);
  }
});
