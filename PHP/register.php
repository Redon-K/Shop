<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once 'config.php';

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// If JSON decode fails, try form data
if ($data === null && !empty($_POST)) {
    $data = $_POST;
}

// Validate that we have data
if (empty($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No data provided']);
    exit;
}

// Parse full name into first and last name
$fullname = isset($data['fullname']) ? trim($data['fullname']) : '';
$name_parts = explode(' ', $fullname, 2);
$first_name = isset($name_parts[0]) ? trim($name_parts[0]) : '';
$last_name = isset($name_parts[1]) ? trim($name_parts[1]) : '';

// Prepare registration data
$register_data = [
    'email' => $data['email'] ?? '',
    'password' => $data['password'] ?? '',
    'confirm_password' => $data['confirm'] ?? '',
    'first_name' => $first_name,
    'last_name' => $last_name,
    'phone' => $data['phone'] ?? '',
    'street' => $data['street'] ?? '',
    'city' => $data['city'] ?? '',
    'postal' => $data['postal'] ?? '',
    'region' => $data['region'] ?? '',
    'country' => $data['country'] ?? '',
    'newsletter' => isset($data['subscribe']) && $data['subscribe'] ? 1 : 0
];

// Call the register_user function
$result = register_user($register_data);

// Set appropriate HTTP status code
if ($result['success']) {
    http_response_code(201);
} else {
    http_response_code(400);
}

echo json_encode($result);
exit;

/**
 * Register a new user
 */
function register_user($data) {
    $conn = getDBConnection();
    
    // Validate required fields
    $required_fields = ['email', 'password', 'confirm_password', 'first_name'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => 'Please fill in all required fields'];
        }
    }
    
    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address'];
    }
    
    // Validate password match
    if ($data['password'] !== $data['confirm_password']) {
        return ['success' => false, 'message' => 'Passwords do not match'];
    }
    
    // Validate password length
    if (strlen($data['password']) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }
    
    // Check if email already exists
    $email = sanitize_input($data['email']);
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error. Please try again.'];
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'This email is already registered'];
    }
    
    // Hash password
    $password_hash = hash_password($data['password']);
    
    // Sanitize inputs
    $first_name = sanitize_input($data['first_name']);
    $last_name = sanitize_input($data['last_name'] ?? '');
    $phone = sanitize_input($data['phone'] ?? '');
    $street = sanitize_input($data['street'] ?? '');
    $city = sanitize_input($data['city'] ?? '');
    $postal = sanitize_input($data['postal'] ?? '');
    $region = sanitize_input($data['region'] ?? '');
    $country = sanitize_input($data['country'] ?? '');
    $newsletter = isset($data['newsletter']) ? (int)$data['newsletter'] : 0;
    
    // Insert user into database
    $stmt = $conn->prepare("
        INSERT INTO users (first_name, last_name, email, password, phone, street_address, city, postal_code, state_region, country, newsletter_subscribed, is_admin, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error. Please try again.'];
    }
    
    $stmt->bind_param("ssssssssssi", $first_name, $last_name, $email, $password_hash, $phone, $street, $city, $postal, $region, $country, $newsletter);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        $stmt->close();
        
        // Auto login after registration
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['is_admin'] = false;
        $_SESSION['login_time'] = time();
        
        return [
            'success' => true,
            'message' => 'Registration successful!',
            'user_id' => $user_id
        ];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

?>

