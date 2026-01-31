<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../auth.php';

$auth = new Auth();

// Check authentication
if (!$auth->check_auth()) {
    json_response(['success' => false, 'message' => 'Authentication required'], 401);
}

// Check if user is admin
if (!$auth->is_admin()) {
    json_response(['success' => false, 'message' => 'Admin access required'], 403);
}

$conn = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handle_get();
        break;
    case 'POST':
        handle_post();
        break;
    case 'PUT':
        handle_put();
        break;
    case 'DELETE':
        handle_delete();
        break;
    default:
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

function handle_get() {
    global $conn;
    
    $id = $_GET['id'] ?? null;
    $category_id = $_GET['category_id'] ?? null;
    $search = $_GET['search'] ?? '';
    $limit = $_GET['limit'] ?? 20;
    $offset = $_GET['offset'] ?? 0;
    $featured = $_GET['featured'] ?? null;
    
    if ($id) {
        // Get single product
        $stmt = $conn->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ? AND p.is_active = 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            json_response(['success' => false, 'message' => 'Product not found'], 404);
        }
        
        $product = $result->fetch_assoc();
        
        // Process JSON fields
        if ($product['image_urls']) {
            $product['image_urls'] = json_decode($product['image_urls'], true);
        }
        if ($product['tags']) {
            $product['tags'] = json_decode($product['tags'], true);
        }
        if ($product['specifications']) {
            $product['specifications'] = json_decode($product['specifications'], true);
        }
        
        json_response(['success' => true, 'product' => $product]);
    } else {
        // Get multiple products
        $where = ["p.is_active = 1"];
        $params = [];
        $types = '';
        
        if ($category_id) {
            $where[] = "p.category_id = ?";
            $params[] = $category_id;
            $types .= 'i';
        }
        
        if ($search) {
            $where[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
            $search_term = "%$search%";
            array_push($params, $search_term, $search_term, $search_term);
            $types .= 'sss';
        }
        
        if ($featured !== null) {
            $where[] = "p.is_featured = ?";
            $params[] = $featured ? 1 : 0;
            $types .= 'i';
        }
        
        $where_clause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        // Get total count
        $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM products p $where_clause");
        if (!empty($params)) {
            $count_stmt->bind_param($types, ...$params);
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total = $count_result->fetch_assoc()['total'];
        
        // Get products
        $query = "
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            $where_clause 
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            // Process JSON fields
            if ($row['image_urls']) {
                $row['image_urls'] = json_decode($row['image_urls'], true);
            }
            if ($row['tags']) {
                $row['tags'] = json_decode($row['tags'], true);
            }
            if ($row['specifications']) {
                $row['specifications'] = json_decode($row['specifications'], true);
            }
            $products[] = $row;
        }
        
        json_response([
            'success' => true,
            'products' => $products,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
}

function handle_post() {
    global $conn, $auth;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        json_response(['success' => false, 'message' => 'Invalid JSON data'], 400);
    }
    
    // Validate required fields
    $required = ['name', 'category_id', 'price', 'stock_quantity'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            json_response(['success' => false, 'message' => "Field '$field' is required"], 400);
        }
    }
    
    // Generate slug
    $slug = create_slug($data['name']);
    
    // Prepare product data
    $product_data = [
        'category_id' => (int)$data['category_id'],
        'name' => sanitize_input($data['name']),
        'slug' => $slug,
        'description' => sanitize_input($data['description'] ?? ''),
        'short_description' => sanitize_input($data['short_description'] ?? ''),
        'price' => (float)$data['price'],
        'compare_price' => !empty($data['compare_price']) ? (float)$data['compare_price'] : null,
        'cost_price' => !empty($data['cost_price']) ? (float)$data['cost_price'] : null,
        'sku' => sanitize_input($data['sku'] ?? ''),
        'barcode' => sanitize_input($data['barcode'] ?? ''),
        'stock_quantity' => (int)$data['stock_quantity'],
        'weight' => !empty($data['weight']) ? (float)$data['weight'] : null,
        'is_featured' => $data['is_featured'] ?? false ? 1 : 0,
        'is_active' => $data['is_active'] ?? true ? 1 : 0,
        'tags' => !empty($data['tags']) ? json_encode($data['tags']) : null,
        'specifications' => !empty($data['specifications']) ? json_encode($data['specifications']) : null
    ];
    
    // Insert product
    $columns = implode(', ', array_keys($product_data));
    $placeholders = implode(', ', array_fill(0, count($product_data), '?'));
    $types = '';
    $values = [];
    
    foreach ($product_data as $value) {
        $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        $values[] = $value;
    }
    
    $query = "INSERT INTO products ($columns, created_at) VALUES ($placeholders, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        $product_id = $conn->insert_id;
        
        // Log admin action
        $user = $auth->get_current_user();
        log_admin_action($user['id'], 'INSERT', 'products', $product_id, [
            'name' => $product_data['name'],
            'price' => $product_data['price']
        ]);
        
        json_response([
            'success' => true,
            'message' => 'Product created successfully',
            'product_id' => $product_id
        ], 201);
    } else {
        json_response(['success' => false, 'message' => 'Failed to create product'], 500);
    }
}

