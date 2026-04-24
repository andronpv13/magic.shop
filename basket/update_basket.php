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

if (updateBasket($product_id, $quantity)) {
    $item = null;
    foreach ($_SESSION['basket'] as $basket_item) {
        if ($basket_item['id'] == $product_id) {
            $item = $basket_item;
            break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Количество обновлено',
        'basket_count' => getBasketCount(),
        'item_total' => $item['price'] * $quantity,
        'basket_total' => getBasketTotal()
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении корзины']);
}
