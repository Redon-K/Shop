<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

require_once 'config.php';
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $product_id = intval($data['product_id'] ?? 0);

    if (!$product_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit();
    }

    // Check if product exists
    $check_stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
    $check_stmt->bind_param("i", $product_id);
    $check_stmt->execute();
    $product_result = $check_stmt->get_result();
    if ($product_result->num_rows === 0) {
        $check_stmt->close();
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }
    $check_stmt->close();

    if ($action === 'add') {
        // Check if already in wishlist
        $check_wish = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check_wish->bind_param("ii", $user_id, $product_id);
        $check_wish->execute();
        $wish_result = $check_wish->get_result();
        
        if ($wish_result->num_rows > 0) {
            $check_wish->close();
            echo json_encode(['success' => true, 'message' => 'Already in wishlist', 'action' => 'already_exists']);
        } else {
            $check_wish->close();
            $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id, added_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ii", $user_id, $product_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Added to wishlist', 'action' => 'added']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding to wishlist']);
            }
            $stmt->close();
        }
    } elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist', 'action' => 'removed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error removing from wishlist']);
        }
        $stmt->close();
    } elseif ($action === 'toggle') {
        // Check if already in wishlist
        $check_wish = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check_wish->bind_param("ii", $user_id, $product_id);
        $check_wish->execute();
        $wish_result = $check_wish->get_result();
        
        if ($wish_result->num_rows > 0) {
            // Remove it
            $check_wish->close();
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Removed from wishlist', 'action' => 'removed']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error removing from wishlist']);
            }
            $stmt->close();
        } else {
            // Add it
            $check_wish->close();
            $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id, added_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("ii", $user_id, $product_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Added to wishlist', 'action' => 'added']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding to wishlist']);
            }
            $stmt->close();
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
$conn->close();
?>