<?php
/**
 * AJAX API: Удаление товара из корзины
 * Разработчик: АВВА © 2025
 */

// Запускаем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

// Проверка CSRF токена
if (!isset($_POST['csrf_token']) || !csrf_verify()) {
    echo json_encode(['success' => false, 'message' => 'Ошибка безопасности. Обновите страницу.']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Некорректный ID товара']);
    exit;
}

$result = removeFromCart($product_id);
echo json_encode($result);
