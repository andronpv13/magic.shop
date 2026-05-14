<?php
/**
 * Профиль пользователя "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Мой профиль - Волшебная ЛАВКА';
require_once __DIR__ . '/../includes/header.php';

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
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="../index.php">Главная</a>
            <span class="separator">/</span>
            <span class="current">Мой профиль</span>
        </nav>

        <h1 class="page-title">Личный кабинет</h1>

        <div class="profile-section">
            <h2>Информация о пользователе</h2>
            <div class="profile-info">
                <div class="info-row">
                    <span class="info-label">Логин:</span>
                    <span class="info-value"><?php echo e($user['username']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo e($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Имя:</span>
                    <span class="info-value"><?php echo e($user['first_name'] ?? ''); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Фамилия:</span>
                    <span class="info-value"><?php echo e($user['last_name'] ?? ''); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Отчество:</span>
                    <span class="info-value"><?php echo e($user['middle_name'] ?? ''); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Телефон:</span>
                    <span class="info-value"><?php echo e($user['phone'] ?? ''); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Адрес:</span>
                    <span class="info-value"><?php
                        $address_parts = array_filter([
                            $user['region'] ?? '',
                            $user['city'] ?? '',
                            $user['street'] ?? '',
                            $user['house'] ?? '',
                            $user['apartment'] ?? ''
                        ]);
                        echo e(implode(' ', $address_parts));
                    ?></span>
                </div>
            </div>
            <a href="edit_profile.php" class="btn btn-primary">Редактировать данные</a>
        </div>

        <div class="profile-section orders-section">
            <h2>История заказов</h2>
            <?php include __DIR__ . '/orders.php'; ?>
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
