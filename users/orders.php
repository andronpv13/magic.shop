<?php
// Определяем, вызван ли файл напрямую или через include
$is_standalone = basename($_SERVER['PHP_SELF']) === 'orders.php';

if ($is_standalone) {
    require_once __DIR__ . '/../includes/header.php';
    if (!isLoggedIn()) { header('Location: /login.php'); exit; }
    $page_title = 'Мои заказы';
}

$orders = getUserOrders($_SESSION['user_id']);
?>
<?php if ($is_standalone): ?>
<section class="section"><div class="container">
    <div class="page-header"><h1 class="page-title">Мои заказы</h1>
    </div>
<?php endif; ?>

<?php if (!empty($orders)): ?>
<div class="products-slider orders-grid">
<?php foreach ($orders as $order): ?>
<div class="product-card order-card">
    <div class="order-header">
        <span class="order-number">Заказ №<?php echo e($order['id']); ?></span>
        <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo getOrderStatusName($order['status']); ?></span>
    </div>
    <div class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></div>
    <div class="card-content">
        <div class="order-items-preview">
            <?php
            $items = getOrderItems($order['id']);
            $items_count = count($items);
            foreach (array_slice($items, 0, 3) as $item):
            ?>
            <div class="order-item-preview"><?php echo e($item['product_name']); ?> — <?php echo $item['quantity']; ?> шт.</div>
            <?php endforeach; ?>
            <?php if ($items_count > 3): ?>
            <div class="order-more-items">+ ещё <?php echo $items_count - 3; ?> поз.</div>
            <?php endif; ?>
        </div>
        <p class="price"><?php echo formatPrice($order['total']); ?></p>
        <div class="btn-container">
            <a href="/users/order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline">Подробнее</a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state"><div class="empty-cart-icon">📦</div><h2>У вас пока нет заказов</h2><a href="/shop.php" class="btn btn-outline">Перейти в каталог</a></div>
<?php endif; ?>

<?php if ($is_standalone): ?>
</div></section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php endif; ?>