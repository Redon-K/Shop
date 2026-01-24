<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'apex_fuel');
define('BASE_URL', 'http://localhost/apex_fuel');

// File upload configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Site configuration
define('SITE_NAME', 'Apex Fuel');
define('SITE_EMAIL', 'info@apexfuel.com');
define('CURRENCY', '$');
define('TAX_RATE', 0.07); // 7%
define('SHIPPING_COST', 4.99);

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('PEPPER', 'apex_fuel_secret_key_change_in_production');

// Create database connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection error. Please try again later.");
        }
    }
    
    return $conn;
}

// Utility functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function hash_password($password) {
    return password_hash($password . PEPPER, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password . PEPPER, $hash);
}

function generate_random_token($length = 32) {  
    return bin2hex(random_bytes($length));
}

function format_price_amount($price) {  
    return CURRENCY . number_format($price, 2);
}

function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function send_json_response($data, $status = 200) {  
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function redirect_to($url) {  
    header("Location: " . $url);
    exit;
}

function handle_file_upload($file, $type = 'image') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File is too large. Maximum size: 5MB');
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($type === 'image' && !in_array($file_ext, ALLOWED_IMAGE_TYPES)) {
        throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed');
    }
    
    if (!file_exists(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
    $upload_path = UPLOAD_PATH . $new_filename;
    
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    return '../uploads/' . $new_filename;
}

function log_admin_action($admin_id, $action, $table = null, $record_id = null, $details = null) {
    $conn = getDBConnection();
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $details_json = $details ? json_encode($details) : null;
    
    $conn->query("
        CREATE TABLE IF NOT EXISTS admin_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            admin_id INT,
            action VARCHAR(100) NOT NULL,
            table_name VARCHAR(50),
            record_id INT,
            details JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_action (action),
            INDEX idx_admin (admin_id)
        )
    ");
    
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, table_name, record_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississs", $admin_id, $action, $table, $record_id, $details_json, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}
?>