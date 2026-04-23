<?php
/**
 * Страница истории заказов "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Проверка авторизации
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$page_title = 'Мои заказы';
$current_user = getCurrentUser();

// Получаем заказы текущего пользователя
$orders = getUserOrders($current_user['id']); 
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Мои заказы</h1>
            <!-- Кнопка "Мой профиль" уже имеет стиль btn-outline -->
            <a href="/users/profile.php" class="btn btn-outline">← Мой профиль</a>
        </div>

        <?php if (!empty($orders)): ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-number">Заказ #<?php echo e($order['id']); ?></span>
                                <span class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo getOrderStatusName($order['status']); ?>
                            </span>
                        </div>
                        
                        <div class="order-items-preview">
                            <?php 
                            $items = getOrderItems($order['id']); 
                            foreach ($items as $item): 
                            ?>
                                <div class="order-item-preview">
                                    <span><?php echo e($item['product_name']); ?></span>
                                    <span>x<?php echo $item['quantity']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-footer">
                            <div class="order-total">
                                Итого: <?php echo formatPrice($order['total_amount']); ?>
                            </div>
                            <!-- ИСПРАВЛЕНО: Правильная ссылка на страницу деталей заказа -->
                            <a href="/users/order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-view">Подробнее</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-cart-icon">📦</div>
                <h2>У вас пока нет заказов</h2>
                <p>Перейдите в каталог, чтобы выбрать что-нибудь интересное.</p>
                <!-- ИСПРАВЛЕНО: Заменен класс btn-primary на btn-outline для идентичности стиля -->
                <a href="/shop.php" class="btn btn-outline">Перейти в каталог</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
