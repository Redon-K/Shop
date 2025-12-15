const products = [
  { name: "Whey Protein Gold", price: 49.99 },
  { name: "Creatine Monohydrate", price: 24.99 },
  { name: "Mass Gainer Extreme", price: 59.99 },
  { name: "Vegan Protein Blend", price: 39.99 },
  { name: "BCAA Energy Powder", price: 19.99 }
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

  items.forEach(product => {
    const div = document.createElement("div");
    div.className = "product";
    div.innerHTML = `
      <strong>${product.name}</strong>
      <div>$${product.price}</div>
    `;
    productList.appendChild(div);
  });
}

// Initial render
renderProducts(products);

// Search logic
searchInput.addEventListener("input", () => {
  const query = searchInput.value.toLowerCase();

  const filteredProducts = products.filter(product =>
    product.name.toLowerCase().includes(query)
  );

  renderProducts(filteredProducts);
});