function handle_put() {
    global $conn, $auth;
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        json_response(['success' => false, 'message' => 'Product ID required'], 400);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        json_response(['success' => false, 'message' => 'Invalid JSON data'], 400);
    }
    
    // Check if product exists
    $check_stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        json_response(['success' => false, 'message' => 'Product not found'], 404);
    }
    
    // Prepare update data
    $update_data = [];
    $types = '';
    $values = [];
    
    $allowed_fields = [
        'category_id', 'name', 'description', 'short_description',
        'price', 'compare_price', 'cost_price', 'sku', 'barcode',
        'stock_quantity', 'weight', 'is_featured', 'is_active',
        'tags', 'specifications'
    ];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $update_data[] = "$field = ?";
            
            if ($field === 'tags' || $field === 'specifications') {
                $value = !empty($data[$field]) ? json_encode($data[$field]) : null;
            } else if ($field === 'name') {
                $value = sanitize_input($data[$field]);
                // Update slug if name changed
                if (isset($data['name'])) {
                    $slug = create_slug($data['name']);
                    $update_data[] = "slug = ?";
                    $values[] = $slug;
                    $types .= 's';
                }
            } else {
                $value = $data[$field];
            }
            
            $values[] = $value;
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }
    }
    
    if (empty($update_data)) {
        json_response(['success' => false, 'message' => 'No data to update'], 400);
    }
    
    // Add updated_at and id to values
    $update_data[] = "updated_at = NOW()";
    $values[] = $id;
    $types .= 'i';
    
    // Build and execute query
    $query = "UPDATE products SET " . implode(', ', $update_data) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        // Log admin action
        $user = $auth->get_current_user();
        log_admin_action($user['id'], 'UPDATE', 'products', $id, [
            'name' => $data['name'] ?? 'Updated'
        ]);
        
        json_response(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to update product'], 500);
    }
}

function handle_delete() {
    global $conn, $auth;
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        json_response(['success' => false, 'message' => 'Product ID required'], 400);
    }
    
    // Check if product exists
    $check_stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        json_response(['success' => false, 'message' => 'Product not found'], 404);
    }
    
    $product = $check_result->fetch_assoc();
    
    // Soft delete (set is_active = 0)
    $stmt = $conn->prepare("UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Log admin action
        $user = $auth->get_current_user();
        log_admin_action($user['id'], 'DELETE', 'products', $id, [
            'name' => $product['name']
        ]);
        
        json_response(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to delete product'], 500);
    }
}

function create_slug($text) {
    $slug = strtolower($text);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM products WHERE slug = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $slug .= '-' . time();
    }
    
    return $slug;
}
?>