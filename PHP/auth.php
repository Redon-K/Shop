<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function require_admin() {
    if (!is_admin()) {
        header("Location: ../HTML/login.php");
        exit();
    }
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: ../HTML/login.php");
        exit();
    }
}

if (!function_exists('get_current_user')) {
    function get_current_user() {
        if (!is_logged_in()) return null;
        
        global $conn;
        $user_id = $_SESSION['user_id'];
        $result = $conn->query("SELECT * FROM users WHERE id = $user_id");
        return $result->fetch_assoc();
    }
}

function login_user($email, $password) {
    global $conn;
    
    $email = sanitize_input($email);
    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    $user = $result->fetch_assoc();
    
    if (!verify_password($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['is_admin'] = $user['is_admin'];
    $_SESSION['login_time'] = time();
    
    return ['success' => true, 'message' => 'Login successful', 'user' => $user];
}

function logout_user() {
    session_destroy();
    if (isset($_COOKIE['user'])) {
        setcookie('user', '', time() - 3600, '/');
    }
    return true;
}

function register_user($data) {
    global $conn;
    
    $required = ['email', 'password', 'confirm', 'first_name'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Field '$field' is required"];
        }
    }
    
    if ($data['password'] !== $data['confirm']) {
        return ['success' => false, 'message' => 'Passwords do not match'];
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    $email = sanitize_input($data['email']);
    $result = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    $password_hash = hash_password($data['password']);
    
    $email = sanitize_input($data['email']);
    $first_name = sanitize_input($data['first_name']);
    $last_name = sanitize_input($data['last_name'] ?? '');
    $phone = sanitize_input($data['phone'] ?? '');
    $street = sanitize_input($data['street'] ?? '');
    $city = sanitize_input($data['city'] ?? '');
    $postal = sanitize_input($data['postal'] ?? '');
    $region = sanitize_input($data['region'] ?? '');
    
    $query = "INSERT INTO users (email, password, first_name, last_name, phone, street_address, city, postal_code, state_region) 
              VALUES ('$email', '$password_hash', '$first_name', '$last_name', '$phone', '$street', '$city', '$postal', '$region')";
    
    if ($conn->query($query)) {
        $user_id = $conn->insert_id;
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email'] = $email;
        $_SESSION['is_admin'] = false;
        $_SESSION['login_time'] = time();
        
        return ['success' => true, 'message' => 'Registration successful', 'user_id' => $user_id];
    }
    
    return ['success' => false, 'message' => 'Registration failed. Please try again.'];
}
?>
