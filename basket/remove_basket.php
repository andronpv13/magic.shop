<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// CSRF Protection: Явная проверка токена для AJAX-запросов
if (!csrf_verify_ajax()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF Token']);
    exit;
}

if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID товара']);
    exit;
}

$product_id = (int)$_POST['product_id'];

if (removeFromBasket($product_id)) {
    echo json_encode([
        'success' => true,
        'message' => 'Товар удалён из корзины',
        'basket_count' => getBasketCount(),
        'basket_total' => getBasketTotal(),
        'csrf_token' => $_SESSION['csrf_token']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при удалении из корзины']);
}