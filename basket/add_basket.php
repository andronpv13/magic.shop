<?php
/**
 * magic.shop — API: Добавить товар в корзину
 * Метод: POST, формат: JSON
 */

// 🔒 Только для внутренних вызовов
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// 🔗 Подключение ядра
require_once __DIR__ . '/../includes/config.php';

// 📦 Ответ по умолчанию
$response = ['success' => false];

try {
    // ✅ Проверка авторизации
    if (!isLoggedIn()) {
        // ✅ Исправлено: динамический URL для редиректа
        $response['redirect'] = site_url('login.php');
        $response['error'] = 'Требуется авторизация';
        echo json_encode($response);
        exit;
    }
    
    // ✅ Проверка CSRF для AJAX (опционально, но рекомендуется)
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf($csrf)) {
        throw new Exception('CSRF validation failed');
    }
    
    // 📥 Получение и валидация данных
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    
    if ($product_id <= 0) {
        throw new Exception('Некорректный ID товара');
    }
    
    // 🔍 Проверка существования и доступности товара
    $stmt = $conn->prepare("SELECT id, name, price, stock, active FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$product || !$product['active']) {
        throw new Exception('Товар не найден или недоступен');
    }
    
    // 📦 Проверка остатков
    if ($product['stock'] < $quantity) {
        $response['error'] = "Доступно только {$product['stock']} шт.";
        $response['max_quantity'] = $product['stock'];
        echo json_encode($response);
        exit;
    }
    
    // ➕ Добавление в корзину
    if (addToBasket($product_id, $quantity)) {
        // 🔄 Пересчёт корзины для ответа
        $items = getBasketItems($conn);
        $response = [
            'success' => true,
            'message' => 'Товар добавлен в корзину',
            'item' => [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'subtotal' => $product['price'] * $quantity,
                'image' => $product['image'] ? site_url("images/product/{$product['image']}") : site_url('images/no_photo.png')
            ],
            'basket' => [
                'count' => array_sum(array_column($items, 'quantity')),
                'total' => getBasketTotal($items)
            ]
        ];
        log_action($_SESSION['user_id'], 'basket_add', "Товар #{$product_id} x{$quantity}", $log_file);
    } else {
        throw new Exception('Не удалось добавить товар');
    }
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    log_error("basket/add_basket: " . $e->getMessage(), 'ERROR', [
        'user_id' => $_SESSION['user_id'] ?? null,
        'product_id' => $product_id ?? null
    ]);
    http_response_code(400);
}

// 📤 Отправка ответа
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>