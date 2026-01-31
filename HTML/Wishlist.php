<?php 
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

require_once '../PHP/config.php';
$conn = getDBConnection();

// Get user data
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$is_admin = isset($user['is_admin']) && $user['is_admin'] ? true : false;

// Get user's wishlist
$wishlist = [];
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.price, p.image_url, p.short_description, c.name as category
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ? AND p.is_active = 1
    ORDER BY w.added_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$wishlist = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

$page_title = 'My Wishlist — Apex Fuel';
$additional_css = ['Wishlist.css'];
?>

<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<div class="wishlist-container">
    <div class="wishlist-header">
        <h1>My Wishlist</h1>
        <p class="wishlist-count"><?php echo count($wishlist); ?> item<?php echo count($wishlist) !== 1 ? 's' : ''; ?></p>
    </div>

    <?php if(empty($wishlist)): ?>
    <div class="empty-state">
        <div class="empty-icon">♡</div>
        <h2>No Items in Wishlist</h2>
        <p>Start adding products to your wishlist!</p>
        <a href="./Home.php" class="btn btn-primary">Browse Products</a>
    </div>
    <?php else: ?>
    <div class="wishlist-grid">
        <?php foreach($wishlist as $product): ?>
        <?php
            // Fix image path
            $img_path = $product['image_url'] ?? 'Images/placeholder.png';
            if (!str_starts_with($img_path, 'http') && !str_starts_with($img_path, '../')) {
                $img_path = '../' . ltrim($img_path, '/');
            }
        ?>
        <div class="wishlist-card" data-product-id="<?php echo htmlspecialchars($product['id']); ?>">
            <div class="product-image">
                <img src="<?php echo htmlspecialchars($img_path); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     loading="lazy"
                     onerror="this.src='../Images/placeholder.png'" />
                <button class="remove-btn" title="Remove from wishlist" data-product-id="<?php echo htmlspecialchars($product['id']); ?>">✕</button>
            </div>
            <div class="product-info">
                <span class="category"><?php echo htmlspecialchars($product['category']); ?></span>
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="description"><?php echo htmlspecialchars(substr($product['short_description'] ?? '', 0, 60)); ?></p>
                <div class="product-footer">
                    <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                    <a href="./Product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-primary">View</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script src="../JS/Home.js"></script>
<script src="../JS/Wishlist.js"></script>
</body>
</html>
