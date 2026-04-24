<?php
// Database configuration
$host = 'localhost';
$db_name = 'shop_db';
$db_user = 'root';
$db_pass = 'Andronpv13';

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 600); // 10 minutes
session_start();

// CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
        die('CSRF validation failed');
    }
}
// Generate new CSRF token for each form
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create database connection
$conn = new mysqli($host, $db_user, $db_pass, $db_name);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        header('Location: /login.php');
        exit();
    }
}

function sanitize($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// Regenerate session ID every 30 minutes
if (!isset($_SESSION['last_regeneration'])) {
    regenerateSessionId();
} else if (time() - $_SESSION['last_regeneration'] > 1800) {
    regenerateSessionId();
}

function regenerateSessionId() {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
