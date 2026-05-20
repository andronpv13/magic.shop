<?php
/**
 * magic.shop — Конфигурация и инициализация
 * Подключается в начале каждого публичного скрипта
 */

// 🔒 Запрет прямого вызова
if (strpos($_SERVER['PHP_SELF'], 'config.php') !== false) {
    die('Direct access not allowed');
}

// 📁 Пути
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CONFIG_PATH', INCLUDES_PATH . '/config');

// 📦 Загрузка переменных окружения из .env (простой парсер)
$env_file = CONFIG_PATH . '/.env';
if (file_exists($env_file)) {
    $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " '\"");
        if (!defined($key)) {
            define($key, $value);
        }
    }
}

// 🗄️ Настройки БД (с дефолтами)
$DB_HOST = defined('DB_HOST') ? DB_HOST : 'localhost';
$DB_NAME = defined('DB_NAME') ? DB_NAME : 'magic_shop';
$DB_USER = defined('DB_USER') ? DB_USER : 'root';
$DB_PASS = defined('DB_PASS') ? DB_PASS : '';
$DB_CHARSET = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

// 🔐 Настройки сессий
$SESSION_LIFETIME = defined('SESSION_LIFETIME') ? (int)SESSION_LIFETIME : 1800; // 30 минут
$SESSION_SECURE = defined('SESSION_SECURE') ? (bool)SESSION_SECURE : false; // true для HTTPS
$SESSION_HTTPONLY = defined('SESSION_HTTPONLY') ? (bool)SESSION_HTTPONLY : true;
$SESSION_SAMESITE = defined('SESSION_SAMESITE') ? SESSION_SAMESITE : 'Strict';

// 🧩 Инициализация сессии
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', $SESSION_HTTPONLY ? '1' : '0');
    ini_set('session.cookie_secure', $SESSION_SECURE ? '1' : '0');
    ini_set('session.cookie_samesite', $SESSION_SAMESITE);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', $SESSION_LIFETIME);
    
    session_start();
    
    // Регенерация ID сессии каждые 30 минут для защиты от fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > $SESSION_LIFETIME / 2) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// 🌐 Таймзона
date_default_timezone_set(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Europe/Moscow');

// 🛡️ CSRF-токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Проверка CSRF-токена
 */
function verify_csrf($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Генерация CSRF-поля для формы
 */
function csrf_field() {
    $token = $_SESSION['csrf_token'] ?? '';
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// 🔗 Подключение к БД (глобальное, с обработкой ошибок)
$conn = null;
try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("DB connect error: " . $conn->connect_error);
    }
    $conn->set_charset($DB_CHARSET);
    
    // Настройки сессии в БД (опционально)
    // $conn->query("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE'");
    
} catch (Exception $e) {
    error_log("[FATAL] Database connection: " . $e->getMessage());
    // В продакшене — показать 503, не раскрывать детали
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die("Ошибка подключения к базе данных");
    } else {
        http_response_code(503);
        die("Сервис временно недоступен");
    }
}

// 📚 Подключение хелперов и функций
require_once INCLUDES_PATH . '/functions.php';
// Дополнительные функции по ролям (подключаются при необходимости)
// require_once INCLUDES_PATH . '/functions_adm.php';
// require_once INCLUDES_PATH . '/functions_md.php';

// 🧹 Авто-очистка старых сессий (1% шанс при каждом запросе)
if (random_int(1, 100) === 1) {
    $old = time() - $SESSION_LIFETIME * 2;
    // Если сессии хранятся в файлах:
    if (ini_get('session.save_handler') === 'files') {
        $save_path = session_save_path();
        foreach (glob("{$save_path}/sess_*") as $file) {
            if (filemtime($file) < $old) @unlink($file);
        }
    }
}
?>