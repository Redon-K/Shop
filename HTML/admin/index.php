<?php
// admin/index.php
session_start();
require_once '../../PHP/config.php';
require_once '../../PHP/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit();
}

$conn = getDBConnection();

// Get dashboard stats
$stats = [];

// Total products
$result = $conn->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
$stats['total_products'] = $result->fetch_assoc()['total'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = $result->fetch_assoc()['total'];

// Total customers
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
$stats['total_customers'] = $result->fetch_assoc()['total'];

// Total revenue
$result = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'delivered'");
$revenue_data = $result->fetch_assoc();
$stats['total_revenue'] = $revenue_data['revenue'] ?? 0;

// Recent orders
$result = $conn->query("
    SELECT o.*, u.email, u.first_name, u.last_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC LIMIT 5
");
$recent_orders = $result->fetch_all(MYSQLI_ASSOC);

// Low stock products
$result = $conn->query("
    SELECT * FROM products 
    WHERE stock_quantity < 10 AND is_active = 1 
    ORDER BY stock_quantity ASC LIMIT 5
");
$low_stock = $result->fetch_all(MYSQLI_ASSOC);

// Recent activity logs
$result = $conn->query("
    SELECT al.*, u.email as admin_email 
    FROM admin_logs al 
    LEFT JOIN users u ON al.admin_id = u.id 
    ORDER BY al.created_at DESC LIMIT 10
");
$recent_activity = $result->fetch_all(MYSQLI_ASSOC);

// Get sales data for chart (last 7 days)
$result = $conn->query("
    SELECT 
        DATE(created_at) as date, 
        COUNT(*) as order_count, 
        SUM(total_amount) as daily_revenue
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$sales_data = $result->fetch_all(MYSQLI_ASSOC);

// Prepare chart data
$chart_dates = [];
$chart_revenue = [];
$chart_orders = [];

foreach ($sales_data as $day) {
    $chart_dates[] = date('M d', strtotime($day['date']));
    $chart_revenue[] = (float)$day['daily_revenue'];
    $chart_orders[] = (int)$day['order_count'];
}

// Get top products
$result = $conn->query("
    SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.total_price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
");
$top_products = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Apex Fuel</title>
    <link rel="stylesheet" href="../../CSS/Home.css">
    <!-- Include admin CSS files -->
    <link rel="stylesheet" href="../../CSS/admin/admin_common.css">
    <link rel="stylesheet" href="../../CSS/admin/admin_forms.css">
    <link rel="stylesheet" href="../../CSS/admin/admin_tables.css">
    <link rel="stylesheet" href="../../CSS/admin/admin_charts.css">
    <!-- External libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Include navbar component -->
    <?php include 'admin_navbar.php'; ?>

    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <div class="welcome-message">
                Welcome back, <strong><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?></strong>!
            </div>
        </div>
        
        <div class="admin-nav">
            <a href="index.php" class="active">Dashboard</a>
            <a href="products.php">Products</a>
            <a href="categories.php">Categories</a>
            <a href="orders.php">Orders</a>
            <a href="customers.php">Customers</a>
            <a href="inventory.php">Inventory</a>
            <a href="reports.php">Reports</a>
            <a href="settings.php">Settings</a>
        </div>
        
        <!-- Stats Cards -->
        <div class="admin-stats-grid">
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_products']); ?></h3>
                    <p>Total Products</p>
                    <a href="products.php" class="admin-stat-link">View All →</a>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_orders']); ?></h3>
                    <p>Total Orders</p>
                    <a href="orders.php" class="admin-stat-link">View All →</a>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_customers']); ?></h3>
                    <p>Customers</p>
                    <a href="customers.php" class="admin-stat-link">View All →</a>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    <p>Total Revenue</p>
                    <a href="reports.php" class="admin-stat-link">View Reports →</a>
                </div>
            </div>
        </div>
        
        <!-- Charts and Content -->
        <div class="content-grid">
            <!-- Sales Chart -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-chart-line"></i> Sales Overview (Last 7 Days)</h3>
                </div>
                <div class="admin-card-body">
                    <div class="admin-chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-shopping-cart"></i> Recent Orders</h3>
                    <a href="orders.php" class="admin-btn-view">View All</a>
                </div>
                <div class="admin-card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="5" class="admin-empty-state">
                                    No recent orders
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?><br>
                                    <small style="color: rgba(248,250,252,0.5);"><?php echo htmlspecialchars($order['email']); ?></small>
                                </td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="admin-status-badge admin-order-status-<?php echo htmlspecialchars($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="orders.php?id=<?php echo $order['id']; ?>" class="admin-btn-action admin-btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Low Stock Products -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Products</h3>
                    <a href="inventory.php" class="admin-btn-view">Manage</a>
                </div>
                <div class="admin-card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Stock</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($low_stock)): ?>
                            <tr>
                                <td colspan="4" class="admin-empty-state">
                                    All products have sufficient stock
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($low_stock as $product): ?>
                            <tr>
                                <td>
                                    <div class="admin-product-info">
                                        <img src="../../<?php echo htmlspecialchars($product['image_url'] ?? 'Images/placeholder.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="admin-product-thumb"
                                             onerror="this.src='../../Images/placeholder.jpg'">
                                        <span><?php echo htmlspecialchars($product['name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="admin-stock-badge <?php echo ($product['stock_quantity'] ?? 0) < 5 ? 'admin-stock-danger' : 'admin-stock-warning'; ?>">
                                        <?php echo $product['stock_quantity'] ?? 0; ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                                <td>
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="admin-btn-action admin-btn-edit">
                                        <i class="fas fa-edit"></i> Restock
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fas fa-history"></i> Recent Activity</h3>
                </div>
                <div class="admin-card-body">
                    <div class="activity-feed">
                        <?php if (empty($recent_activity)): ?>
                        <div class="admin-empty-state">
                            No recent activity
                        </div>
                        <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo get_activity_icon($activity['action']); ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p><?php echo format_admin_activity($activity); ?></p>
                                <span class="activity-time">
                                    <?php echo time_ago_in_words($activity['created_at']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Products -->
        <div class="admin-card" style="margin-bottom: 30px;">
            <div class="admin-card-header">
                <h3><i class="fas fa-star"></i> Top Products (Last 30 Days)</h3>
            </div>
            <div class="admin-card-body">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_products)): ?>
                        <tr>
                            <td colspan="4" class="admin-empty-state">
                                No sales data available
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo $product['total_sold']; ?></td>
                            <td>$<?php echo number_format($product['revenue'] ?? 0, 2); ?></td>
                            <td>
                                <span class="admin-status-badge admin-order-status-delivered">
                                    Trending
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            <div class="admin-card-body">
                <div class="quick-actions">
                    <a href="add_product.php" class="quick-action-btn">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add New Product</span>
                    </a>
                    <a href="orders.php" class="quick-action-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Process Orders</span>
                    </a>
                    <a href="customers.php" class="quick-action-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>View Customers</span>
                    </a>
                    <a href="reports.php" class="quick-action-btn">
                        <i class="fas fa-chart-line"></i>
                        <span>Generate Reports</span>
                    </a>
                    <a href="inventory.php" class="quick-action-btn">
                        <i class="fas fa-boxes"></i>
                        <span>Manage Inventory</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Initialize Sales Chart
    const salesChartCtx = document.getElementById('salesChart');
    
    if (salesChartCtx) {
        const salesChart = new Chart(salesChartCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_dates); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode($chart_revenue); ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y',
                    fill: true
                }, {
                    label: 'Orders',
                    data: <?php echo json_encode($chart_orders); ?>,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#f8fafc',
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(8,12,20,0.95)',
                        titleColor: '#f8fafc',
                        bodyColor: '#f8fafc',
                        borderColor: 'rgba(87,87,243,0.5)',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.label.includes('Revenue')) {
                                    label += '$' + context.parsed.y.toFixed(2);
                                } else {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255,255,255,0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: 'rgba(248,250,252,0.7)'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)',
                            color: 'rgba(248,250,252,0.7)'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: 'rgba(248,250,252,0.7)',
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders',
                            color: 'rgba(248,250,252,0.7)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            color: 'rgba(248,250,252,0.7)'
                        }
                    }
                }
            }
        });
    }
    
    // Auto-refresh dashboard every 60 seconds
    setInterval(function() {
        fetch('../../PHP/api/dashboard_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update stats cards
                    const statCards = document.querySelectorAll('.admin-stat-card h3');
                    if (statCards[0]) statCards[0].textContent = data.products.toLocaleString();
                    if (statCards[1]) statCards[1].textContent = data.orders.toLocaleString();
                    if (statCards[2]) statCards[2].textContent = data.customers.toLocaleString();
                    if (statCards[3]) statCards[3].textContent = '$' + parseFloat(data.revenue).toLocaleString(undefined, {minimumFractionDigits: 2});
                    
                    // Update chart if it exists
                    if (salesChart) {
                        salesChart.data.labels = data.chart_dates;
                        salesChart.data.datasets[0].data = data.chart_revenue;
                        salesChart.data.datasets[1].data = data.chart_orders;
                        salesChart.update();
                    }
                }
            })
            .catch(error => console.error('Error refreshing dashboard:', error));
    }, 60000);
    
    // Add some interactivity to cards
    document.querySelectorAll('.admin-stat-card').forEach(card => {
        card.addEventListener('click', function() {
            const link = this.querySelector('.admin-stat-link');
            if (link) {
                window.location.href = link.href;
            }
        });
    });
    
    // Add hover effect to quick actions
    document.querySelectorAll('.quick-action-btn').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    </script>
