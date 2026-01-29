<?php
// admin/customers.php
session_start();
require_once '../../PHP/config.php';
require_once '../../PHP/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit();
}

$conn = getDBConnection();
$customer_id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'list';

// Handle customer actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $newsletter_subscribed = isset($_POST['newsletter_subscribed']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Check if email already exists for another user
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $id);
        $check_email->execute();
        $email_result = $check_email->get_result();
        
        if ($email_result->num_rows > 0) {
            $_SESSION['error'] = 'Email already exists for another customer';
            $check_email->close();
            header("Location: customers.php?id=" . $id);
            exit();
        }
        $check_email->close();
        
        $stmt = $conn->prepare("
            UPDATE users SET 
                first_name = ?, 
                last_name = ?, 
                email = ?, 
                phone = ?, 
                newsletter_subscribed = ?, 
                is_active = ?,
                updated_at = NOW()
            WHERE id = ? AND is_admin = 0
        ");
        
        if ($stmt) {
            $stmt->bind_param("ssssiii", $first_name, $last_name, $email, $phone, $newsletter_subscribed, $is_active, $id);
            
            if ($stmt->execute()) {
                log_admin_action($_SESSION['user_id'], 'UPDATE', 'users', $id, [
                    'email' => $email,
                    'first_name' => $first_name,
                    'is_active' => $is_active
                ]);
                $_SESSION['success'] = 'Customer updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update customer: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = 'Database error: ' . $conn->error;
        }
        
        header("Location: customers.php?id=" . $id);
        exit();
    }
}

// Get customer details if viewing single customer
$customer = null;
$customer_stats = null;
if ($customer_id > 0) {
    // Get customer details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND is_admin = 0");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();
    
    if ($customer) {
        // Get customer stats
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_spent,
                MIN(created_at) as first_order_date,
                MAX(created_at) as last_order_date
            FROM orders 
            WHERE user_id = ? AND status != 'cancelled'
        ");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer_stats = $result->fetch_assoc();
        $stmt->close();
    }
}

// Get all customers for listing
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where = ["u.is_admin = 0"];  // FIXED: Changed from "is_admin = 0" to "u.is_admin = 0"
$params = [];
$types = '';

if ($search) {
    $where[] = "(u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.phone LIKE ?)";
    $search_term = "%$search%";
    array_push($params, $search_term, $search_term, $search_term, $search_term);
    $types .= 'ssss';
}

if ($status_filter === 'active') {
    $where[] = "u.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $where[] = "u.is_active = 0";
} elseif ($status_filter === 'subscribed') {
    $where[] = "u.newsletter_subscribed = 1";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Count total customers
$count_query = "SELECT COUNT(*) as total FROM users u $where_clause";

if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_customers = $result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $result = $conn->query($count_query);
    $total_customers = $result->fetch_assoc()['total'];
}

$total_pages = ceil($total_customers / $limit);

