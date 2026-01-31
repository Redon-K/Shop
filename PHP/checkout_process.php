<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to checkout']);
    exit();
}

require_once 'config.php';
$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data)) {
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Get cart items from localStorage or from cart_items table
    $cart_items = [];
    
    // Try to get from localStorage data first
    if (isset($data['cart_items'])) {
        $cart_items = $data['cart_items'];
    } else {
        // Fallback: get from database cart_items table
        $stmt = $conn->prepare("
            SELECT ci.*, p.name, p.price, p.image_url 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.id 
            WHERE ci.user_id = ? AND p.is_active = 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($item = $result->fetch_assoc()) {
            $cart_items[] = [
                'product_id' => $item['product_id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'image_url' => $item['image_url']
            ];
        }
        $stmt->close();
    }
    
    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
        exit();
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += ($item['price'] * $item['quantity']);
    }
    
    $shipping_amount = 4.99; // Fixed shipping
    $tax_amount = $subtotal * 0.07; // 7% tax
    $total_amount = $subtotal + $shipping_amount + $tax_amount;
    
    // Generate unique order number
    $order_number = 'ORD-' . strtoupper(uniqid());
    
    // Prepare shipping address
    $shipping_address = '';
    if (isset($data['shipping'])) {
        $shipping = $data['shipping'];
        $shipping_address = implode("\n", [
            $shipping['name'] ?? '',
            $shipping['address'] ?? '',
            $shipping['city'] ?? '',
            $shipping['postal'] ?? '',
            $shipping['country'] ?? ''
        ]);
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert order
        $stmt = $conn->prepare("
            INSERT INTO orders (
                order_number, user_id, status, total_amount, subtotal,
                tax_amount, shipping_amount, shipping_address,
                payment_method, payment_status
            ) VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, 'paid')
        ");
        
        $payment_method = $data['payment']['method'] ?? 'card';
        $stmt->bind_param(
            "siddddss",
            $order_number,
            $user_id,
            $total_amount,
            $subtotal,
            $tax_amount,
            $shipping_amount,
            $shipping_address,
            $payment_method
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create order: ' . $stmt->error);
        }
        
        $order_id = $conn->insert_id;
        $stmt->close();
        
        // Insert order items
        $stmt = $conn->prepare("
            INSERT INTO order_items (
                order_id, product_id, product_name, product_price,
                quantity, total_price
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($cart_items as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $stmt->bind_param(
                "iisdid",
                $order_id,
                $item['product_id'],
                $item['name'],
                $item['price'],
                $item['quantity'],
                $item_total
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to add order item: ' . $stmt->error);
            }
            
            // Update product stock
            $update_stmt = $conn->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - ? 
                WHERE id = ? AND stock_quantity >= ?
            ");
            $update_stmt->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
            
            if (!$update_stmt->execute()) {
                throw new Exception('Insufficient stock for product: ' . $item['name']);
            }
            $update_stmt->close();
        }
        $stmt->close();
        
        // Clear user's cart from database
        $clear_stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $clear_stmt->bind_param("i", $user_id);
        $clear_stmt->execute();
        $clear_stmt->close();
        
        // Log admin action if admin is placing order
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            log_admin_action($user_id, 'ORDER', 'orders', $order_id, [
                'order_number' => $order_number,
                'total' => $total_amount,
                'items' => count($cart_items)
            ]);
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully!',
            'order_number' => $order_number,
            'order_id' => $order_id,
            'total' => number_format($total_amount, 2)
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    $conn->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?>