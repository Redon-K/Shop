<?php
require_once 'config.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
        session_start();
    }
    
    public function login($email, $password, $remember = false) {
        $email = sanitize_input($email);
        
        if ($this->has_exceeded_login_attempts($email)) {
            return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
        }
        
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->record_login_attempt($email, false);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        $user = $result->fetch_assoc();
        
        if (!verify_password($password, $user['password'])) {
            $this->record_login_attempt($email, false);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        if (!($user['is_active'] ?? true)) {
            return ['success' => false, 'message' => 'Account is deactivated. Please contact support.'];
        }
        
        $this->record_login_attempt($email, true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        $_SESSION['login_time'] = time();
        
        if ($remember) {
            $token = $this->generate_remember_token($user['id']);
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
            setcookie('user_id', $user['id'], time() + (30 * 24 * 60 * 60), '/');
        }
        
        if ($user['is_admin']) {
            log_admin_action($user['id'], 'LOGIN', 'users', $user['id'], [
                'email' => $user['email'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'is_admin' => $user['is_admin']
            ]
        ];
    }
    
    public function logout() {
        if (isset($_COOKIE['remember_token'])) {
            $this->clear_remember_token($_SESSION['user_id'] ?? 0);
            setcookie('remember_token', '', time() - 3600, '/');
            setcookie('user_id', '', time() - 3600, '/');
        }
        
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function register($data) {
        $required = ['email', 'password', 'confirm_password', 'first_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Please fill in all required fields"];
            }
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            return ['success' => false, 'message' => 'Passwords do not match'];
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        $email = sanitize_input($data['email']);
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        $password_hash = hash_password($data['password']);
        
        $first_name = sanitize_input($data['first_name']);
        $last_name = sanitize_input($data['last_name'] ?? '');
        $phone = sanitize_input($data['phone'] ?? '');
        $address = sanitize_input($data['address'] ?? '');
        $newsletter = isset($data['newsletter']) ? 1 : 0;
        
        $stmt = $this->conn->prepare("
            INSERT INTO users (email, password, first_name, last_name, phone, street_address, newsletter_subscribed, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("ssssssi", $email, $password_hash, $first_name, $last_name, $phone, $address, $newsletter);
        
        if ($stmt->execute()) {
            $user_id = $this->conn->insert_id;
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['is_admin'] = false;
            $_SESSION['login_time'] = time();
            
            if ($newsletter) {
                $this->subscribe_newsletter($email, $first_name);
            }
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $user_id
            ];
        }
        
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
    
    public function check_auth() {
        if (isset($_SESSION['user_id']) && isset($_SESSION['login_time'])) {
            if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
                $this->logout();
                return false;
            }
            
            $_SESSION['login_time'] = time();
            return true;
        }
        
        if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
            $user_id = (int)$_COOKIE['user_id'];
            $token = $_COOKIE['remember_token'];
            
            if ($this->validate_remember_token($user_id, $token)) {
                // Get user data
                $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['is_admin'] = (bool)$user['is_admin'];
                    $_SESSION['login_time'] = time();
                    
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function is_admin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
    
    public function get_user_data() { 
        if (!$this->check_auth()) {
            return null;
        }
        
        $user_id = $_SESSION['user_id'];
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function update_profile($data) {
        if (!$this->check_auth()) {
            return ['success' => false, 'message' => 'Not authenticated'];
        }
        
        $user_id = $_SESSION['user_id'];
        
        $fields = [];
        $values = [];
        $types = '';
        
        $allowed_fields = ['first_name', 'last_name', 'phone', 'street_address', 'city', 
                          'postal_code', 'state_region', 'country', 'date_of_birth', 
                          'newsletter_subscribed', 'contact_preference', 'avatar_url'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $fields[] = "$key = ?";
                $values[] = sanitize_input($value);
                $types .= 's';
            }
        }
        
        if (empty($fields)) {
            return ['success' => false, 'message' => 'No valid fields to update'];
        }
        
        $values[] = $user_id;
        $types .= 'i';
        
        $query = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            if (isset($data['first_name'])) $_SESSION['first_name'] = $data['first_name'];
            if (isset($data['last_name'])) $_SESSION['last_name'] = $data['last_name'];
            
            return ['success' => true, 'message' => 'Profile updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
    
    private function has_exceeded_login_attempts($email) {
        $key = 'login_attempts_' . md5($email);
        
        if (!isset($_SESSION[$key])) {
            return false;
        }
        
        $attempts = $_SESSION[$key];
        $time = time();
        
        $attempts = array_filter($attempts, function($attempt) use ($time) {
            return $time - $attempt < 900; 
        });
        
        $_SESSION[$key] = $attempts;
        
        return count($attempts) >= MAX_LOGIN_ATTEMPTS;
    }
    
    private function record_login_attempt($email, $success) {
        $key = 'login_attempts_' . md5($email);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        
        if (!$success) {
            $_SESSION[$key][] = time();
        } else {
            unset($_SESSION[$key]);
        }
    }
    
    private function generate_remember_token($user_id) {
        $token = generate_token();
        $hash = hash('sha256', $token);
        
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS user_tokens (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                token_hash VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_token (user_id),
                INDEX idx_token_hash (token_hash),
                INDEX idx_expires_at (expires_at)
            )
        ");
        
        $stmt = $this->conn->prepare("
            INSERT INTO user_tokens (user_id, token_hash, expires_at) 
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
            ON DUPLICATE KEY UPDATE token_hash = ?, expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY)
        ");
        
        $stmt->bind_param("iss", $user_id, $hash, $hash);
        $stmt->execute();
        $stmt->close();
        
        return $token;
    }
    
    private function validate_remember_token($user_id, $token) {
        $hash = hash('sha256', $token);
        
        $stmt = $this->conn->prepare("
            SELECT id FROM user_tokens 
            WHERE user_id = ? AND token_hash = ? AND expires_at > NOW()
        ");
        
        $stmt->bind_param("is", $user_id, $hash);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows === 1;
    }
    
    private function clear_remember_token($user_id) {
        $stmt = $this->conn->prepare("DELETE FROM user_tokens WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    
    private function subscribe_newsletter($email, $name) {
        try {
            $this->conn->query("
                CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    name VARCHAR(255),
                    is_active BOOLEAN DEFAULT TRUE,
                    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    unsubscribed_at TIMESTAMP NULL
                )
            ");
            
            $stmt = $this->conn->prepare("
                INSERT INTO newsletter_subscribers (email, name, subscribed_at) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE is_active = 1, unsubscribed_at = NULL
            ");
            
            $stmt->bind_param("ss", $email, $name);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Newsletter subscription error: " . $e->getMessage());
        }
    }
}

$auth = new Auth();

function is_user_logged_in() {
    global $auth;
    return $auth->check_auth();
}

function is_user_admin() {
    global $auth;
    return $auth->is_admin();
}

function get_user_data() { 
    global $auth;
    return $auth->get_user_data();
}

function require_user_login() {
    if (!is_user_logged_in()) {
        redirect('../HTML/login.php');
    }
}

function require_admin_access() {
    if (!is_user_admin()) {
        redirect('../HTML/login.php');
    }
}
?>