<?php
// admin/orders.php
session_start();
require_once '../../PHP/config.php';
require_once '../../PHP/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit();
}

$conn = getDBConnection();
$order_id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'list';

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status' && isset($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
        $status = sanitize_input($_POST['status'] ?? 'pending');
        $tracking_number = sanitize_input($_POST['tracking_number'] ?? '');
        
        $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        if (in_array($status, $allowed_statuses)) {
            $stmt = $conn->prepare("UPDATE orders SET status = ?, tracking_number = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $status, $tracking_number, $order_id);
            
            if ($stmt->execute()) {
                log_admin_action($_SESSION['user_id'], 'UPDATE', 'orders', $order_id, [
                    'status' => $status,
                    'tracking_number' => $tracking_number
                ]);
                $_SESSION['success'] = 'Order status updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update order status';
            }
            $stmt->close();
        }
        header("Location: orders.php?id=" . $order_id);
        exit();
    }
}

// Get order details if viewing single order
$order = null;
$order_items = [];
if ($order_id > 0) {
    // Get order details
    $stmt = $conn->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if ($order) {
        // Get order items
        $stmt = $conn->prepare("
            SELECT oi.*, p.name as product_name, p.image_url
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order_items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Get all orders for listing
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = [];
$params = [];
$types = '';

if ($search) {
    $where[] = "(o.order_number LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $search_term = "%$search%";
    array_push($params, $search_term, $search_term, $search_term, $search_term);
    $types .= 'ssss';
}

if ($status_filter && $status_filter !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($date_from) {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Count total orders
$count_query = "
    SELECT COUNT(*) as total 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $where_clause
";

if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_orders = $result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $result = $conn->query($count_query);
    $total_orders = $result->fetch_assoc()['total'];
}

$total_pages = ceil($total_orders / $limit);

// Get orders
$query = "
    SELECT o.*, u.first_name, u.last_name, u.email,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $where_clause
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Panel</title>
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
            <div class="admin-message admin-success">
                ✅ <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="admin-message admin-error">
                ❌ <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-header">
            <h1>
                <?php if ($order_id && $order): ?>📦 Order #<?php echo htmlspecialchars($order['order_number']); ?>
                <?php else: ?>📋 Manage Orders<?php endif; ?>
            </h1>
            <div>
                <?php if ($order_id && $order): ?>
                    <a href="orders.php" class="admin-back-btn">← Back to Orders</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="products.php">Products</a>
            <a href="categories.php">Categories</a>
            <a href="orders.php" class="active">Orders</a>
            <a href="customers.php">Customers</a>
            <a href="inventory.php">Inventory</a>
            <a href="reports.php">Reports</a>
        </div>
        
        <?php if ($order_id && $order): ?>
            <!-- Order Details -->
            <div class="order-details-grid">
                <div class="order-card">
                    <h3>👤 Customer Information</h3>
                    <div class="order-info">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></p>
                        <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="order-card">
                    <h3>📍 Shipping Address</h3>
                    <div class="order-info">
                        <?php if (!empty($order['shipping_address'])): ?>
                            <p style="white-space: pre-line;"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                        <?php else: ?>
                            <p>No shipping address provided</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="order-card">
                    <h3>⚙️ Order Status</h3>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        
                        <div class="admin-form-group">
                            <label>Current Status</label>
                            <select name="status" class="admin-form-control" required>
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="refunded" <?php echo $order['status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        
                        <div class="admin-form-group">
                            <label>Tracking Number</label>
                            <input type="text" name="tracking_number" class="admin-form-control"
                                   value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>" 
                                   placeholder="Enter tracking number">
                        </div>
                        
                        <div class="admin-form-actions">
                            <button type="submit" class="admin-btn admin-btn-primary">💾 Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Order Items -->
            <h2 style="margin: 30px 0 20px 0; color: rgba(248,250,252,0.9);">🛒 Order Items</h2>
            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $items_total = 0; ?>
                    <?php foreach ($order_items as $item): ?>
                        <?php $item_total = $item['product_price'] * $item['quantity']; ?>
                        <?php $items_total += $item_total; ?>
                        <tr>
                            <td>
                                <div class="admin-product-info">
                                    <?php if ($item['image_url']): ?>
                                        <img src="../../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                             class="admin-product-thumb"
                                             onerror="this.src='../../Images/placeholder.jpg'">
                                    <?php else: ?>
                                        <img src="../../Images/placeholder.jpg" alt="No image" class="admin-product-thumb">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                                        <small>ID: <?php echo $item['product_id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>$<?php echo number_format($item['product_price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><strong>$<?php echo number_format($item_total, 2); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Order Totals -->
            <div class="order-totals">
                <h3 style="margin-top: 0; margin-bottom: 20px; color: rgba(248,250,252,0.9);">💰 Order Summary</h3>
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($order['subtotal'] ?? $items_total, 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Shipping:</span>
                    <span>$<?php echo number_format($order['shipping_amount'] ?? 0, 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Tax:</span>
                    <span>$<?php echo number_format($order['tax_amount'] ?? 0, 2); ?></span>
                </div>
                <div class="total-row total-amount">
                    <strong>Total Amount:</strong>
                    <strong>$<?php echo number_format($order['total_amount'], 2); ?></strong>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Orders List -->
            <div class="admin-filters">
                <form method="GET" class="admin-filter-form">
                    <input type="text" name="search" placeholder="Search orders..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <select name="status" class="admin-form-control">
                        <option value="">All Status</option>
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <input type="date" name="date_from" placeholder="From" 
                           value="<?php echo htmlspecialchars($date_from); ?>"
                           class="admin-form-control">
                    <input type="date" name="date_to" placeholder="To" 
                           value="<?php echo htmlspecialchars($date_to); ?>"
                           class="admin-form-control">
                    <button type="submit" class="admin-btn admin-btn-primary">🔍 Search</button>
                    <?php if ($search || $status_filter || $date_from || $date_to): ?>
                    <a href="orders.php" style="color: #6366f1; text-decoration: none; padding: 0 20px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong><br>
                                    <small style="color: rgba(248,250,252,0.6);">ID: <?php echo $order['id']; ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?><br>
                                    <small style="color: rgba(248,250,252,0.6);"><?php echo htmlspecialchars($order['email']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo $order['item_count']; ?> items</td>
                                <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="admin-status-badge admin-order-status-<?php echo htmlspecialchars($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-action-buttons">
                                        <a href="orders.php?id=<?php echo $order['id']; ?>" 
                                           class="admin-btn-action admin-btn-view">👁️ View</a>
                                        <a href="mailto:<?php echo htmlspecialchars($order['email']); ?>" 
                                           class="admin-btn-action admin-btn-view">📧 Email</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="admin-empty-state">
                                <div style="font-size: 48px; margin-bottom: 20px;">📋</div>
                                <h3>No orders found</h3>
                                <?php if ($search || $status_filter || $date_from || $date_to): ?>
                                    <p>Try adjusting your search filters</p>
                                    <a href="orders.php" style="display: inline-block; margin-top: 15px; color: #6366f1;">Clear filters</a>
                                <?php else: ?>
                                    <p>No orders have been placed yet</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (!empty($orders)): ?>
            <div style="margin-top: 20px; color: rgba(248,250,252,0.6); text-align: center;">
                Showing <?php echo count($orders); ?> of <?php echo $total_orders; ?> order(s)
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="admin-pagination">
                <?php if ($page > 1): ?>
                <a href="?page=1&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">««</a>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">«</a>
                <?php endif; ?>
                
                <?php 
                $start = max(1, $page - 2);
                $end = min($total_pages, $start + 4);
                for ($i = $start; $i <= $end; $i++): 
                ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
                   class="<?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">»</a>
                <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">»»</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
    // Status change confirmation
    const statusSelect = document.querySelector('select[name="status"]');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            const criticalChanges = ['cancelled', 'refunded'];
            const newStatus = this.value;
            const oldStatus = this.options[this.selectedIndex].getAttribute('data-old') || '';
            
            if (criticalChanges.includes(newStatus) && !criticalChanges.includes(oldStatus)) {
                if (!confirm(`Are you sure you want to change status to "${newStatus}"?\n\nThis action may be irreversible and will notify the customer.`)) {
                    this.value = oldStatus;
                }
            }
        });
    }
    
    // Set current status as data attribute
    document.addEventListener('DOMContentLoaded', function() {
        const statusSelect = document.querySelector('select[name="status"]');
        if (statusSelect) {
            const selectedOption = statusSelect.options[statusSelect.selectedIndex];
            selectedOption.setAttribute('data-old', selectedOption.value);
        }
    });
    </script>
</body>
</html>

<?php 
// Close connection
$conn->close();
?>