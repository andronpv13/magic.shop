<?php
/**
 * Детали заказа покупателя "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Детали заказа';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    header('Location: profile.php');
    exit;
}

$order = getOrderDetails($order_id, $_SESSION['user_id']);

if (!$order) {
    echo '<div class="container section"><p class="empty-state">Заказ не найден</p></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/users/profile.php">Мой профиль</a> <!-- Исправлен путь на абсолютный -->
            <span class="separator">/</span>
            <span class="current">Заказ #<?php echo $order_id; ?></span>
        </nav>

        <h1 class="page-title">Заказ #<?php echo $order_id; ?></h1>

        <div class="order-detail-layout">
            <!-- Информация о заказе -->
            <div class="order-info-section">
                <h2>Информация о заказе</h2>
                
                <div class="order-details">
                    <div class="detail-row">
                        <span>Дата создания:</span>
                        <span><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span>Статус:</span>
                        <!-- ИСПРАВЛЕНО: Используем функцию getOrderStatusName -->
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo getOrderStatusName($order['status']); ?>
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <span>Сумма:</span>
                        <!-- ИСПРАВЛЕНО: Используем formatPrice -->
                        <span class="order-total"><?php echo formatPrice($order['total_amount']); ?></span>
                    </div>
                    
                    <?php if (!empty($order['delivery_address'])): ?>
                        <div class="detail-row">
                            <span>Адрес доставки:</span>
                            <span><?php echo nl2br(e($order['delivery_address'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($order['comment'])): ?>
                        <div class="detail-row">
                            <span>Комментарий:</span>
                            <span><?php echo nl2br(e($order['comment'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Проверка статуса. Если в БД числа, то сравниваем с числом (например, 1) -->
                <?php if ($order['status'] == 1 || $order['status'] === 'pending'): ?>
                    <a href="/pay.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary btn-lg">
                        💳 Оплатить заказ
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Позиции заказа -->
        <div class="order-items-section">
            <h2>Состав заказа</h2>
            
            <?php if (!empty($order['items'])): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Товар</th>
                                <th>Количество</th>
                                <th>Цена</th>
                                <th>Сумма</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <a href="/shop.php?product_id=<?php echo $item['product_id']; ?>">
                                            <?php echo e($item['product_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo $item['quantity']; ?> шт</td>
                                    <!-- ИСПРАВЛЕНО: Используем formatPrice -->
                                    <td><?php echo formatPrice($item['price']); ?></td>
                                    <td><?php echo formatPrice($item['subtotal']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">Позиции не найдены</p>
            <?php endif; ?>
        </div>

        <a href="/users/orders.php" class="back-link">← Назад к заказам</a> <!-- Исправлен путь -->
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
