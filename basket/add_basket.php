<?php
/**
 * AJAX API: Добавление товара в корзину
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
    echo json_encode(['success' => false, 'message' => 'Ошибка безопасности: истек срок действия сессии. Обновите страницу.']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Некорректный ID товара']);
    exit;
}

$result = addToCart($product_id, $quantity);
echo json_encode($result);
