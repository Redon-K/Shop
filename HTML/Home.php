<?php
require_once '../PHP/config.php';

// Get all categories
$conn = getDBConnection();
$categories = [];
$result = $conn->query("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY name");
if ($result) {
    $categories = $result->fetch_all(MYSQLI_ASSOC);
}

// Map category slugs to display names and IDs
$categoryMap = [];
foreach ($categories as $cat) {
    $categoryMap[$cat['slug']] = ['name' => $cat['name'], 'id' => $cat['id']];
}

// Fetch products by category
$productsByCategory = [];
foreach ($categories as $cat) {
    $stmt = $conn->prepare("SELECT id, name, price, image_url FROM products WHERE category_id = ? AND is_active = 1 LIMIT 6");
    $stmt->bind_param("i", $cat['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $productsByCategory[$cat['slug']] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();

$page_title = 'Apex Fuel — Home';
?>

<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<div class="Main">
    <div class="Intro">
        <h1>APEX FUEL</h1>
        <h2>ACHIEVE YOUR GOALS: <br> <b>BULK & DEFINE</b></h2>
        <button id="shop-now" type="button">SHOP NOW</button>
    </div>

    <div class="Products">
        <!-- Proteins part -->
        <h3 id="Proteins">Proteins</h3>
        <div id="Proteins" class="Proteins">
            <?php foreach ($productsByCategory['proteins'] ?? [] as $product): ?>
            <div class="product-card" data-id="<?php echo htmlspecialchars($product['id']); ?>">
                <img src="<?php echo '../' . htmlspecialchars($product['image_url'] ?? 'Images/placeholder.jpg'); ?>" 
     alt="<?php echo htmlspecialchars($product['name']); ?>">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p>$<?php echo number_format($product['price'], 2); ?></p>
                <div style="display: flex; gap: 5px; width: 100%;">
                    <button class="add-to-cart" data-id="<?php echo htmlspecialchars($product['id']); ?>" type="button" style="flex: 1;">Add to Cart</button>
                    <a href="./Product.php?id=<?php echo htmlspecialchars($product['id']); ?>" style="flex: 1; text-decoration: none;margin: 5px;"><button type="button" style="width: 100%; padding: 8px; background: #5757f3; color: white; border: none; border-radius: 4px; cursor: pointer;">View Details</button></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pre Workout part -->
        <h3 id="Pre">Pre Workout</h3>
        <div id="Pre" class="Pre">
            <?php foreach ($productsByCategory['pre-workout'] ?? [] as $product): ?>
            <div class="product-card" data-id="<?php echo htmlspecialchars($product['id']); ?>">
                <img src="<?php echo '../' . htmlspecialchars($product['image_url'] ?? 'Images/placeholder.jpg'); ?>" 
                alt="<?php echo htmlspecialchars($product['name']); ?>">                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p>$<?php echo number_format($product['price'], 2); ?></p>
                <div style="display: flex; gap: 5px; width: 100%;">
                    <button class="add-to-cart" data-id="<?php echo htmlspecialchars($product['id']); ?>" type="button" style="flex: 1;">Add to Cart</button>
                  <a href="./Product.php?id=<?php echo htmlspecialchars($product['id']); ?>" style="flex: 1; text-decoration: none;"><button type="button" style="width: 100%; padding: 8px; background: #5757f3; color: white; border: none; border-radius: 4px; cursor: pointer;">View Details</button></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Vitamins part -->
        <h3 id="Vitamins">Vitamins</h3>
        <div id="Vitamins" class="Vitamins">
            <?php foreach ($productsByCategory['vitamins'] ?? [] as $product): ?>
            <div class="product-card" data-id="<?php echo htmlspecialchars($product['id']); ?>">
                <img src="<?php echo '../' . htmlspecialchars($product['image_url'] ?? 'Images/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p>$<?php echo number_format($product['price'], 2); ?></p>
                <div style="display: flex; gap: 5px; width: 100%;">
                    <button class="add-to-cart" data-id="<?php echo htmlspecialchars($product['id']); ?>" type="button" style="flex: 1;">Add to Cart</button>
                    <a href="./Product.php?id=<?php echo htmlspecialchars($product['id']); ?>" style="flex: 1; text-decoration: none;"><button type="button" style="width: 100%; padding: 8px; background: #5757f3; color: white; border: none; border-radius: 4px; cursor: pointer;">View Details</button></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Supplements part -->
        <h3 id="Supplements">Supplements</h3>
        <div id="Supplements" class="Supplements">
            <?php foreach ($productsByCategory['supplements'] ?? [] as $product): ?>
            <div class="product-card" data-id="<?php echo htmlspecialchars($product['id']); ?>">
                <img src="<?php echo '../' . htmlspecialchars($product['image_url'] ?? 'Images/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p>$<?php echo number_format($product['price'], 2); ?></p>
                <div style="display: flex; gap: 5px; width: 100%;">
                    <button class="add-to-cart" data-id="<?php echo htmlspecialchars($product['id']); ?>" type="button" style="flex: 1;">Add to Cart</button>
                    <a href="./Product.php?id=<?php echo htmlspecialchars($product['id']); ?>" style="flex: 1; text-decoration: none;"><button type="button" style="width: 100%; padding: 8px; background: #5757f3; color: white; border: none; border-radius: 4px; cursor: pointer;">View Details</button></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="../JS/Home.js"></script>
</body>
</html>