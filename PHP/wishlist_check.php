<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['loggedIn' => false]);
    exit();
}

require_once 'config.php';
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Check if a specific product is in wishlist
if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode([
        'loggedIn' => true,
        'inWishlist' => $result->num_rows > 0
    ]);
    $stmt->close();
} else {
    echo json_encode(['loggedIn' => true]);
}

$conn->close();
?>