</body>
</html>

<?php
// Helper functions for activity display
function get_activity_icon($action) {
    $icons = [
        'INSERT' => 'plus-circle',
        'UPDATE' => 'edit',
        'DELETE' => 'trash-alt',
        'LOGIN' => 'sign-in-alt',
        'LOGOUT' => 'sign-out-alt',
        'ORDER' => 'shopping-cart',
        'PAYMENT' => 'credit-card',
        'BACKUP' => 'database',
        'SETTINGS' => 'cog'
    ];
    return $icons[$action] ?? 'info-circle';
}

function format_admin_activity($activity) {
    $admin = $activity['admin_email'] ?? 'System';
    $action = $activity['action'];
    $table = $activity['table_name'] ?? '';
    $details = json_decode($activity['details'] ?? '{}', true);
    
    switch ($action) {
        case 'LOGIN':
            return "<strong>$admin</strong> logged in";
        case 'INSERT':
            return "<strong>$admin</strong> added new " . strtolower($table) . 
                   (isset($details['name']) ? ": " . $details['name'] : "");
        case 'UPDATE':
            return "<strong>$admin</strong> updated " . strtolower($table) . 
                   (isset($details['name']) ? ": " . $details['name'] : "");
        case 'DELETE':
            return "<strong>$admin</strong> deleted " . strtolower($table);
        default:
            return "<strong>$admin</strong> performed $action on $table";
    }
}

function time_ago_in_words($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return "Just now";
    if ($diff < 3600) return floor($diff/60) . " minutes ago";
    if ($diff < 86400) return floor($diff/3600) . " hours ago";
    if ($diff < 604800) return floor($diff/86400) . " days ago";
    
    return date('M d, Y', $time);
}

// Close connection
$conn->close();
?>