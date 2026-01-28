<?php
session_start();

session_destroy();

if(isset($_COOKIE['user'])){
    setcookie('user', "", time() - 3600);
}

if(isset($_COOKIE['PHPSESSID'])){
    setcookie('PHPSESSID', "", time() - 3600);
}

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    exit();
} else {
    header("Location: ../HTML/login.php");
    exit();
}
?>