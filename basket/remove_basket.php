<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID товара']);
    exit;
}

$product_id = (int)$_POST['product_id'];

if (removeFromBasket($product_id)) {
    echo json_encode([
        'success' => true,
        'message' => 'Товар удален из корзины',
        'basket_count' => getBasketCount(),
        'basket_total' => getBasketTotal()
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при удалении из корзины']);
}
