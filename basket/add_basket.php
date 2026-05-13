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

if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Не указаны необходимые параметры']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];

if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Неверное количество']);
    exit;
}

if (addToBasket($product_id, $quantity)) {
    echo json_encode([
        'success' => true,
        'message' => 'Товар добавлен в корзину',
        'basket_count' => getBasketCount(),
        'csrf_token' => $_SESSION['csrf_token']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при добавлении в корзину']);
}