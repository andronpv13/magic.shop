<?php
/**
 * Профиль пользователя "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Мой профиль - Волшебная ЛАВКА';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Получение истории заказов
$orders = getUserOrders($user_id);
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="../index.php">Главная</a>
            <span class="separator">/</span>
            <span class="current">Мой профиль</span>
        </nav>

        <h1 class="page-title">Личный кабинет</h1>

        <div class="profile-layout">
            <!-- Информация о пользователе -->
            <div class="profile-section">
                <h2>Информация о пользователе</h2>
                <div class="profile-info">
                    <div class="info-row">
                        <span class="info-label">Имя:</span>
                        <span class="info-value"><?php echo e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo e($user['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Роль:</span>
                        <span class="info-value"><?php echo e(ucfirst($user['role'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Дата регистрации:</span>
                        <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
                <a href="edit_profile.php" class="btn btn-primary">Редактировать профиль</a>
            </div>
        </div>

        <!-- История заказов -->
        <div class="profile-section orders-section">
            <h2>История заказов</h2>

            <?php if (empty($orders)): ?>
                <p class="empty-state">У вас пока нет заказов</p>
                <a href="../shop.php" class="btn btn-primary">Перейти в каталог</a>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <span class="order-number">Заказ #<?php echo $order['id']; ?></span>
                                <span class="order-status status-<?php echo e($order['status']); ?>">
                                    <?php
                                    $status_labels = [
                                        'pending' => 'Ожидает оплаты',
                                        'payment' => 'Оплачен',
                                        'completed' => 'Выполнен',
                                        'cancelled' => 'Отменён'
                                    ];
                                    echo e($status_labels[$order['status']] ?? $order['status']);
                                    ?>
                                </span>
                                <span class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                            </div>

                            <div class="order-items">
                                <?php
                                $order_items = getOrderItems($order['id']);
                                foreach ($order_items as $item):
                                ?>
                                    <div class="order-item">
                                        <img src="<?php echo getProductImage($item['product_image']); ?>"
                                             alt="<?php echo e($item['product_name']); ?>"
                                             class="order-item-image">
                                        <div class="order-item-details">
                                            <span class="order-item-name"><?php echo e($item['product_name']); ?></span>
                                            <span class="order-item-quantity"><?php echo $item['quantity']; ?> шт.</span>
                                        </div>
                                        <span class="order-item-price"><?php echo number_format($item['price'] * $item['quantity'], 2, '.', ' '); ?> ₽</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="order-footer">
                                <span class="order-total">Итого: <?php echo number_format($order['total'], 2, '.', ' '); ?> ₽</span>
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline">Подробности</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.profile-layout {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.profile-section {
    background: var(--card-bg);
    padding: 2rem;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.profile-section h2 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: var(--text-primary);
    font-size: 1.5rem;
}

.profile-info {
    margin-bottom: 1.5rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: var(--text-secondary);
}

.info-value {
    color: var(--text-primary);
    font-weight: 400;
}

.orders-section {
    margin-top: 2rem;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.order-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.order-number {
    font-weight: 600;
    color: var(--text-primary);
}

.order-date {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.order-items {
    margin-bottom: 1.5rem;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}

.order-item:last-child {
    border-bottom: none;
}

.order-item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.order-item-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.order-item-name {
    font-weight: 500;
    color: var(--text-primary);
}

.order-item-quantity {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.order-item-price {
    font-weight: 600;
    color: var(--accent-gold);
}

.order-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.order-total {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--accent-gold);
}

.status-pending {
    background: #fff3cd;
    color: #856404;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-payment {
    background: #cce5ff;
    color: #004085;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-completed {
    background: #d4edda;
    color: #155724;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

@media (max-width: 768px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }

    .order-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }

    .order-footer {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .info-row {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
