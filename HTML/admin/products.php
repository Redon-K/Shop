<?php
// admin/products.php
session_start();
require_once '../../PHP/config.php';
require_once '../../PHP/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit();
}

$conn = getDBConnection();

// Handle product deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        // Soft delete by marking as inactive
        $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: products.php?message=Product+deleted+successfully");
        exit();
    }
}

// Fetch products with category names
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.is_active = 1
          ORDER BY p.id DESC";
$result = $conn->query($query);

// Check for errors
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Inventory - Admin Panel</title>
    <link rel="stylesheet" href="../../CSS/Home.css">
    <!-- Include admin CSS files -->
    <link rel="stylesheet" href="../../CSS/admin/admin_common.css">
    <link rel="stylesheet" href="../../CSS/admin/admin_forms.css">
    <link rel="stylesheet" href="../../CSS/admin/admin_tables.css">
    <link rel="stylesheet" href="../../CSS/admin/admin_charts.css">
</head>
<body>
    <!-- Include navbar component -->
    <?php include 'admin_navbar.php'; ?>

    <div class="admin-container">
        <?php if (isset($_GET['message'])): ?>
            <div class="admin-message admin-success">
                <?php echo htmlspecialchars(str_replace('+', ' ', $_GET['message'])); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-header">
            <h1>📦 Product Inventory</h1>
            <a href="add_product.php" class="admin-btn admin-btn-primary">+ Add New Product</a>
        </div>
        
        <div class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="products.php" class="active">Products</a>
            <a href="categories.php">Categories</a>
            <a href="orders.php">Orders</a>
            <a href="customers.php">Customers</a>
            <a href="inventory.php">Inventory</a>
            <a href="reports.php">Reports</a>
        </div>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="50">ID</th>
                    <th width="70">Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th width="100">Price</th>
                    <th width="120">Stock</th>
                    <th width="150">Status</th>
                    <th width="150">Created</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($product = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $product['id']; ?></td>
                            <td>
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="admin-product-thumb"
                                         onerror="this.src='../../Images/placeholder.jpg'">
                                <?php else: ?>
                                    <img src="../../Images/placeholder.jpg" alt="No image" class="admin-product-thumb">
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                <small style="color: rgba(248,250,252,0.6);"><?php echo htmlspecialchars($product['sku'] ?? 'No SKU'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td>
                                <span class="admin-stock-badge <?php echo ($product['stock_quantity'] ?? 0) < 10 ? 'admin-stock-warning' : 'admin-stock-success'; ?>">
                                    <?php echo $product['stock_quantity'] ?? 0; ?> units
                                </span>
                            </td>
                            <td>
                                <span class="admin-status-badge <?php echo $product['is_active'] ? 'admin-status-active' : 'admin-status-inactive'; ?>">
                                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                            <td>
                                <div class="admin-action-buttons">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="admin-btn-action admin-btn-edit">Edit</a>
                                    <a href="products.php?delete=<?php echo $product['id']; ?>" 
                                       class="admin-btn-action admin-btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                    <a href="../Product.php?id=<?php echo $product['id']; ?>" 
                                       class="admin-btn-action admin-btn-view">View</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="admin-empty-state">
                            <div style="font-size: 48px; margin-bottom: 20px;">📦</div>
                            <h3>No products found</h3>
                            <p>Get started by adding your first product</p>
                            <a href="add_product.php" class="admin-btn admin-btn-primary" style="margin-top: 20px;">+ Add New Product</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($result && $result->num_rows > 0): ?>
        <div style="margin-top: 20px; color: rgba(248,250,252,0.6); text-align: center;">
            Showing <?php echo $result->num_rows; ?> product(s)
        </div>
        <?php endif; ?>
    </div>

    <script>
    // Add some interactivity
    document.addEventListener('DOMContentLoaded', function() {
        // Delete confirmation
        const deleteButtons = document.querySelectorAll('.admin-btn-delete');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this product?\n\nThis will mark the product as inactive.')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>

<?php 
// Close connection
if ($result) $result->free();
$conn->close();
?>