<?php
// admin/inventory.php
session_start();
require_once '../../PHP/config.php';
require_once '../../PHP/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit();
}

$conn = getDBConnection();

// Handle stock updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_stock') {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $operation = $_POST['operation']; // 'add' or 'remove'
        
        // Get current stock
        $stmt = $conn->prepare("SELECT stock_quantity, name FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if ($product) {
            $current_stock = $product['stock_quantity'];
            $new_stock = $operation === 'add' ? $current_stock + $quantity : $current_stock - $quantity;
            
            // Don't allow negative stock
            if ($new_stock < 0) {
                $_SESSION['error'] = 'Cannot reduce stock below 0';
            } else {
                // Update stock
                $update_stmt = $conn->prepare("UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?");
                $update_stmt->bind_param("ii", $new_stock, $product_id);
                
                if ($update_stmt->execute()) {
                    // Log the action
                    log_admin_action($_SESSION['user_id'], 'UPDATE', 'products', $product_id, [
                        'action' => 'stock_update',
                        'product' => $product['name'],
                        'operation' => $operation,
                        'quantity' => $quantity,
                        'old_stock' => $current_stock,
                        'new_stock' => $new_stock
                    ]);
                    
                    $_SESSION['success'] = 'Stock updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update stock';
                }
                $update_stmt->close();
            }
        }
        $stmt->close();
    }
    
    header("Location: inventory.php");
    exit();
}

// Get inventory data with filters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$stock_filter = $_GET['stock'] ?? '';

$where = ["p.is_active = 1"];
$params = [];
$types = '';

if ($search) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $search_term = "%$search%";
    array_push($params, $search_term, $search_term, $search_term);
    $types .= 'sss';
}

