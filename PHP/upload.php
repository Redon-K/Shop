<?php
require_once 'config.php';
require_once 'auth.php';

$auth = new Auth();

// Check authentication
if (!$auth->check_auth()) {
    json_response(['success' => false, 'message' => 'Authentication required'], 401);
}

// Check if admin
if (!$auth->is_admin()) {
    json_response(['success' => false, 'message' => 'Admin access required'], 403);
}

// Check if file was uploaded
if (!isset($_FILES['file'])) {
    json_response(['success' => false, 'message' => 'No file uploaded'], 400);
}

$file = $_FILES['file'];

try {
    // Handle file upload
    $file_path = handle_file_upload($file);
    
    json_response([
        'success' => true,
        'message' => 'File uploaded successfully',
        'file_path' => $file_path,
        'file_name' => basename($file_path)
    ]);
} catch (Exception $e) {
    json_response([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
}
?>