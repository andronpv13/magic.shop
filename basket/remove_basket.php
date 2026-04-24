<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Проверяем CSRF токен
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Ошибка безопасности: неверный CSRF токен']);
    exit;
}

if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID товара']);
    exit;
}

$product_id = (int)$_POST['product_id'];

if (removeFromBasket($product_id)) {
    // Генерируем новый CSRF токен после успешного удаления
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    echo json_encode([
        'success' => true,
        'message' => 'Товар удален из корзины',
        'basket_count' => getBasketCount(),
        'basket_total' => getBasketTotal(),
        'csrf_token' => $_SESSION['csrf_token']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при удалении из корзины']);
}
