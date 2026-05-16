<?php
$page_title = 'Детали заказа';
require_once __DIR__ . '/../includes/header.php';

// ✅ ИСПРАВЛЕНО: Добавлена проверка прав администратора
requireAdmin();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$order_id) { header('Location: /admin/manage_orders.php'); exit; }

// ✅ ИСПРАВЛЕНО: Используется функция для администратора вместо пользовательской
$order = getOrderDetailsAdmin($order_id);
if (!$order) { echo '<div class="container section"><p class="empty-state">Заказ не найден</p></div>'; require_once __DIR__ . '/../includes/footer.php'; exit; }
?>
<section class="section"><div class="container">
    <nav class="breadcrumbs"><a href="/admin/manage_orders.php">Управление заказами</a><span class="separator">/</span><span class="current">Заказ #<?php echo $order_id; ?></span></nav>
    <h1 class="page-title">Заказ #<?php echo $order_id; ?></h1>
    <div class="order-detail-layout">
        <div class="order-info-section">
            <h2>Информация о заказе</h2>
            <div class="order-details">
                <div class="detail-row"><span>Покупатель:</span><span><?php echo e($order['user_id']); ?></span></div>
                <div class="detail-row"><span>Дата создания:</span><span><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span></div>
                <div class="detail-row"><span>Статус:</span><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo getOrderStatusName($order['status']); ?></span></div>
                <div class="detail-row"><span>Сумма:</span><span class="order-total"><?php echo formatPrice($order['total']); ?></span></div>
                <?php if (!empty($order['delivery_address'])): ?><div class="detail-row"><span>Адрес:</span><span><?php echo nl2br(e($order['delivery_address'])); ?></span></div><?php endif; ?>
                <?php if (!empty($order['comment'])): ?><div class="detail-row"><span>Комментарий:</span><span><?php echo nl2br(e($order['comment'])); ?></span></div><?php endif; ?>
            </div>
            <form method="POST" action="/admin/update_order_status.php" class="admin-order-form">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <?php csrf_field(); ?>
                <label>Изменить статус:
                    <select name="status">
                        <option value="pending" <?php if ($order['status'] === 'pending') echo 'selected'; ?>>Ожидает</option>
                        <option value="payment" <?php if ($order['status'] === 'payment') echo 'selected'; ?>>Ожидает оплаты</option>
                        <option value="completed" <?php if ($order['status'] === 'completed') echo 'selected'; ?>>Завершён</option>
                        <option value="cancelled" <?php if ($order['status'] === 'cancelled') echo 'selected'; ?>>Отменён</option>
                    </select>
                </label>
                <button type="submit" class="btn btn-outline">Сохранить</button>
            </form>
        </div>
        <div class="order-items-section">
            <h2>Состав заказа</h2>
            <?php if (!empty($order['items'])): ?>
            <div class="table-container"><table class="data-table"><thead><tr><th>Товар</th><th>Кол-во</th><th>Цена</th><th>Сумма</th></tr></thead><tbody>
            <?php foreach ($order['items'] as $item): ?><tr><td><?php echo e($item['product_name']); ?></td><td><?php echo $item['quantity']; ?> шт</td><td><?php echo formatPrice($item['price']); ?></td><td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td></tr><?php endforeach; ?>
            </tbody></table></div><?php else: ?><p class="empty-state">Позиции не найдены</p><?php endif; ?>
        </div>
    </div>
    <a href="/admin/manage_orders.php" class="back-link">← Назад к заказам</a>
</div></section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>