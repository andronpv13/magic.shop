<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

// Проверка CSRF токена для AJAX запросов
if (!csrf_verify_ajax()) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF validation failed']);
    exit;
}

$type = $_POST['type'] ?? $_GET['type'] ?? '';
$value = trim($_POST['value'] ?? $_GET['value'] ?? '');

// Whitelist допустимых значений для параметра type
$allowed_types = ['username', 'email'];
if (!in_array($type, $allowed_types)) {
    echo json_encode(['error' => 'Invalid type parameter']);
    exit;
}

if (empty($value)) {
    echo json_encode(['exists' => false]);
    exit;
}

global $conn;
$current_id = $_SESSION['user_id'] ?? 0;
$field = ($type === 'username') ? 'username' : 'email';

$stmt = $conn->prepare("SELECT id FROM users WHERE $field = ? AND id != ?");
$stmt->bind_param("si", $value, $current_id);
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;

// Возвращаем available: true если пользователь НЕ существует (логин свободен)
echo json_encode([
    'exists' => $exists,
    'available' => !$exists
]);
