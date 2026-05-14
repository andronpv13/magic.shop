<?php
require_once __DIR__ . '/../includes/config.php';

// Проверка прав администратора
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/manage_orders.php');
    exit;
}

// Проверка CSRF токена с использованием csrf_verify()
if (!csrf_verify()) {
    log_action('CSRF violation in order status update', ['user_id' => $_SESSION['user_id'] ?? null]);
    header('Location: /admin/manage_orders.php?error=csrf');
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Валидация статуса
$valid_statuses = ['pending', 'payment', 'completed', 'cancelled'];
if (!$order_id || !in_array($status, $valid_statuses)) {
    header('Location: /admin/manage_orders.php?error=invalid_data');
    exit;
}

// Обновление статуса заказа
$result = updateOrderStatus($order_id, $status);

if ($result['success']) {
    log_action('Order status updated by admin', [
        'order_id' => $order_id,
        'new_status' => $status,
        'admin_id' => $_SESSION['user_id']
    ]);
    header('Location: /admin/order_details.php?order_id=' . $order_id . '&success=1');
} else {
    header('Location: /admin/order_details.php?order_id=' . $order_id . '&error=1');
}
exit;
?>
