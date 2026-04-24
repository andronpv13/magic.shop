<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

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

// Проверяем CSRF токен
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Ошибка безопасности: неверный CSRF токен']);
    exit;
}

if (addToBasket($product_id, $quantity)) {
    echo json_encode([
        'success' => true,
        'message' => 'Товар добавлен в корзину',
        'basket_count' => getBasketCount()
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при добавлении в корзину']);
}
