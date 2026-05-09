<?php
/**
 * Детали заказа модератора "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Детали заказа - Панель модератора';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_md.php';

requireModerator();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header('Location: manage_orders_md.php');
    exit;
}

$order = getOrderDetailsModerator($order_id);

if (!$order) {
    echo '<div class="container section"><p class="empty-state">Заказ не найден</p></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$success = '';
$error = '';

// Изменение статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'] ?? '';
    $allowed_statuses = ['pending', 'payment', 'completed', 'cancelled'];

    if (!in_array($new_status, $allowed_statuses)) {
        $error = 'Недопустимый статус';
    } else {
        $result = updateOrderStatusModerator($order_id, $new_status);

        if ($result['success']) {
            $success = $result['message'];
            $order = getOrderDetailsModerator($order_id);
        } else {
            $error = $result['message'];
        }
    }
}
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="index_md.php">Панель модератора</a>
            <span class="separator">/</span>
            <a href="manage_orders_md.php">Заказы</a>
            <span class="separator">/</span>
            <span class="current">Заказ #<?php echo $order_id; ?></span>
        </nav>

        <h1 class="page-title">Заказ #<?php echo $order_id; ?></h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

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
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php
                            $status_names = [
                                'pending' => 'Ожидает',
                                'payment' => 'Оплата',
                                'completed' => 'Завершён',
                                'cancelled' => 'Отменён'
                            ];
                            echo $status_names[$order['status']] ?? $order['status'];
                            ?>
                        </span>
                    </div>

                    <div class="detail-row">
                        <span>Сумма:</span>
                        <span class="order-total"><?php echo number_format($order['total'], 0, ',', ' '); ?> ₽</span>
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

                <!-- Смена статуса -->
                <form method="POST" class="status-form">
                    <h3>Изменить статус</h3>

                    <div class="form-group">
                        <select name="status" required>
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Ожидает</option>
                            <option value="payment" <?php echo $order['status'] === 'payment' ? 'selected' : ''; ?>>Ожидает оплаты</option>
                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Завершён</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменён</option>
                        </select>
                    </div>

                    <button type="submit" name="update_status" class="btn btn-primary">Обновить статус</button>
                </form>
            </div>

            <!-- Информация о покупателе -->
            <div class="customer-info-section">
                <h2>Покупатель</h2>

                <div class="customer-details">
                    <p><strong>Имя:</strong> <?php echo e($order['first_name'] ?? $order['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo e($order['email'] ?? '-'); ?></p>
                    <p><strong>Телефон:</strong> <?php echo e($order['phone'] ?? '-'); ?></p>
                </div>
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
                                        <?php echo e($item['product_name']); ?>
                                    </td>
                                    <td><?php echo $item['quantity']; ?> шт</td>
                                    <td><?php echo number_format($item['price'], 0, ',', ' '); ?> ₽</td>
                                    <td><?php echo number_format($item['subtotal'], 0, ',', ' '); ?> ₽</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">Позиции не найдены</p>
            <?php endif; ?>
        </div>

        <a href="manage_orders_md.php" class="back-link">← Назад к заказам</a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
