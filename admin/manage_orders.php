<?php
/**
 * Управление заказами "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Заказы - Админ-панель';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';

requireAdmin();

$filter = isset($_GET['status']) ? $_GET['status'] : null;
$allowed_statuses = ['pending', 'payment', 'completed', 'cancelled'];

if ($filter && !in_array($filter, $allowed_statuses)) {
    $filter = null;
}

$orders = getAllOrders($filter);
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Управление заказами</h1>

        <!-- Фильтр по статусу -->
        <div class="status-filter">
            <a href="manage_orders.php" class="status-tag <?php echo !$filter ? 'active' : ''; ?>">
                Все
            </a>
            <a href="manage_orders.php?status=pending" class="status-tag status-pending <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                Ожидает
            </a>
            <a href="manage_orders.php?status=payment" class="status-tag status-payment <?php echo $filter === 'payment' ? 'active' : ''; ?>">
                Оплата
            </a>
            <a href="manage_orders.php?status=completed" class="status-tag status-completed <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                Завершён
            </a>
            <a href="manage_orders.php?status=cancelled" class="status-tag status-cancelled <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">
                Отменён
            </a>
        </div>

        <?php if (!empty($orders)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Дата</th>
                            <th>Покупатель</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <?php echo e($order['username'] ?? 'Гость'); ?><br>
                                    <small><?php echo e($order['email'] ?? ''); ?></small>
                                </td>
                                <td><?php echo number_format($order['total_amount'], 0, ',', ' '); ?> ₽</td>
                                <td>
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
                                </td>
                                <td>
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-view">
                                        👁️ Просмотр
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty-state">Заказов не найдено</p>
        <?php endif; ?>

        <a href="index.php" class="back-link">← Назад в панель</a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
