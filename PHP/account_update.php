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
    
    $first_name = sanitize_input($data['firstName'] ?? '');
    $last_name = sanitize_input($data['lastName'] ?? '');
    $email = sanitize_input($data['email'] ?? '');
    $phone = sanitize_input($data['phone'] ?? '');
    $street_address = sanitize_input($data['address'] ?? '');
    $city = sanitize_input($data['city'] ?? '');
    $postal_code = sanitize_input($data['postalCode'] ?? '');
    $state_region = sanitize_input($data['stateRegion'] ?? '');
    $country = sanitize_input($data['country'] ?? '');
    $date_of_birth = !empty($data['dob']) ? sanitize_input($data['dob']) : NULL;
    $newsletter_subscribed = isset($data['newsletter']) ? (int)(bool)$data['newsletter'] : 0;
    $contact_preference = in_array($data['contactPref'] ?? 'email', ['email', 'phone']) ? $data['contactPref'] : 'email';
    
    if ($email) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email already in use']);
            exit();
        }
        $stmt->close();
    }
    
    $stmt = $conn->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?, email = ?, phone = ?, 
            street_address = ?, city = ?, postal_code = ?, state_region = ?, 
            country = ?, date_of_birth = ?, newsletter_subscribed = ?, 
            contact_preference = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param(
        "ssssssssssssi",
        $first_name, $last_name, $email, $phone,
        $street_address, $city, $postal_code, $state_region,
        $country, $date_of_birth, $newsletter_subscribed, $contact_preference, $user_id
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile saved']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error saving profile']);
    }
    $stmt->close();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("
        SELECT id, email, first_name, last_name, phone, street_address, 
               city, postal_code, state_region, country, date_of_birth, 
               newsletter_subscribed, avatar_url, contact_preference
        FROM users WHERE id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode(['success' => true, 'user' => $user]);
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
$conn->close();
?>
