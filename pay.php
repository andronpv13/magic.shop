<?php
/**
 * Страница оплаты "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Оплата заказа';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Требуем авторизацию
requireLogin();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    header('Location: /users/profile.php');
    exit;
}

// Получаем заказ
$order = getOrderDetails($order_id, $_SESSION['user_id']);

if (!$order) {
    echo '<div class="container section"><p class="empty-state">Заказ не найден</p></div>';
    require_once 'includes/footer.php';
    exit;
}

// Если заказ уже оплачен
if ($order['status'] === 'completed') {
    header('Location: /users/profile.php?order_id=' . $order_id);
    exit;
}

$success = '';
$error = '';

// Демо-оплата
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (empty($payment_method)) {
        $error = 'Выберите способ оплаты';
    } else {
        // Обновляем статус - ожидает обработки
        $result = updateOrderStatus($order_id, 'payment');
        
        if ($result['success']) {
            // Демо: сразу помечаем как оплаченный
            $result = updateOrderStatus($order_id, 'completed');
            
            if ($result['success']) {
                $success = 'Оплата прошла успешно! Заказ оплачен.';
                $order['status'] = 'completed';
            } else {
                $error = 'Ошибка при оплате';
            }
        } else {
            $error = 'Ошибка при обработке оплаты';
        }
    }
}
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Оплата заказа #<?php echo $order_id; ?></h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo e($success); ?>
            </div>
            <div class="payment-success-box">
                <h2>🎉 Спасибо за покупку!</h2>
                <p>Ваш заказ успешно оплачен.</p>
                <a href="/users/orders.php" class="btn btn-primary">Перейти к моим заказам</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <!-- Информация о заказе -->
            <div class="payment-order-info">
                <h2>Информация о заказе</h2>
                
                <div class="payment-order-details">
                    <div class="detail-row">
                        <span>Номер заказа:</span>
                        <span>#<?php echo $order_id; ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Дата создания:</span>
                        <span><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Статус:</span>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php
                            $status_names = [
                                'pending' => 'Ожидает оплаты',
                                'payment' => 'Ожидает обработки',
                                'completed' => 'Завершён',
                                'cancelled' => 'Отменён'
                            ];
                            echo $status_names[$order['status']] ?? $order['status'];
                            ?>
                        </span>
                    </div>
                    <div class="detail-row detail-total">
                        <span>Сумма заказа:</span>
                        <span class="detail-total-amount"><?php echo number_format($order['total_amount'], 0, ',', ' '); ?> ₽</span>
                    </div>
                </div>

                <!-- Позиции заказа -->
                <h3>Состав заказа</h3>
                <div class="payment-items">
                    <?php if (!empty($order['items'])): ?>
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="payment-item">
                                <span class="payment-item-name"><?php echo e($item['product_name']); ?></span>
                                <span class="payment-item-quantity"><?php echo $item['quantity']; ?> шт</span>
                                <span class="payment-item-price"><?php echo number_format($item['subtotal'], 0, ',', ' '); ?> ₽</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Выбор способа оплаты -->
            <?php if ($order['status'] === 'pending'): ?>
                <form method="POST" class="payment-form">
                    <h2>Выберите способ оплаты</h2>
                    
                    <div class="payment-methods">
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="card" required>
                            <span class="payment-method-icon">💳</span>
                            <span class="payment-method-name">Банковская карта</span>
                        </label>
                        
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="cash">
                            <span class="payment-method-icon">💵</span>
                            <span class="payment-method-name">Наличные</span>
                        </label>
                        
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="sbp">
                            <span class="payment-method-icon">₿</span>
                            <span class="payment-method-name">СБП</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-pay">Оплатить заказ</button>
                </form>
            <?php endif; ?>

            <a href="/users/profile.php" class="back-link">← Вернуться к заказам</a>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
