<?php
require_once '../PHP/config.php';

$product = null;
$error = null;

if (isset($_GET['id'])) {
    $productId = (int) $_GET['id'];
    $conn = getDBConnection();
    
    // Fetch product details from database
    $query = "SELECT p.*, c.name as category_name FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE p.id = ? AND p.is_active = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        $error = "Product not found";
    }
    
    $stmt->close();
    $conn->close();
} else {
    $error = "No product ID provided";
}

if ($product):
    $page_title = htmlspecialchars($product['name']) . ' — Apex Fuel';
    $additional_css = ['Product.css'];
    
    // Fix image path
    $image_path = $product['image_url'] ?? 'Images/placeholder.jpg';
    if (!str_starts_with($image_path, 'http') && !str_starts_with($image_path, '../')) {
        $image_path = '../' . ltrim($image_path, '/');
    }
?>

<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<main class="product-page">
    <div class="product-container">
        <section class="product-gallery">
            <div class="gallery-viewport">
                <img id="main-product-img" src="<?php echo htmlspecialchars($image_path); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     onerror="this.src='../Images/placeholder.jpg'">
            </div>
        </section>

        <aside class="product-info">
            <h2 class="prod-brand">Apex Fuel</h2>
            <h1 class="prod-title"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="price-row" style="margin-bottom: 20px;">
                <div class="price" style="font-size: 32px; color: #e67e22; font-weight: bold;">$<?php echo number_format($product['price'], 2); ?></div>
            </div>

            <div class="size-select">
                <div class="size-label">Select Size</div>
                <div class="sizes">
                    <button class="size" data-size="1">1 kg</button>
                    <button class="size" data-size="2">2 kg</button>
                    <button class="size" data-size="5">5 kg</button>
                </div>
            </div>

            <ul class="quick-links">
                <li><?php echo (!empty($product['stock_quantity']) && $product['stock_quantity'] > 0) ? '✓ In stock' : '✗ Out of stock'; ?></li>
                <li><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></li>
                <li>Free Shipping & Returns</li>
            </ul>
            <div class="price-row">
                <button class="btn add" data-id="<?php echo htmlspecialchars($product['id']); ?>">ADD TO CART</button>
                <button class="btn buy" data-id="<?php echo htmlspecialchars($product['id']); ?>">BUY NOW</button>
                <button class="btn wishlist-btn" id="wishlist-btn" data-id="<?php echo htmlspecialchars($product['id']); ?>" title="Add to wishlist">♡</button>
            </div>
        </aside>
    </div>

    <div class="product-tabs">
        <nav class="tabs">
            <button class="tab active" data-tab="description">Description</button>
            <button class="tab" data-tab="specifications">Specifications</button>
            <button class="tab" data-tab="reviews">Reviews</button>
        </nav>
        <section class="tab-panel">
            <div class="tab-content" id="description">
                <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available')); ?></p>
                <?php if (!empty($product['short_description'])): ?>
                    <h3>Quick Details</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['short_description'])); ?></p>
                <?php endif; ?>
            </div>
            <div class="tab-content" id="specifications" style="display: none;">
                <h3>Product Specifications</h3>
                <ul>
                    <li><strong>Product ID:</strong> <?php echo htmlspecialchars($product['id']); ?></li>
                    <li><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></li>
                    <li><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></li>
                    <li><strong>Stock Available:</strong> <?php echo htmlspecialchars($product['stock_quantity'] ?? 0); ?> units</li>
                    <?php if (!empty($product['sku'])): ?>
                        <li><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($product['weight'])): ?>
                        <li><strong>Weight:</strong> <?php echo htmlspecialchars($product['weight']); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="tab-content" id="reviews" style="display: none;">
                <p>No reviews yet. Be the first to review this product!</p>
            </div>
        </section>
    </div>
</main>

<script src="../JS/Home.js"></script>
<script src="../JS/Product.js"></script>
<script>
    // Tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                // Hide all tab content
                document.querySelectorAll('.tab-content').forEach(content => content.style.display = 'none');
                
                // Add active class to clicked tab
                this.classList.add('active');
                // Show corresponding content
                const tabName = this.getAttribute('data-tab');
                document.getElementById(tabName).style.display = 'block';
            });
        });
        
        // Ensure first tab is active on page load
        const firstTab = document.querySelector('.tab.active');
        if (firstTab) {
            const tabName = firstTab.getAttribute('data-tab');
            document.getElementById(tabName).style.display = 'block';
        }
    });
</script>
</body>
</html>
<?php else: 
    $page_title = 'Product Not Found — Apex Fuel';
?>
<?php include 'components/head.php'; ?>
<header>
    <div class="nav">
        <a href="./Home.php"><img id="logo" src="../Images/Logo.png" alt="Apex Fuel logo"></a>
        <div class="buttons">
            <a class="nav-link" href="./Home.php">Back to Home</a>
        </div>
    </div>
</header>
<main style="max-width: 1200px; margin: 100px auto; text-align: center; padding: 20px;">
    <h1>Product Not Found</h1>
    <p><?php echo htmlspecialchars($error ?? 'The product you are looking for does not exist.'); ?></p>
    <a href="./Home.php" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #e67e22; color: white; text-decoration: none; border-radius: 4px;">Back to Home</a>
</main>
</body>
</html>
<?php endif; ?>