// Get customers with stats - FIXED: Added table aliases
$query = "
    SELECT u.*,
           (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as order_count,
           (SELECT SUM(total_amount) FROM orders o WHERE o.user_id = u.id AND o.status != 'cancelled') as total_spent
    FROM users u
    $where_clause
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
";

// Add pagination parameters
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if ($stmt) {
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $customers = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $customers = [];
    error_log("Customers query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - Admin Panel</title>
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
                <?php if ($customer_id && $customer): ?>👤 Customer Details
                <?php else: ?>👥 Manage Customers<?php endif; ?>
            </h1>
            <div>
                <?php if ($customer_id && $customer): ?>
                    <a href="customers.php" class="admin-back-btn">← Back to Customers</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="products.php">Products</a>
            <a href="categories.php">Categories</a>
            <a href="orders.php">Orders</a>
            <a href="customers.php" class="active">Customers</a>
            <a href="inventory.php">Inventory</a>
            <a href="reports.php">Reports</a>
        </div>
        
        <?php if ($customer_id && $customer): ?>
            <!-- Customer Details -->
            <div class="customer-details-grid">
                <div class="customer-card">
                    <h3>👤 Customer Information</h3>
                    <div class="customer-info">
                        <p><strong>Customer ID:</strong> #<?php echo $customer['id']; ?></p>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></p>
                        <p><strong>Joined:</strong> <?php echo date('F j, Y', strtotime($customer['created_at'])); ?></p>
                        <p><strong>Last Updated:</strong> <?php echo date('F j, Y', strtotime($customer['updated_at'])); ?></p>
                    </div>
                </div>
                
                <div class="customer-card">
                    <h3>📊 Customer Stats</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-label">Total Orders</div>
                            <div class="stat-value"><?php echo $customer_stats['total_orders'] ?? 0; ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Total Spent</div>
                            <div class="stat-value">$<?php echo number_format($customer_stats['total_spent'] ?? 0, 2); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">First Order</div>
                            <div class="stat-value">
                                <?php if ($customer_stats['first_order_date']): ?>
                                    <?php echo date('M Y', strtotime($customer_stats['first_order_date'])); ?>
                                <?php else: ?>-<?php endif; ?>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Last Order</div>
                            <div class="stat-value">
                                <?php if ($customer_stats['last_order_date']): ?>
                                    <?php echo date('M Y', strtotime($customer_stats['last_order_date'])); ?>
                                <?php else: ?>-<?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="customer-card">
                    <h3>⚙️ Edit Customer</h3>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $customer_id; ?>">
                        
                        <div class="admin-form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="admin-form-control"
                                   value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                        </div>
                        
                        <div class="admin-form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="admin-form-control"
                                   value="<?php echo htmlspecialchars($customer['last_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="admin-form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="admin-form-control"
                                   value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                        </div>
                        
                        <div class="admin-form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" class="admin-form-control"
                                   value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="admin-checkbox-group">
                            <input type="checkbox" id="newsletter_subscribed" name="newsletter_subscribed" value="1"
                                   <?php echo $customer['newsletter_subscribed'] ? 'checked' : ''; ?>>
                            <label for="newsletter_subscribed">Newsletter Subscribed</label>
                        </div>
                        
                        <div class="admin-checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                   <?php echo ($customer['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="is_active">Account Active</label>
                        </div>
                        
                        <div class="admin-form-actions">
                            <button type="submit" class="admin-btn admin-btn-primary">💾 Save Changes</button>
                            <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>" class="admin-btn admin-btn-secondary">📧 Email Customer</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div style="margin-top: 30px; text-align: center;">
                <a href="orders.php?search=<?php echo urlencode($customer['email']); ?>" 
                   class="admin-btn admin-btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                    📋 View All Orders
                </a>
            </div>
            
        <?php else: ?>
            <!-- Customers List -->
            <div class="admin-filters">
                <form method="GET" class="admin-filter-form">
                    <input type="text" name="search" placeholder="Search customers..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="admin-form-control">
                    <select name="status" class="admin-form-control">
                        <option value="">All Customers</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="subscribed" <?php echo $status_filter === 'subscribed' ? 'selected' : ''; ?>>Newsletter Subscribers</option>
                    </select>
                    <button type="submit" class="admin-btn admin-btn-primary">🔍 Search</button>
                    <?php if ($search || $status_filter): ?>
                    <a href="customers.php" style="color: #6366f1; text-decoration: none; padding: 0 20px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if (!empty($customers)): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $cust): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']); ?></strong><br>
                                    <small style="color: rgba(248,250,252,0.6);">
                                        ID: #<?php echo $cust['id']; ?> | 
                                        Joined: <?php echo date('M Y', strtotime($cust['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($cust['email']); ?><br>
                                    <?php if ($cust['phone']): ?>
                                    <small style="color: rgba(248,250,252,0.6);"><?php echo htmlspecialchars($cust['phone']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="background: rgba(99, 102, 241, 0.2); color: #6366f1; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 600;">
                                        <?php echo $cust['order_count']; ?> order(s)
                                    </span>
                                </td>
                                <td>
                                    <strong>$<?php echo number_format($cust['total_spent'] ?? 0, 2); ?></strong>
                                </td>
                                <td>
                                    <span class="admin-status-badge <?php echo ($cust['is_active'] ?? 1) ? 'admin-status-active' : 'admin-status-inactive'; ?>">
                                        <?php echo ($cust['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                    <?php if ($cust['newsletter_subscribed']): ?>
                                    <span style="background: rgba(156, 39, 176, 0.2); color: #9C27B0; margin-top: 4px; display: block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">
                                        Subscribed
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="admin-action-buttons">
                                        <a href="customers.php?id=<?php echo $cust['id']; ?>" 
                                           class="admin-btn-action admin-btn-view" title="View Details">👁️</a>
                                        <a href="mailto:<?php echo htmlspecialchars($cust['email']); ?>" 
                                           class="admin-btn-action admin-btn-view" title="Send Email">📧</a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?php echo $cust['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo ($cust['is_active'] ?? 1) ? 0 : 1; ?>">
                                            <button type="submit" class="admin-btn-action admin-btn-view" 
                                                    title="<?php echo ($cust['is_active'] ?? 1) ? 'Deactivate' : 'Activate'; ?>"
                                                    onclick="return confirm('Are you sure you want to <?php echo ($cust['is_active'] ?? 1) ? 'deactivate' : 'activate'; ?> this customer?')">
                                                <?php echo ($cust['is_active'] ?? 1) ? '⛔' : '✅'; ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px; color: rgba(248,250,252,0.6); text-align: center;">
                    Showing <?php echo count($customers); ?> of <?php echo $total_customers; ?> customer(s)
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="admin-pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=1&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">««</a>
                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">«</a>
                    <?php endif; ?>
                    
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $start + 4);
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>"
                       class="<?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">»</a>
                    <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>">»»</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: rgba(248,250,252,0.5);">
                    <div style="font-size: 48px; margin-bottom: 20px;">👥</div>
                    <h3>No customers found</h3>
                    <?php if ($search || $status_filter): ?>
                        <p>Try adjusting your search filters</p>
                        <a href="customers.php" style="display: inline-block; margin-top: 15px; color: #6366f1;">Clear filters</a>
                    <?php else: ?>
                        <p>No customers have registered yet</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
    // Form validation
    document.querySelectorAll('.admin-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]')?.value.trim();
            const firstName = this.querySelector('input[name="first_name"]')?.value.trim();
            
            if (!email || !firstName) {
                e.preventDefault();
                alert('Email and First Name are required');
                return false;
            }
            
            const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            if (!emailValid) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
            
            return true;
        });
    });
    
    // Toggle status confirmation
    document.querySelectorAll('form[action*="update"] button').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const form = this.closest('form');
            const isActive = form.querySelector('input[name="is_active"]').value;
            const action = isActive == 1 ? 'deactivate' : 'activate';
            
            if (!confirm(`Are you sure you want to ${action} this customer?`)) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>

<?php 
// Close connection
$conn->close();
?>