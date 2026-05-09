<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
if (!isLoggedIn()) { header('Location: /login.php'); exit; }
$page_title = 'Мои заказы';
$orders = getUserOrders($_SESSION['user_id']);
?>
<section class="section"><div class="container">
    <div class="page-header"><h1 class="page-title">Мои заказы</h1><a href="/users/profile.php" class="btn btn-outline">← Мой профиль</a></div>
    <?php if (!empty($orders)): ?>
    <div class="orders-list">
    <?php foreach ($orders as $order): ?>
    <div class="order-card">
        <div class="order-header">
            <div><span class="order-number">Заказ #<?php echo e($order['id']); ?></span><span class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span></div>
            <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo getOrderStatusName($order['status']); ?></span>
        </div>
        <div class="order-items-preview">
            <?php $items = getOrderItems($order['id']); foreach ($items as $item): ?>
            <div class="order-item-preview"><span><?php echo e($item['product_name']); ?></span><span>x<?php echo $item['quantity']; ?></span></div>
            <?php endforeach; ?>
        </div>
        <div class="order-footer">
            <div class="order-total">Итого: <?php echo formatPrice($order['total']); ?></div>
            <a href="/users/order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-view">Подробнее</a>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state"><div class="empty-cart-icon">📦</div><h2>У вас пока нет заказов</h2><a href="/shop.php" class="btn btn-outline">Перейти в каталог</a></div>
    <?php endif; ?>
</div></section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
