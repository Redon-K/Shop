<?php
// admin/reports.php
session_start();
require_once '../../PHP/config.php';
require_once '../../PHP/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit();
}

$conn = getDBConnection();

// Get report parameters
$report_type = $_GET['type'] ?? 'sales';
$date_range = $_GET['range'] ?? 'month';
$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-t');
$category_id = $_GET['category'] ?? '';

// Default to current month if not specified
if (!$start_date) $start_date = date('Y-m-01');
if (!$end_date) $end_date = date('Y-m-t');

// Get categories for filter
$categories_result = $conn->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Generate reports based on type
$report_data = [];
$report_title = '';
$chart_labels = [];
$chart_data = [];

switch ($report_type) {
    case 'sales':
        $report_title = 'Sales Report';
        generate_sales_report($start_date, $end_date, $category_id);
        break;
    case 'products':
        $report_title = 'Products Report';
        generate_products_report($start_date, $end_date, $category_id);
        break;
    case 'customers':
        $report_title = 'Customers Report';
        generate_customers_report($start_date, $end_date);
        break;
    case 'inventory':
        $report_title = 'Inventory Report';
        generate_inventory_report();
        break;
}

function generate_sales_report($start_date, $end_date, $category_id) {
    global $conn, $report_data, $chart_labels, $chart_data, $report_title;
    
    // Date-wise sales
    $date_query = "
        SELECT DATE(o.created_at) as sale_date,
               COUNT(DISTINCT o.id) as order_count,
               COUNT(oi.id) as item_count,
               SUM(oi.total_price) as total_sales,
               SUM(oi.quantity) as total_quantity
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
          AND o.status != 'cancelled'
    ";
    
    $params = [$start_date, $end_date];
    $types = "ss";
    
    if ($category_id) {
        $date_query .= " AND oi.product_id IN (SELECT id FROM products WHERE category_id = ?)";
        $params[] = $category_id;
        $types .= "i";
        $report_title .= " - Category Filtered";
    }
    
    $date_query .= " GROUP BY DATE(o.created_at) ORDER BY sale_date";
    
    $stmt = $conn->prepare($date_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $date_sales = $result->fetch_all(MYSQLI_ASSOC);
    
    // Prepare chart data
    $chart_labels = array_column($date_sales, 'sale_date');
    $chart_data = array_column($date_sales, 'total_sales');
    
    // Summary stats
    $summary_query = "
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            COUNT(DISTINCT o.user_id) as total_customers,
            SUM(o.total_amount) as total_revenue,
            AVG(o.total_amount) as avg_order_value,
            SUM(o.shipping_amount) as total_shipping,
            SUM(o.tax_amount) as total_tax
        FROM orders o
        WHERE DATE(o.created_at) BETWEEN ? AND ?
          AND o.status != 'cancelled'
    ";
    
    $stmt = $conn->prepare($summary_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc();
    
    // Top products
    $products_query = "
        SELECT 
            p.name as product_name,
            p.category_id,
            c.name as category_name,
            SUM(oi.quantity) as total_sold,
            SUM(oi.total_price) as total_revenue,
            AVG(oi.product_price) as avg_price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
          AND o.status != 'cancelled'
    ";
    
    if ($category_id) {
        $products_query .= " AND p.category_id = ?";
        $stmt = $conn->prepare($products_query);
        $stmt->bind_param("ssi", $start_date, $end_date, $category_id);
    } else {
        $stmt = $conn->prepare($products_query);
        $stmt->bind_param("ss", $start_date, $end_date);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $top_products = $result->fetch_all(MYSQLI_ASSOC);
    
    $report_data = [
        'summary' => $summary,
        'date_sales' => $date_sales,
        'top_products' => $top_products,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

function generate_products_report($start_date, $end_date, $category_id) {
    global $conn, $report_data, $chart_labels, $chart_data, $report_title;
    
    $query = "
        SELECT 
            p.id,
            p.name,
            p.sku,
            c.name as category_name,
            p.price,
            p.stock_quantity,
            COALESCE(SUM(oi.quantity), 0) as total_sold,
            COALESCE(SUM(oi.total_price), 0) as total_revenue,
            COALESCE(AVG(oi.product_price), p.price) as avg_sale_price,
            (p.stock_quantity * p.price) as stock_value
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'
        WHERE p.is_active = 1
    ";
    
    $params = [$start_date, $end_date];
    $types = "ss";
    
    if ($category_id) {
        $query .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
        $report_title .= " - Category Filtered";
    }
    
    $query .= " GROUP BY p.id, p.name, p.sku, c.name, p.price, p.stock_quantity
                ORDER BY total_sold DESC, total_revenue DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    
    // Prepare chart data for top 10 products
    $top_10 = array_slice($products, 0, 10);
    $chart_labels = array_column($top_10, 'name');
    $chart_data = array_column($top_10, 'total_sold');
    
    // Summary stats
    $summary = [
        'total_products' => count($products),
        'total_stock_value' => array_sum(array_column($products, 'stock_value')),
        'total_sold' => array_sum(array_column($products, 'total_sold')),
        'total_revenue' => array_sum(array_column($products, 'total_revenue')),
        'out_of_stock' => count(array_filter($products, fn($p) => $p['stock_quantity'] == 0)),
        'low_stock' => count(array_filter($products, fn($p) => $p['stock_quantity'] > 0 && $p['stock_quantity'] < 10))
    ];
    
    $report_data = [
        'summary' => $summary,
        'products' => $products,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

function generate_customers_report($start_date, $end_date) {
    global $conn, $report_data, $chart_labels, $chart_data;
    
    $query = "
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.created_at as join_date,
            COUNT(DISTINCT o.id) as total_orders,
            SUM(o.total_amount) as total_spent,
            MIN(o.created_at) as first_order_date,
            MAX(o.created_at) as last_order_date,
            AVG(o.total_amount) as avg_order_value
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id 
            AND DATE(o.created_at) BETWEEN ? AND ? 
            AND o.status != 'cancelled'
        WHERE u.is_admin = 0
        GROUP BY u.id, u.first_name, u.last_name, u.email, u.created_at
        ORDER BY total_spent DESC, total_orders DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $customers = $result->fetch_all(MYSQLI_ASSOC);
    
    // Prepare chart data for top 10 customers
    $top_10 = array_slice($customers, 0, 10);
    $chart_labels = array_column($top_10, 'email');
    $chart_data = array_column($top_10, 'total_spent');
    
    // Summary stats
    $summary = [
        'total_customers' => count($customers),
        'active_customers' => count(array_filter($customers, fn($c) => $c['total_orders'] > 0)),
        'total_revenue' => array_sum(array_column($customers, 'total_spent')),
        'avg_orders_per_customer' => count($customers) > 0 ? 
            array_sum(array_column($customers, 'total_orders')) / count($customers) : 0,
        'new_customers' => count(array_filter($customers, 
            fn($c) => strtotime($c['join_date']) >= strtotime($start_date)))
    ];
    
    $report_data = [
        'summary' => $summary,
        'customers' => $customers,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

function generate_inventory_report() {
    global $conn, $report_data, $chart_labels, $chart_data;
    
    $query = "
        SELECT 
            p.id,
            p.name,
            p.sku,
            c.name as category_name,
            p.price,
            p.stock_quantity,
            (p.stock_quantity * p.price) as stock_value,
            COALESCE(SUM(sm.change_quantity), 0) as monthly_movement,
            SUM(CASE WHEN sm.operation = 'add' THEN sm.change_quantity ELSE 0 END) as monthly_in,
            SUM(CASE WHEN sm.operation = 'remove' THEN sm.change_quantity ELSE 0 END) as monthly_out
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN stock_movements sm ON p.id = sm.product_id 
            AND sm.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        WHERE p.is_active = 1
        GROUP BY p.id, p.name, p.sku, c.name, p.price, p.stock_quantity
        ORDER BY stock_value DESC, p.stock_quantity ASC
    ";
    
    $result = $conn->query($query);
    $inventory = $result->fetch_all(MYSQLI_ASSOC);
    
    // Prepare chart data by category
    $category_totals = [];
    foreach ($inventory as $item) {
        $category = $item['category_name'] ?: 'Uncategorized';
        if (!isset($category_totals[$category])) {
            $category_totals[$category] = 0;
        }
        $category_totals[$category] += $item['stock_value'];
    }
    
    $chart_labels = array_keys($category_totals);
    $chart_data = array_values($category_totals);
    
    // Summary stats
    $summary = [
        'total_products' => count($inventory),
        'total_stock_value' => array_sum(array_column($inventory, 'stock_value')),
        'out_of_stock' => count(array_filter($inventory, fn($i) => $i['stock_quantity'] == 0)),
        'low_stock' => count(array_filter($inventory, fn($i) => $i['stock_quantity'] > 0 && $i['stock_quantity'] < 10)),
        'high_value_items' => count(array_filter($inventory, fn($i) => $i['stock_value'] > 1000))
    ];
    
    $report_data = [
        'summary' => $summary,
        'inventory' => $inventory
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
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
            <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
            <div class="admin-action-buttons">
                <button type="button" class="admin-btn admin-btn-primary" onclick="printReport()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button type="button" class="admin-btn admin-btn-secondary" onclick="exportReport()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
        
        <div class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="products.php">Products</a>
            <a href="categories.php">Categories</a>
            <a href="orders.php">Orders</a>
            <a href="customers.php">Customers</a>
            <a href="inventory.php">Inventory</a>
            <a href="reports.php" class="active">Reports</a>
        </div>
        
        <!-- Report Filters -->
        <div class="admin-filters">
            <form method="GET" class="admin-filter-form">
                <div class="admin-form-group" style="margin-bottom: 0;">
                    <label for="reportType">Report Type</label>
                    <select id="reportType" name="type" class="admin-form-control">
                        <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>Sales Report</option>
                        <option value="products" <?php echo $report_type === 'products' ? 'selected' : ''; ?>>Products Report</option>
                        <option value="customers" <?php echo $report_type === 'customers' ? 'selected' : ''; ?>>Customers Report</option>
                        <option value="inventory" <?php echo $report_type === 'inventory' ? 'selected' : ''; ?>>Inventory Report</option>
                    </select>
                </div>
                
                <div class="admin-form-group" style="margin-bottom: 0;">
                    <label for="dateRange">Date Range</label>
                    <select id="dateRange" name="range" class="admin-form-control">
                        <option value="today" <?php echo $date_range === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="yesterday" <?php echo $date_range === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                        <option value="week" <?php echo $date_range === 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo $date_range === 'month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="quarter" <?php echo $date_range === 'quarter' ? 'selected' : ''; ?>>This Quarter</option>
                        <option value="year" <?php echo $date_range === 'year' ? 'selected' : ''; ?>>This Year</option>
                        <option value="custom" <?php echo $date_range === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                    </select>
                </div>
                
                <div class="admin-form-group" id="customDateRange" style="<?php echo $date_range === 'custom' ? '' : 'display: none; margin-bottom: 0;'; ?>">
                    <label>Custom Date Range</label>
                    <div class="date-range-inputs">
                        <input type="date" name="start" value="<?php echo $start_date; ?>" class="admin-form-control">
                        <span>to</span>
                        <input type="date" name="end" value="<?php echo $end_date; ?>" class="admin-form-control">
                    </div>
                </div>
                
                <?php if ($report_type === 'sales' || $report_type === 'products'): ?>
                <div class="admin-form-group" style="margin-bottom: 0;">
                    <label for="categoryFilter">Category Filter</label>
                    <select id="categoryFilter" name="category" class="admin-form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                            <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="admin-form-group" style="margin-bottom: 0;">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <i class="fas fa-chart-line"></i> Generate Report
                    </button>
                    <?php if (isset($_GET['type']) || isset($_GET['range']) || isset($_GET['category'])): ?>
                    <a href="reports.php" class="admin-btn admin-btn-secondary">Clear Filters</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Report Summary Stats -->
        <?php if (isset($report_data['summary'])): ?>
        <div class="admin-stats-grid">
            <?php foreach ($report_data['summary'] as $key => $value): ?>
            <?php 
            $titles = [
                'total_orders' => ['Total Orders', 'fas fa-shopping-cart', '#4CAF50'],
                'total_revenue' => ['Total Revenue', 'fas fa-dollar-sign', '#2196F3'],
                'total_customers' => ['Total Customers', 'fas fa-users', '#FF9800'],
                'avg_order_value' => ['Avg Order Value', 'fas fa-chart-line', '#9C27B0'],
                'total_products' => ['Total Products', 'fas fa-box', '#4CAF50'],
                'total_stock_value' => ['Stock Value', 'fas fa-warehouse', '#2196F3'],
                'total_sold' => ['Total Sold', 'fas fa-chart-bar', '#FF9800'],
                'active_customers' => ['Active Customers', 'fas fa-user-check', '#9C27B0'],
                'out_of_stock' => ['Out of Stock', 'fas fa-exclamation-triangle', '#f44336'],
                'low_stock' => ['Low Stock', 'fas fa-exclamation-circle', '#FF9800'],
                'new_customers' => ['New Customers', 'fas fa-user-plus', '#4CAF50']
            ];
            
            if (isset($titles[$key])):
                [$title, $icon, $color] = $titles[$key];
                $formatted_value = strpos($key, 'revenue') !== false || strpos($key, 'value') !== false ? 
                    '$' . number_format($value, 2) : 
                    (is_numeric($value) ? number_format($value) : $value);
            ?>
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background: <?php echo $color; ?>;">
                    <i class="<?php echo $icon; ?>"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $formatted_value; ?></h3>
                    <p><?php echo $title; ?></p>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Report Chart -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-chart-line"></i> <?php echo $report_title; ?> Chart</h3>
                <?php if (isset($report_data['start_date'])): ?>
                <span style="color: rgba(248,250,252,0.6); font-size: 14px;">
                    Period: <?php echo date('F d, Y', strtotime($report_data['start_date'])); ?> 
                    to <?php echo date('F d, Y', strtotime($report_data['end_date'])); ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="admin-card-body">
                <div class="admin-chart-container">
                    <canvas id="reportChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Report Details -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-table"></i> Report Details</h3>
            </div>
            <div class="admin-card-body">
                <?php if ($report_type === 'sales'): ?>
                <!-- Sales Report Details -->
                <div style="margin-bottom: 30px;">
                    <h4 style="color: rgba(248,250,252,0.9); font-size: 16px; margin: 0 0 15px 0; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-calendar-alt"></i> Daily Sales
                    </h4>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Items Sold</th>
                                <th>Total Sales</th>
                                <th>Avg. Order Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($report_data['date_sales'])): ?>
                                <?php foreach ($report_data['date_sales'] as $day): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($day['sale_date'])); ?></td>
                                    <td><?php echo $day['order_count']; ?></td>
                                    <td><?php echo $day['item_count']; ?></td>
                                    <td>$<?php echo number_format($day['total_sales'], 2); ?></td>
                                    <td>$<?php echo $day['order_count'] > 0 ? number_format($day['total_sales'] / $day['order_count'], 2) : '0.00'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="admin-empty-state">No sales data for selected period</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div>
                    <h4 style="color: rgba(248,250,252,0.9); font-size: 16px; margin: 0 0 15px 0; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-star"></i> Top Products
                    </h4>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                                <th>Avg. Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($report_data['top_products'])): ?>
                                <?php foreach (array_slice($report_data['top_products'], 0, 10) as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo $product['total_sold']; ?></td>
                                    <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                    <td>$<?php echo number_format($product['avg_price'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="admin-empty-state">No product sales data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php elseif ($report_type === 'products'): ?>
                <!-- Products Report Details -->
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                            <th>Stock Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report_data['products'])): ?>
                            <?php foreach ($report_data['products'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <span class="admin-stock-badge <?php 
                                        echo $product['stock_quantity'] == 0 ? 'admin-stock-danger' : 
                                             ($product['stock_quantity'] < 10 ? 'admin-stock-warning' : 'admin-stock-success'); 
                                    ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo $product['total_sold']; ?></td>
                                <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                <td>$<?php echo number_format($product['stock_value'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="admin-empty-state">No products found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php elseif ($report_type === 'customers'): ?>
                <!-- Customers Report Details -->
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Join Date</th>
                            <th>Total Orders</th>
                            <th>Total Spent</th>
                            <th>Avg. Order Value</th>
                            <th>First Order</th>
                            <th>Last Order</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report_data['customers'])): ?>
                            <?php foreach ($report_data['customers'] as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($customer['join_date'])); ?></td>
                                <td><?php echo $customer['total_orders']; ?></td>
                                <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                <td>$<?php echo number_format($customer['avg_order_value'], 2); ?></td>
                                <td><?php echo $customer['first_order_date'] ? date('M d, Y', strtotime($customer['first_order_date'])) : 'No orders'; ?></td>
                                <td><?php echo $customer['last_order_date'] ? date('M d, Y', strtotime($customer['last_order_date'])) : 'No orders'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="admin-empty-state">No customers found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php elseif ($report_type === 'inventory'): ?>
                <!-- Inventory Report Details -->
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Stock Value</th>
                            <th>Monthly In</th>
                            <th>Monthly Out</th>
                            <th>Net Movement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report_data['inventory'])): ?>
                            <?php foreach ($report_data['inventory'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <span class="admin-stock-badge <?php 
                                        echo $item['stock_quantity'] == 0 ? 'admin-stock-danger' : 
                                             ($item['stock_quantity'] < 10 ? 'admin-stock-warning' : 'admin-stock-success'); 
                                    ?>">
                                        <?php echo $item['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($item['stock_value'], 2); ?></td>
                                <td><?php echo $item['monthly_in']; ?></td>
                                <td><?php echo $item['monthly_out']; ?></td>
                                <td><?php echo $item['monthly_movement']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="admin-empty-state">No inventory data found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Initialize Report Chart
    const reportChartCtx = document.getElementById('reportChart');
    
    if (reportChartCtx && <?php echo !empty($chart_labels) ? 'true' : 'false'; ?>) {
        const chartType = '<?php echo $report_type === "inventory" ? "pie" : "bar"; ?>';
        const chartData = {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: '<?php echo $report_type === "sales" ? "Revenue ($)" : ($report_type === "products" ? "Units Sold" : ($report_type === "customers" ? "Total Spent ($)" : "Stock Value ($)")); ?>',
                data: <?php echo json_encode($chart_data); ?>,
                backgroundColor: chartType === 'pie' ? [
                    '#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#f44336',
                    '#00BCD4', '#E91E63', '#8BC34A', '#FF5722', '#795548'
                ] : '#4361ee',
                borderColor: chartType === 'pie' ? '#fff' : '#4361ee',
                borderWidth: 2
            }]
        };
        
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: chartType === 'pie' ? 'right' : 'top',
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
                    borderWidth: 1
                }
            },
            scales: chartType === 'bar' ? {
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
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255,255,255,0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        color: 'rgba(248,250,252,0.7)',
                        callback: function(value) {
                            return '<?php echo $report_type === "sales" || $report_type === "customers" || $report_type === "inventory" ? "$" : ""; ?>' + value.toLocaleString();
                        }
                    }
                }
            } : {}
        };
        
        new Chart(reportChartCtx, {
            type: chartType,
            data: chartData,
            options: chartOptions
        });
    }
    
    // Date range toggle
    const dateRangeSelect = document.getElementById('dateRange');
    const customDateRange = document.getElementById('customDateRange');
    
    dateRangeSelect?.addEventListener('change', function() {
        if (this.value === 'custom') {
            customDateRange.style.display = 'block';
        } else {
            customDateRange.style.display = 'none';
            // Set dates based on selection
            const now = new Date();
            let start, end;
            
            switch (this.value) {
                case 'today':
                    start = end = now.toISOString().split('T')[0];
                    break;
                case 'yesterday':
                    const yesterday = new Date(now);
                    yesterday.setDate(now.getDate() - 1);
                    start = end = yesterday.toISOString().split('T')[0];
                    break;
                case 'week':
                    const weekStart = new Date(now);
                    weekStart.setDate(now.getDate() - 7);
                    start = weekStart.toISOString().split('T')[0];
                    end = now.toISOString().split('T')[0];
                    break;
                case 'month':
                    start = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
                    end = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
                    break;
                case 'quarter':
                    const quarter = Math.floor(now.getMonth() / 3);
                    start = new Date(now.getFullYear(), quarter * 3, 1).toISOString().split('T')[0];
                    end = new Date(now.getFullYear(), quarter * 3 + 3, 0).toISOString().split('T')[0];
                    break;
                case 'year':
                    start = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
                    end = new Date(now.getFullYear(), 11, 31).toISOString().split('T')[0];
                    break;
            }
            
            if (start && end) {
                document.querySelector('input[name="start"]').value = start;
                document.querySelector('input[name="end"]').value = end;
            }
        }
    });
    
    // Print report
    function printReport() {
        window.print();
    }
    
    // Export report
    function exportReport() {
        alert('Export feature would be implemented here. In a real application, this would generate a CSV/PDF file.');
        // In a real implementation, you would call an export script:
        // const params = new URLSearchParams(window.location.search);
        // window.location.href = `export_report.php?${params.toString()}`;
    }
    
    // Add styles for date range inputs
    const style = document.createElement('style');
    style.textContent = `
        .date-range-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .date-range-inputs span {
            color: rgba(248,250,252,0.6);
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>

<?php 
// Close connections
if (isset($categories_result)) $categories_result->free();
$conn->close();
?>