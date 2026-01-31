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
$user_id = $_SESSION['user_id'];

// Check for new orders in last 5 minutes
$five_minutes_ago = date('Y-m-d H:i:s', strtotime('-5 minutes'));
$result = $conn->query("
    SELECT COUNT(*) as new_orders 
    FROM orders 
    WHERE created_at > '$five_minutes_ago' AND status = 'pending'
");

$new_orders = (int)$result->fetch_assoc()['new_orders'];

// Check for low stock products
$result = $conn->query("
    SELECT COUNT(*) as low_stock 
    FROM products 
    WHERE stock_quantity < 5 AND is_active = 1
");

$low_stock = (int)$result->fetch_assoc()['low_stock'];

$notifications = [];

if ($new_orders > 0) {
    $notifications[] = [
        'message' => "You have $new_orders new order(s) pending",
        'type' => 'info'
    ];
}

if ($low_stock > 0) {
    $notifications[] = [
        'message' => "$low_stock product(s) are low in stock",
        'type' => 'warning'
    ];
}

send_json_response([
    'success' => true,
    'notifications' => $notifications,
    'new_orders' => $new_orders,
    'low_stock' => $low_stock
]);
?>