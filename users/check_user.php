<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

$type = $_POST['type'] ?? $_GET['type'] ?? '';
$value = trim($_POST['value'] ?? $_GET['value'] ?? '');

// Whitelist допустимых значений для параметра type
$allowed_types = ['username', 'email', 'current_password'];
if (!in_array($type, $allowed_types)) {
    echo json_encode(['error' => 'Invalid type parameter']);
    exit;
}

if (empty($value) && $type !== 'current_password') {
    echo json_encode(['exists' => false]);
    exit;
}

global $conn;
$current_id = $_SESSION['user_id'] ?? 0;

// Обработка валидации текущего пароля
if ($type === 'current_password') {
    if (!isLoggedIn()) {
        echo json_encode(['valid' => false, 'error' => 'User not logged in']);
        exit;
    }

    // Получаем хеш пароля текущего пользователя
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $current_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        echo json_encode(['valid' => false, 'error' => 'User not found']);
        exit;
    }

    // Проверяем введённый пароль с хешем в базе
    $isValid = password_verify($value, $result['password']);
    echo json_encode(['valid' => $isValid]);
    exit;
}

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