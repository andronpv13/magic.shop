<?php
$page_title = 'Оплата заказа';
require_once 'includes/header.php';
require_once 'includes/functions.php';
requireLogin();
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
        updateOrderStatus($order_id, 'payment');
        $res = updateOrderStatus($order_id, 'completed');
        if ($res['success']) { $success = 'Оплата прошла успешно! Заказ оплачен.'; $order['status'] = 'completed'; }
        else $error = 'Ошибка при обработке оплаты';
    }
}
?>
<section class="section"><div class="container">
    <h1 class="page-title">Оплата заказа #<?php echo $order_id; ?></h1>
    <?php if ($success): ?>
    <div class="alert alert-success"><h2>🎉 Спасибо за покупку!</h2><p>Ваш заказ успешно оплачен.</p><a href="/users/orders.php" class="btn btn-primary">Перейти к моим заказам</a></div>
    <?php else: ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
        <div class="payment-order-info"><h2>Информация о заказе</h2>
            <div class="payment-order-details"><div class="detail-row"><span>Номер заказа:</span><span>#<?php echo $order_id; ?></span></div><div class="detail-row"><span>Дата создания:</span><span><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span></div><div class="detail-row"><span>Статус:</span><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo getOrderStatusName($order['status']); ?></span></div><div class="detail-row detail-total"><span>Сумма заказа:</span><span class="detail-total-amount"><?php echo formatPrice($order['total']); ?></span></div></div>
        </div>
        <?php if ($order['status'] === 'pending'): ?>
        <form method="POST" class="payment-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <h2>Выберите способ оплаты</h2>
            <div class="payment-methods">
                <label class="payment-method"><input type="radio" name="payment_method" value="card" required> 💳 Банковская карта</label>
                <label class="payment-method"><input type="radio" name="payment_method" value="cash"> 💵 Наличные</label>
                <label class="payment-method"><input type="radio" name="payment_method" value="sbp"> 📱 СБП</label>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-pay">Оплатить заказ</button>
        </form>
        <?php endif; ?>
        <a href="/users/orders.php" class="back-link">← Вернуться к заказам</a>
    <?php endif; ?>
</div></section>
<?php require_once 'includes/footer.php'; ?>