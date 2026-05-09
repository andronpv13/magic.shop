<?php
/**
 * Профиль пользователя "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Мой профиль - Волшебная ЛАВКА';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name)) {
        $error = 'Укажите имя';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Укажите корректный email';
    } else {
        // Проверка uniqueness email
        $existing_user = getUserByEmail($email);
        if ($existing_user && $existing_user['id'] != $user_id) {
            $error = 'Этот email уже используется';
        } else {
            $result = updateUserProfile($user_id, $name, $email);

            if ($result['success']) {
                $success = 'Профиль обновлен';
                $user = getUserById($user_id);
                $_SESSION['user_name'] = $name;
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Обработка смены пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Заполните все поля';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Новые пароли не совпадают';
    } elseif (strlen($new_password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        $result = changeUserPassword($user_id, $current_password, $new_password);

        if ($result['success']) {
            $success = 'Пароль изменен';
        } else {
            $error = $result['message'];
        }
    }
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

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="profile-layout">
            <!-- Информация о пользователе -->
            <div class="profile-section">
                <h2>Информация о пользователе</h2>
                <form method="POST" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="update_profile" value="1">

                    <div class="form-group">
                        <label for="name">Имя: *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo e($user['name']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email: *</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo e($user['email']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Роль:</label>
                        <p class="form-hint"><?php echo e(ucfirst($user['role'])); ?></p>
                    </div>

                    <div class="form-group">
                        <label>Дата регистрации:</label>
                        <p class="form-hint"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></p>
                    </div>

                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </form>
            </div>

            <!-- Смена пароля -->
            <div class="profile-section">
                <h2>Смена пароля</h2>
                <form method="POST" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="change_password" value="1">

                    <div class="form-group">
                        <label for="current_password">Текущий пароль: *</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">Новый пароль: *</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Подтвердите пароль: *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary">Изменить пароль</button>
                </form>
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
                                <span class="order-total">Итого: <?php echo number_format($order['total_amount'], 2, '.', ' '); ?> ₽</span>
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

.profile-form .form-group {
    margin-bottom: 1.5rem;
}

.form-hint {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-top: 0.5rem;
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
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
