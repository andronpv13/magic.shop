<?php
/**
 * Базовая конфигурация проекта "Волшебная ЛАВКА"
 * Версия 2.0 - Исправления безопасности и оптимизации
 */

// Константы конфигурации (вынесены из hardcoded значений)
define('MIN_PASSWORD_LENGTH', 6);
define('MAX_USERNAME_LENGTH', 50);
define('MAX_EMAIL_LENGTH', 100);
define('MAX_STRING_LENGTH', 255);
define('SESSION_REGENERATION_INTERVAL', 1800); // 30 минут
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900); // 15 минут
define('LOG_DIR', __DIR__ . '/../logs');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Создание директории для логов если не существует
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

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
$app_env = getenv('APP_ENV') ?: 'development';

// Проверка наличия пароля БД
if (empty($db_pass)) {
    log_error('Critical: Database password is not set');
    die('Error: Database password is not set. Please configure .env file.');
}

// Настройка сессии ДО session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 600);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

// Настройка логирования ошибок
if ($app_env === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_DIR . '/php_errors.log');
} else {
    ini_set('display_errors', 1);
}

error_reporting(E_ALL);

set_error_handler(function($severity, $message, $file, $line) {
    log_error("[$severity] $message in $file on line $line");
    throw new ErrorException($message, 0, $severity, $file, $line);
});

session_start();

// Регенерация session_id каждые 30 минут
if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > SESSION_REGENERATION_INTERVAL) {
    regenerateSessionId();
}

// Генерация CSRF токена если его нет
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting - проверка количества попыток
function checkRateLimit($action, $max_attempts = MAX_LOGIN_ATTEMPTS, $window = LOGIN_ATTEMPT_WINDOW) {
    $key = 'rate_limit_' . $action;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $identifier = $key . '_' . md5($ip);

    if (!isset($_SESSION[$identifier])) {
        $_SESSION[$identifier] = ['count' => 0, 'reset_time' => time() + $window];
    }

    if (time() > $_SESSION[$identifier]['reset_time']) {
        $_SESSION[$identifier] = ['count' => 0, 'reset_time' => time() + $window];
    }

    $_SESSION[$identifier]['count']++;

    if ($_SESSION[$identifier]['count'] > $max_attempts) {
        log_error("Rate limit exceeded for $action from IP: $ip");
        return false;
    }

    return true;
}

function incrementLoginAttempt() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + LOGIN_ATTEMPT_WINDOW];
    }

    if (time() > $_SESSION[$key]['reset_time']) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + LOGIN_ATTEMPT_WINDOW];
    }

    $_SESSION[$key]['count']++;
    log_error("Failed login attempt {$_SESSION[$key]['count']} from IP: $ip");
}

function resetLoginAttempts() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    unset($_SESSION[$key]);
}

function checkLoginAttempts() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        return true;
    }

    if (time() > $_SESSION[$key]['reset_time']) {
        unset($_SESSION[$key]);
        return true;
    }

    return $_SESSION[$key]['count'] < MAX_LOGIN_ATTEMPTS;
}

// Логирование ошибок
function log_error($message, $level = 'ERROR') {
    $log_file = LOG_DIR . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? 'guest';
    $log_entry = "[$timestamp] [$level] [IP:$ip] [User:$user_id] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Логирование действий пользователя
function log_action($action, $details = []) {
    $message = $action . ': ' . json_encode($details);
    log_error($message, 'ACTION');
}

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
    } else {
        log_error('CSRF validation failed');
    }

    return $csrf_validation_passed;
}

// CSRF проверка для AJAX запросов
function csrf_verify_ajax() {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        log_error('AJAX CSRF validation failed');
        return false;
    }
    // Обновляем токен после успешной проверки
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return true;
}

// Алиасы для совместимости с другими именами функций
function verifyCsrfToken($token) {
    return !empty($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') . '">';
}

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    log_error('Database connection failed: ' . $conn->connect_error, 'CRITICAL');
    die("Connection failed. Please try again later.");
}
$conn->set_charset('utf8mb4');

// Включение подготовленных выражений по умолчанию
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        log_action('Unauthorized access attempt', ['required_role' => $role, 'current_role' => $_SESSION['role'] ?? 'none']);
        header('Location: /login.php');
        exit();
    }
}

function requireAdmin() { requireRole('admin'); }

function requireModerator() {
    if (!isLoggedIn() || !in_array($_SESSION['role'], ['moderator', 'admin'])) {
        log_action('Unauthorized moderator access attempt', ['current_role' => $_SESSION['role'] ?? 'none']);
        header('Location: /login.php');
        exit();
    }
}

function sanitize($input) {
    return htmlspecialchars($input ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function e($str) {
    return sanitize($str);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function regenerateSessionId() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Валидация email с проверкой домена (упрощённая для production)
function validateEmail($email) {
    if (empty($email) || strlen($email) > MAX_EMAIL_LENGTH) {
        return false;
    }

    // Нормализация email
    $email = filter_var(strtolower(trim($email)), FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Проверка существования домена через DNS (только для production и только как дополнительная проверка)
    // В development или при отсутствии DNS записей - разрешаем email с корректным форматом
    $domain = substr(strrchr($email, "@"), 1);

    // Проверяем наличие MX или A записей
    if (checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A')) {
        return true;
    }

    // Если DNS проверка не прошла, просто логируем предупреждение и разрешаем email
    // Это нужно для:
    // 1. Локальных доменов (.local, .test и т.д.)
    // 2. Email без публичных DNS записей (корпоративные домены)
    // 3. Development окружений без доступа к DNS
    log_error("Email domain without DNS records (allowed): $domain", 'INFO');
    return true;
}

// Полная валидация данных профиля
function validateProfileData($data) {
    $errors = [];

    if (empty($data['username']) || strlen($data['username']) > MAX_USERNAME_LENGTH) {
        $errors[] = 'Логин должен быть от 1 до ' . MAX_USERNAME_LENGTH . ' символов';
    }

    if (!validateEmail($data['email'] ?? '')) {
        $errors[] = 'Некорректный Email';
    }

    $string_fields = ['first_name', 'last_name', 'middle_name', 'phone'];
    foreach ($string_fields as $field) {
        if (!empty($data[$field]) && strlen($data[$field]) > MAX_STRING_LENGTH) {
            $errors[] = "Поле $field слишком длинное";
        }
    }

    $address_fields = ['zip_code', 'region', 'city', 'street', 'house', 'apartment'];
    foreach ($address_fields as $field) {
        if (!empty($data[$field]) && strlen($data[$field]) > 100) {
            $errors[] = "Поле адреса $field слишком длинное";
        }
    }

    return $errors;
}

// Безопасное получение order_id с проверкой доступа
function getSecureOrderId($order_id, $user_id = null) {
    global $conn;

    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? 0;
    }

    if (!$user_id) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        log_error("Unauthorized order access attempt: order_id=$order_id by user_id=$user_id");
        return null;
    }

    return $order_id;
}
?>
