<?php
/**
 * Базовая конфигурация проекта "Волшебная ЛАВКА"
 */

// Загрузка переменных окружения из файла .env
$env_file = dirname(__DIR__) . '/includes/config/.env';
if (file_exists($env_file)) {
    $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

$host = getenv('DB_HOST') ?: '';
$db_name = getenv('DB_NAME') ?: '';
$db_user = getenv('DB_USER') ?: '';
$db_pass = getenv('DB_PASS') ?: '';
$app_env = getenv('APP_ENV') ?: '';

// Проверка наличия пароля БД
if (empty($db_pass)) {
    die('Error: Database password is not set. Please configure .env file.');
}

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 600);
session_start();

// 1. Генерируем токен, если его нет
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF проверка теперь выполняется индивидуально в каждой форме через csrf_verify()

function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_verify() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }

    $csrf_validation_passed = !empty($_POST['csrf_token'])
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);

    // Обновляем токен после успешной проверки
    if ($csrf_validation_passed) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $csrf_validation_passed;
}

error_reporting(E_ALL);

// Отключаем отображение ошибок в production режиме
if ($app_env === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    ini_set('display_errors', 1);
}

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

// Константа для интервала регенерации сессии (30 минут)
define('SESSION_REGENERATION_INTERVAL', 1800);

if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > SESSION_REGENERATION_INTERVAL) {
    regenerateSessionId();
}
