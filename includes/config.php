<?php
/**
 * Базовая конфигурация проекта "Волшебная ЛАВКА"
 */
$host = 'localhost';
$db_name = 'shop_db';
$db_user = 'root';
$db_pass = 'Andronpv13'; // Измените на ваш пароль

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 600);
session_start();

// 1. Генерируем токен, если его нет
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Проверяем токен для POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
        http_response_code(403);
        die('CSRF validation failed');
    }
    // Обновляем токен ПОСЛЕ успешной проверки
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

error_reporting(E_ALL);
ini_set('display_errors', 1); // На продакшене поставить 0

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

function isLoggedIn() { return isset($_SESSION['user_id']) && isset($_SESSION['role']); }

function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        header('Location: /login.php'); exit();
    }
}
function requireAdmin() { requireRole('admin'); }
function requireModerator() {
    if (!isLoggedIn() || !in_array($_SESSION['role'], ['moderator', 'admin'])) {
        header('Location: /login.php'); exit();
    }
}
function sanitize($input) { return htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); }
function e($str) { return sanitize($str); }
function redirect($url) { header("Location: $url"); exit(); }

function regenerateSessionId() {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 1800) {
    regenerateSessionId();
}