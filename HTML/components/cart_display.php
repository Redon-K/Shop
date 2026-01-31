<?php
// components/cart_display.php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo '<div class="cart-empty">Please login to view your cart</div>';
    exit();
}

require_once '../PHP/config.php';
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get cart items from database
$query = "
    SELECT ci.*, p.name, p.price, p.image_url, p.stock_quantity
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.user_id = ? AND p.is_active = 1
    ORDER BY ci.added_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="cart-empty">Your cart is empty</div>';
} else {
    echo '<div class="cart-items">';
    $subtotal = 0;
    
    while ($item = $result->fetch_assoc()) {
        $item_total = $item['price'] * $item['quantity'];
        $subtotal += $item_total;
        
        echo '
        <div class="cart-item">
            <img src="../' . htmlspecialchars($item['image_url']) . '" alt="' . htmlspecialchars($item['name']) . '">
            <div class="cart-item-details">
                <h4>' . htmlspecialchars($item['name']) . '</h4>
                <p>$' . number_format($item['price'], 2) . ' × ' . $item['quantity'] . '</p>
                <p class="item-total">$' . number_format($item_total, 2) . '</p>
            </div>
        </div>';
    }
    
    $shipping = 4.99;
    $tax = $subtotal * 0.07;
    $total = $subtotal + $shipping + $tax;
    
    echo '
    <div class="cart-totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>$' . number_format($subtotal, 2) . '</span>
        </div>
        <div class="total-row">
            <span>Shipping:</span>
            <span>$' . number_format($shipping, 2) . '</span>
        </div>
        <div class="total-row">
            <span>Tax:</span>
            <span>$' . number_format($tax, 2) . '</span>
        </div>
        <div class="total-row grand-total">
            <span>Total:</span>
            <span>$' . number_format($total, 2) . '</span>
        </div>
    </div>';
    
    echo '<a href="CheckOut.php" class="checkout-btn">Proceed to Checkout</a>';
    echo '</div>';
}

$stmt->close();
$conn->close();
?>