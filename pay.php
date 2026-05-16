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
            <div class="alert alert-success"><h2>🎉 Спасибо за покупку!</h2><p>Ваш заказ успешно оплачен.</p>
            <a href="/users/orders.php" class="btn btn-primary">Перейти к моим заказам</a></div>
            <?php else: ?>
                <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

                <div class="payment-order-info" style="text-align: left; width: 100%; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--color-lavender); margin-bottom: 1rem;">Информация о заказе</h3>
                    <div class="payment-order-details">
                        <div class="detail-row" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <span style="color: var(--color-gray);">Номер заказа:</span>
                            <span><?php echo $order_id; ?></span>
                        </div>
                        <div class="detail-row" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <span style="color: var(--color-gray);">Дата создания:</span>
                            <span><?php echo date('d.m.Y - H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="detail-row" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <span style="color: var(--color-gray);">Статус:</span>
                            <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo getOrderStatusName($order['status']); ?></span>
                        </div>
                        <div class="detail-row detail-total" style="display: flex; justify-content: space-between; padding: 1rem 0; font-weight: bold;">
                            <span style="color: var(--color-gold);">Сумма заказа:</span>
                            <span class="detail-total-amount" style="color: var(--color-emerald-light);"><?php echo formatPrice($order['total']); ?></span>
                        </div>
                    </div>
                </div>

                <?php if ($order['status'] === 'pending'): ?>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <h3 style="color: var(--color-lavender); margin-bottom: 1rem;">Выберите способ оплаты</h3>
                    <div class="payment-methods">
                        <label class="payment-method" style="display: block; padding: 0.75rem; margin-bottom: 0.5rem; background: rgba(255,255,255,0.05); border-radius: var(--radius-md); cursor: pointer; transition: all var(--transition-normal);">
                            <input type="radio" name="payment_method" value="card" required style="margin-right: 0.5rem;"> 💳 Банковская карта
                        </label>
                        <label class="payment-method" style="display: block; padding: 0.75rem; margin-bottom: 0.5rem; background: rgba(255,255,255,0.05); border-radius: var(--radius-md); cursor: pointer; transition: all var(--transition-normal);">
                            <input type="radio" name="payment_method" value="cash" style="margin-right: 0.5rem;"> 💵 Наличные
                        </label>
                        <label class="payment-method" style="display: block; padding: 0.75rem; margin-bottom: 0.5rem; background: rgba(255,255,255,0.05); border-radius: var(--radius-md); cursor: pointer; transition: all var(--transition-normal);">
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

<style>
.payment-method:hover {
    background: rgba(157, 78, 221, 0.2) !important;
    border-color: var(--color-purple-bright);
}
.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-full);
    font-size: 0.85rem;
    font-weight: 600;
}
.status-pending { background: rgba(255, 193, 7, 0.2); color: #FFC107; }
.status-completed { background: rgba(46, 196, 182, 0.2); color: var(--color-emerald-light); }
.status-cancelled { background: rgba(224, 30, 90, 0.2); color: var(--color-ruby-light); }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>