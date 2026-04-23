<?php
/**
 * Проверка уникальности логина и email (AJAX)
 * Разработчик: АВВА © 2025
 */

// 1. Настройка ошибок (отключаем HTML-вывод, чтобы не ломать JSON)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 2. Подключение конфигурации
// Используем __DIR__ для надежности путей
$config_path = __DIR__ . '/../includes/config.php';

if (!file_exists($config_path)) {
    // Если файла нет, возвращаем JSON с ошибкой
    header('Content-Type: application/json');
    echo json_encode(['valid' => true, 'debug' => 'Config file not found at: ' . $config_path]);
    exit;
}

require_once $config_path;

// 3. Проверка констант
if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
    header('Content-Type: application/json');
    echo json_encode(['valid' => true, 'debug' => 'DB Constants are not defined in config.php']);
    exit;
}

// 4. Основной блок выполнения в try-catch
try {
    header('Content-Type: application/json');

    // Получаем параметры
    $type = $_GET['type'] ?? ''; // 'username' или 'email'
    $value = trim($_GET['value'] ?? '');

    $response = ['valid' => true, 'message' => ''];

    if (empty($value)) {
        echo json_encode($response);
        exit;
    }

    // Подключение к БД
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");

    // Текущий ID пользователя (исключаем себя при проверке)
    $current_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    if ($type === 'username') {
        // Проверка логина
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        if (!$stmt) {
            throw new Exception("Prepare failed (username): " . $mysqli->error);
        }
        $stmt->bind_param("si", $value, $current_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $response = ['valid' => false, 'message' => 'Логин занят, введите другой'];
        }
        $stmt->close();
        
    } elseif ($type === 'email') {
        // Проверка email
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        if (!$stmt) {
            throw new Exception("Prepare failed (email): " . $mysqli->error);
        }
        $stmt->bind_param("si", $value, $current_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $response = ['valid' => false, 'message' => 'Данный email зарегистрирован, введите незарегистрированный email'];
        }
        $stmt->close();
    }

    $mysqli->close();

    // Вывод успешного JSON
    echo json_encode($response);

} catch (Exception $e) {
    // Ловим любую ошибку и выводим её в debug
    header('Content-Type: application/json');
    echo json_encode([
        'valid' => true, // Разрешаем ввод, чтобы не блокировать пользователя при ошибке сервера
        'debug' => 'Server Error: ' . $e->getMessage()
    ]);
}
?>
