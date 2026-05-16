<?php
$page_title = 'Оплата заказа';
require_once __DIR__ . '/includes/header.php';
if (!isLoggedIn()) { header('Location: /login.php'); exit; }
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$order_id) { header('Location: /users/orders.php'); exit; }
$order = getOrderDetails($order_id, $_SESSION['user_id']);
if (!$order) { echo '<div class="container section"><p class="empty-state">Заказ не найден</p></div>'; require_once 'includes/footer.php'; exit; }
if ($order['status'] === 'completed') { header('Location: /users/orders.php'); exit; }
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    if (empty($payment_method)) $error = 'Выберите способ оплаты';
    else {
        $res = payForOrder($order_id, $_SESSION['user_id']);
        if ($res['success']) { $success = $res['message']; $order['status'] = 'completed'; }
        else $error = $res['message'];
    }
}
?>

<section class="section">
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">💳 Оплата заказа</h1>
            <p class="auth-subtitle">Заказ №<?php echo $order_id; ?></p>

            <?php if ($success): ?>
            <div class="alert alert-success payment-success">
                <h2>🎉 Спасибо за покупку!</h2>
                <p>Ваш заказ успешно оплачен.</p>
                <div class="payment-success-actions">
                    <a href="/users/orders.php" class="btn btn-primary">Перейти к моим заказам</a>
                </div>
            </div>
            <?php else: ?>
                <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

                <div class="payment-order-info">
                    <h3 style="color: var(--color-lavender); margin-bottom: 1rem;">Информация о заказе</h3>
                    <div class="payment-order-details">
                        <div class="detail-row">
                            <span style="color: var(--color-gray);">Номер заказа:</span>
                            <span><?php echo $order_id; ?></span>
                        </div>
                        <div class="detail-row">
                            <span style="color: var(--color-gray);">Дата создания:</span>
                            <span><?php echo date('d.m.Y - H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span style="color: var(--color-gray);">Статус:</span>
                            <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo getOrderStatusName($order['status']); ?></span>
                        </div>
                        <div class="detail-row detail-total">
                            <span style="color: var(--color-gold);">Сумма заказа:</span>
                            <span class="detail-total-amount"><?php echo formatPrice($order['total']); ?></span>
                        </div>
                    </div>
                </div>

                <?php if ($order['status'] === 'pending'): ?>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <h3 style="color: var(--color-lavender); margin-bottom: 1rem;">Выберите способ оплаты</h3>
                    <div class="payment-methods">
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="card" required style="margin-right: 0.5rem;"> 💳 Банковская карта
                        </label>
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="cash" style="margin-right: 0.5rem;"> 💵 Наличные
                        </label>
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="sbp" style="margin-right: 0.5rem;"> 📱 СБП
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Оплатить заказ</button>
                </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>