<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth();

// Check authentication
if (!$auth->check_auth() || !$auth->is_admin()) {
    send_json_response(['success' => false, 'message' => 'Admin access required'], 403);
}

$conn = getDBConnection();

// Get stats
$result = $conn->query("SELECT COUNT(*) as total FROM products");
$total_products = (int)$result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM orders");
$total_orders = (int)$result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
$total_users = (int)$result->fetch_assoc()['total'];

$result = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'delivered'");
$revenue_data = $result->fetch_assoc();
$total_revenue = (float)($revenue_data['revenue'] ?? 0);

// Get sales data for chart (last 7 days)
$result = $conn->query("
    SELECT DATE(created_at) as date, COUNT(*) as order_count, SUM(total_amount) as daily_revenue
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

// Fill missing days
$seven_days_ago = date('Y-m-d', strtotime('-6 days'));
$today = date('Y-m-d');
$all_dates = [];

for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime($seven_days_ago . " +$i days"));
    $all_dates[] = date('M d', strtotime($date));
    
    // Check if date exists in data
    $found = false;
    foreach ($sales_data as $day) {
        if (date('M d', strtotime($day['date'])) == date('M d', strtotime($date))) {
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $chart_dates[] = date('M d', strtotime($date));
        $chart_revenue[] = 0;
        $chart_orders[] = 0;
    }
}

// Sort arrays by date
array_multisort($chart_dates, $chart_revenue, $chart_orders);

send_json_response([
    'success' => true,
    'products' => $total_products,
    'orders' => $total_orders,
    'customers' => $total_users,
    'revenue' => $total_revenue,
    'chart_dates' => $chart_dates,
    'chart_revenue' => $chart_revenue,
    'chart_orders' => $chart_orders
]);
?>