if ($category_filter) {
    $where[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if ($stock_filter === 'low') {
    $where[] = "p.stock_quantity < 10 AND p.stock_quantity > 0";
} elseif ($stock_filter === 'out') {
    $where[] = "p.stock_quantity = 0";
} elseif ($stock_filter === 'sufficient') {
    $where[] = "p.stock_quantity >= 10";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get categories for filter
$categories_result = $conn->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Get inventory summary
$summary_query = "
    SELECT 
        COUNT(*) as total_products,
        SUM(p.stock_quantity) as total_stock,
        SUM(CASE WHEN p.stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
        SUM(CASE WHEN p.stock_quantity < 10 AND p.stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN p.stock_quantity >= 10 THEN 1 ELSE 0 END) as sufficient_stock,
        SUM(p.stock_quantity * p.price) as total_stock_value
    FROM products p
    $where_clause
";

if (!empty($params)) {
    $stmt = $conn->prepare($summary_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc();
    $stmt->close();
} else {
    $result = $conn->query($summary_query);
    $summary = $result->fetch_assoc();
}

// Get products for display
$inventory_query = "
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $where_clause
    ORDER BY p.stock_quantity ASC, p.name ASC
";

if (!empty($params)) {
    $stmt = $conn->prepare($inventory_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query($inventory_query);
    $products = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Admin Panel</title>
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
        <?php if (isset($_SESSION['success'])): ?>
            <div class="admin-message admin-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="admin-message admin-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="admin-header">
            <h1>📊 Inventory Management</h1>
        </div>
        
        <!-- Inventory Stats -->
        <div class="admin-stats-grid">
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background: rgba(99, 102, 241, 0.2);">
                    📦
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($summary['total_products'] ?? 0); ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background: rgba(76, 175, 80, 0.2);">
                    📊
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($summary['total_stock'] ?? 0); ?></h3>
                    <p>Total Stock Units</p>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background: rgba(255, 152, 0, 0.2);">
                    ⚠️
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($summary['low_stock'] ?? 0); ?></h3>
                    <p>Low Stock Items</p>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background: rgba(244, 67, 54, 0.2);">
                    🔴
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($summary['out_of_stock'] ?? 0); ?></h3>
                    <p>Out of Stock</p>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background: rgba(156, 39, 176, 0.2);">
                    💰
                </div>
                <div class="stat-content">
                    <h3>$<?php echo number_format($summary['total_stock_value'] ?? 0, 2); ?></h3>
                    <p>Total Stock Value</p>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="admin-filters">
            <form method="GET" class="admin-filter-form">
                <input type="text" name="search" placeholder="Search products..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="admin-form-control">
                <select name="category" class="admin-form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"
                        <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="stock" class="admin-form-control">
                    <option value="">All Stock Levels</option>
                    <option value="low" <?php echo $stock_filter === 'low' ? 'selected' : ''; ?>>Low Stock (< 10)</option>
                    <option value="out" <?php echo $stock_filter === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                    <option value="sufficient" <?php echo $stock_filter === 'sufficient' ? 'selected' : ''; ?>>Sufficient Stock</option>
                </select>
                <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
                <?php if ($search || $category_filter || $stock_filter): ?>
                <a href="inventory.php" style="color: #6366f1; text-decoration: none; display: flex; align-items: center; padding: 0 20px;">
                    Clear Filters
                </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Inventory Table -->
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Current Stock</th>
                    <th>Stock Value</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <?php 
                        $stock_value = ($product['stock_quantity'] ?? 0) * ($product['price'] ?? 0);
                        $stock_class = '';
                        if ($product['stock_quantity'] == 0) {
                            $stock_class = 'admin-stock-danger';
                        } elseif ($product['stock_quantity'] < 10) {
                            $stock_class = 'admin-stock-warning';
                        } else {
                            $stock_class = 'admin-stock-success';
                        }
                        ?>
                        <tr data-product-id="<?php echo $product['id']; ?>">
                            <td>
                                <div class="admin-product-info">
                                    <?php if (!empty($product['image_url'])): ?>
                                    <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="admin-product-thumb"
                                         onerror="this.src='../../Images/placeholder.jpg'">
                                    <?php else: ?>
                                    <img src="../../Images/placeholder.jpg" alt="No image" class="admin-product-thumb">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                        <small style="color: rgba(248,250,252,0.6);">SKU: <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                            <td>$<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                            <td>
                                <span class="admin-stock-badge <?php echo $stock_class; ?>">
                                    <?php echo $product['stock_quantity'] ?? 0; ?> units
                                </span>
                            </td>
                            <td>$<?php echo number_format($stock_value, 2); ?></td>
                            <td>
                                <span class="admin-status-badge <?php echo $product['is_active'] ? 'admin-status-active' : 'admin-status-inactive'; ?>">
                                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="admin-action-buttons">
                                    <button type="button" class="admin-btn-action admin-btn-edit" 
                                            onclick="openStockModal(<?php echo $product['id']; ?>, 'add', '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['stock_quantity'] ?? 0; ?>)">
                                        ➕ Add
                                    </button>
                                    <button type="button" class="admin-btn-action admin-btn-delete" 
                                            onclick="openStockModal(<?php echo $product['id']; ?>, 'remove', '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['stock_quantity'] ?? 0; ?>)">
                                        ➖ Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: rgba(248,250,252,0.5);">
                            No products found matching your filters.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if (!empty($products)): ?>
        <div style="margin-top: 20px; color: rgba(248,250,252,0.6); text-align: center;">
            Showing <?php echo count($products); ?> product(s)
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Stock Update Modal -->
    <div id="stockModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 20px;">Update Stock</h3>
            <form id="stockForm" method="POST">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" id="modalProductId" name="product_id">
                <input type="hidden" id="modalOperation" name="operation">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: rgba(248,250,252,0.8);">Product</label>
                    <input type="text" id="modalProductName" class="admin-form-control" readonly>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: rgba(248,250,252,0.8);">Current Stock</label>
                    <input type="text" id="modalCurrentStock" class="admin-form-control" readonly>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: rgba(248,250,252,0.8);">Quantity *</label>
                    <input type="number" id="modalQuantity" name="quantity" min="1" required class="admin-form-control">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="admin-btn admin-btn-primary" style="flex: 1;">
                        Update Stock
                    </button>
                    <button type="button" onclick="closeStockModal()" class="admin-btn admin-btn-secondary" style="flex: 1;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    let currentStock = 0;
    
    function openStockModal(productId, operation, productName, currentStockValue) {
        currentStock = currentStockValue;
        
        document.getElementById('modalProductId').value = productId;
        document.getElementById('modalOperation').value = operation;
        document.getElementById('modalProductName').value = productName;
        document.getElementById('modalCurrentStock').value = currentStockValue;
        document.getElementById('modalQuantity').value = '';
        document.getElementById('stockModal').style.display = 'flex';
        
        // Update modal title based on operation
        const modalTitle = document.querySelector('#stockModal h3');
        modalTitle.textContent = operation === 'add' ? '➕ Add Stock' : '➖ Remove Stock';
    }
    
    function closeStockModal() {
        document.getElementById('stockModal').style.display = 'none';
        document.getElementById('stockForm').reset();
    }
    
    // Form validation
    document.getElementById('stockForm').addEventListener('submit', function(e) {
        const operation = document.getElementById('modalOperation').value;
        const quantity = parseInt(document.getElementById('modalQuantity').value);
        
        if (operation === 'remove' && quantity > currentStock) {
            e.preventDefault();
            alert(`Cannot remove ${quantity} units. Only ${currentStock} available in stock.`);
            return false;
        }
        
        if (quantity < 1) {
            e.preventDefault();
            alert('Please enter a valid quantity (minimum 1).');
            return false;
        }
        
        return true;
    });
    
    // Close modal when clicking outside
    document.getElementById('stockModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeStockModal();
        }
    });
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeStockModal();
        }
    });
    
    // Modal styles
    const style = document.createElement('style');
    style.textContent = `
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: var(--admin-card);
            padding: 30px;
            border-radius: 12px;
            width: 400px;
            border: 1px solid var(--admin-border);
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>

<?php 
// Close connection
if (isset($result)) $result->free();
if (isset($categories_result)) $categories_result->free();
$conn->close();
?>