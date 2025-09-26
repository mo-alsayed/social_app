<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'social_media_app');

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser()
{
    global $pdo;
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

function getSafeImage($path, $default = 'default_profile.jpg')
{
    if (file_exists($path)) {
        return $path;
    }
    // Return default based on type
    if (strpos($path, 'covers') !== false) {
        return 'assets/images/covers/default_cover.jpg';
    }
    return 'assets/images/profiles/default_profile.jpg';
}
function validateImage($file)
{
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Check if file is an actual image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'error' => 'File is not an image.'];
    }

    // Check MIME type
    if (!in_array($check['mime'], $allowed_types)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, and GIF files are allowed.'];
    }

    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File size must be less than 5MB.'];
    }

    return ['success' => true];
}
?>