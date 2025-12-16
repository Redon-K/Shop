document.addEventListener('DOMContentLoaded', () => {
  const products = [
    {
      name: "Whey Protein",
      price: 29.99,
      image: "../Images/Prote+na+whey+OPTIMUN+NUTRITION+Gold+Standard+chocolate+908+g-1159974388.jpg"
    }
  ];

  const productList = document.getElementById("productList");
  const searchInput = document.getElementById("searchInput");

  // Render products
  function renderProducts(items) {
    productList.innerHTML = "";

    if (items.length === 0) {
      productList.innerHTML = "<p>No products found.</p>";
      return;
    }

    items.forEach((product, idx) => {
      const div = document.createElement("div");
      div.className = "product-card"; // use same class as home cards for consistent styling
      div.dataset.id = idx;
      const imgSrc = product.image || "../Images/Prote+na+whey+OPTIMUN+NUTRITION+Gold+Standard+chocolate+908+g-1159974388.jpg";
      div.innerHTML = `
        <img src="${imgSrc}" alt="${product.name}" />
        <h3>${product.name}</h3>
        <p>$${product.price.toFixed(2)}</p>
        <button class="add-to-cart" data-id="${idx}">Add to Cart</button>
      `;
      productList.appendChild(div);
    });
  }

  // Filter helper
  function applyFilter(query) {
    const q = (query || "").toLowerCase();
    const filtered = products.filter(p => p.name.toLowerCase().includes(q));
    renderProducts(filtered);
  }

  // wire input
  searchInput.addEventListener('input', () => applyFilter(searchInput.value));

  // delegated click handler for Add to Cart buttons
  productList.addEventListener('click', (e) => {
    const btn = e.target.closest('.add-to-cart');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    const item = products[Number(id)];
    if (!item) return;

    // simple cart in localStorage
    try {
      const raw = localStorage.getItem('cart') || '[]';
      const cart = JSON.parse(raw);
      cart.push({ name: item.name, price: item.price, addedAt: Date.now() });
      localStorage.setItem('cart', JSON.stringify(cart));
      // temporary feedback on button
      const old = btn.textContent;
      btn.textContent = 'Added ✓';
      btn.disabled = true;
      setTimeout(() => {
        btn.textContent = old;
        btn.disabled = false;
      }, 1200);
    } catch (err) {
      console.error('Cart error', err);
    }
  });

  // If page loaded with ?q=, apply that query
  const params = new URLSearchParams(window.location.search);
  const q = params.get('q') || '';
  if (q) {
    searchInput.value = q;
    applyFilter(q);
  } else {
    // initial render all
    renderProducts(products);
  }
});
