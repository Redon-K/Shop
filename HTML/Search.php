<?php
require_once '../PHP/config.php';

$conn = getDBConnection();

// Get all filter parameters
$searchQuery = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : [];
$priceFilter = isset($_GET['price']) ? $_GET['price'] : '';

// Convert category array if it's a string
if (is_string($categoryFilter)) {
    $categoryFilter = explode(',', $categoryFilter);
}

// Build query with filters
$where = ["p.is_active = 1"];
$params = [];
$types = '';

// Search term filter
if (!empty($searchQuery)) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
    $searchTerm = "%$searchQuery%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm);
    $types .= 'sss';
}

// Category filter
if (!empty($categoryFilter) && is_array($categoryFilter)) {
    $categoryPlaceholders = implode(',', array_fill(0, count($categoryFilter), '?'));
    $where[] = "c.slug IN ($categoryPlaceholders)";
    $params = array_merge($params, $categoryFilter);
    $types .= str_repeat('s', count($categoryFilter));
}

// Price filter
if (!empty($priceFilter) && strpos($priceFilter, '-') !== false) {
    [$minPrice, $maxPrice] = explode('-', $priceFilter);
    $where[] = "p.price BETWEEN ? AND ?";
    $params[] = floatval($minPrice);
    $params[] = floatval($maxPrice);
    $types .= 'dd';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get products with filters
$query = "SELECT p.*, c.slug as category_slug, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          $where_clause 
          ORDER BY p.created_at DESC";

$products = [];

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query($query);
    $products = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all active categories for filter sidebar
$categories_result = $conn->query("SELECT slug, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

$conn->close();

$page_title = 'Search — Apex Fuel';
$additional_css = ['Search.css'];
?>

<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<main class="search-page" style="max-width:1200px;margin:24px auto;padding:0 20px;">
    <div class="search-layout">
        <aside class="search-sidebar" aria-label="Catalog filters">
            <div class="search-controls">
                <input id="sidebarSearch" type="search" placeholder="Search products..." 
                       value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       aria-label="Search products" />
            </div>

            <div class="catalog" aria-label="Product catalog filters">
                <form id="filterForm" method="GET" action="Search.php">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button id="clearSearchBtn" class="btn-clear catalog-clear" type="button" aria-label="Clear search">Clear All</button>
                    
                    <fieldset>
                        <legend>Categories</legend>
                        <?php foreach ($categories as $category): ?>
                            <?php 
                            $isChecked = empty($categoryFilter) || in_array($category['slug'], (array)$categoryFilter);
                            ?>
                            <label>
                                <input type="checkbox" name="category[]" value="<?php echo $category['slug']; ?>"
                                       <?php echo $isChecked ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    
                    <fieldset class="price-filters">
                        <legend>Price Range</legend>
                        <?php 
                        $priceRanges = [
                            '' => 'Any price',
                            '0-15' => 'Under $15',
                            '15-30' => '$15 — $30',
                            '30-50' => '$30 — $50',
                            '50-99999' => '$50+'
                        ];
                        ?>
                        <?php foreach ($priceRanges as $value => $label): ?>
                            <label>
                                <input type="radio" name="price" value="<?php echo $value; ?>"
                                       <?php echo $priceFilter === $value ? 'checked' : ''; ?>>
                                <?php echo $label; ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                </form>
            </div>
        </aside>

        <section class="search-results">
            <h3 id="searchResultsTitle">
                <?php if (!empty($searchQuery)): ?>
                    Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"
                <?php else: ?>
                    All Products
                <?php endif; ?>
                <small id="resultCount" style="opacity:.85; font-weight:600;">(<?php echo count($products); ?>)</small>
            </h3>

            <div class="product-grid" id="productGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        // Fix image path
                        $img_path = $product['image_url'] ?? 'Images/placeholder.jpg';
                        if (!str_starts_with($img_path, 'http') && !str_starts_with($img_path, '../')) {
                            $img_path = '../' . ltrim($img_path, '/');
                        }
                        ?>
                        
                        <div class="product-card" style="background: rgba(255,255,255,0.02); border-radius: 8px; padding: 12px; overflow: hidden;">
                            <img src="<?php echo htmlspecialchars($img_path); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 onerror="this.src='../Images/placeholder.jpg'"
                                 style="width: 100%; height: 150px; object-fit: cover; border-radius: 6px; margin-bottom: 10px;">
                            
                            <h4 style="margin: 0 0 6px 0; color: #f8fafc; font-size: 14px; font-weight: 600;">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h4>
                            
                            <p style="margin: 0 0 10px 0; color: rgba(248,250,252,0.82); font-size: 14px;">
                                $<?php echo number_format($product['price'], 2); ?>
                            </p>
                            
                            <div style="display: flex; gap: 4px; width: 100%;">
                                <button class="add-to-cart" 
                                        data-id="<?php echo $product['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                        data-price="<?php echo $product['price']; ?>"
                                        style="flex: 1; padding: 6px 8px; background: #5757f3; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                    Add to Cart
                                </button>
                                <a href="./Product.php?id=<?php echo $product['id']; ?>" style="flex: 1; text-decoration: none;">
                                    <button type="button" style="width: 100%; padding: 6px 8px; background: rgba(87,87,243,0.1); color: #5757f3; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                        View Details
                                    </button>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: rgba(248,250,252,0.5);">
                        <p style="font-size: 18px;">No products found. Try adjusting your search.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<script src="../JS/Home.js"></script>
<script>
    // Simple JavaScript for handling form submission and cart functionality
document.addEventListener('DOMContentLoaded', () => {
    console.log('Search page loaded');
    
    // DOM elements
    const sidebarSearch = document.getElementById('sidebarSearch');
    const clearBtn = document.getElementById('clearSearchBtn');
    const filterForm = document.getElementById('filterForm');
    const categoryChecks = document.querySelectorAll('input[name="category[]"]');
    const priceRadios = document.querySelectorAll('input[name="price"]');
    const mainSearch = document.getElementById('SearchBar');
    
    // Handle search input
    if (sidebarSearch) {
        let debounceTimer;
        sidebarSearch.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                filterForm.querySelector('input[name="q"]').value = e.target.value;
                filterForm.submit();
            }, 500);
        });
    }
    
    // Handle category changes
    categoryChecks.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            filterForm.submit();
        });
    });
    
    // Handle price changes
    priceRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            filterForm.submit();
        });
    });
    
    // Clear all filters
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            // Redirect to search page without any filters
            window.location.href = 'Search.php';
        });
    }
    
    // Sync sidebar search with main navbar search
    if (sidebarSearch && mainSearch) {
        sidebarSearch.addEventListener('input', () => {
            mainSearch.value = sidebarSearch.value;
        });
        
        mainSearch.addEventListener('input', () => {
            sidebarSearch.value = mainSearch.value;
        });
    }
    
    // Add to cart functionality (delegated)
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.add-to-cart')) return;
        
        const btn = e.target.closest('.add-to-cart');
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const price = parseFloat(btn.dataset.price);
        
        try {
            // Get current cart
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            
            // Check if item already exists
            const existingItem = cart.find(item => item.id == id);
            if (existingItem) {
                existingItem.quantity = (existingItem.quantity || 1) + 1;
            } else {
                cart.push({ 
                    id: id,
                    name: name, 
                    price: price,
                    quantity: 1
                });
            }
            
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Button feedback
            btn.textContent = 'Added ✓';
            btn.disabled = true;
            btn.style.background = '#4CAF50';
            
            setTimeout(() => {
                btn.textContent = 'Add to Cart';
                btn.disabled = false;
                btn.style.background = '#5757f3';
            }, 1500);
            
            // Update cart UI
            document.dispatchEvent(new Event('cart-updated'));
            
            // Show notification
            showToast('Added to cart!');
            
        } catch (error) {
            console.error('Cart error:', error);
            showToast('Error adding to cart', true);
        }
    });
    
    // Toast notification function
    function showToast(message, isError = false) {
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
    }
});

</script>
</body>
</html>
