<?php
/**
 * Дашборд администратора "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Админ-панель';
require_once __DIR__ . '/../includes/functions_adm.php';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

$stats = getAdminStats();
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Панель администратора</h1>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?php echo $stats['customers']; ?></h3>
                    <p>Покупателей</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🛡️</div>
                <div class="stat-info">
                    <h3><?php echo $stats['moderators']; ?></h3>
                    <p>Модераторов</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <h3><?php echo $stats['products']; ?></h3>
                    <p>Товаров</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🛍️</div>
                <div class="stat-info">
                    <h3><?php echo $stats['orders']; ?></h3>
                    <p>Заказов</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['revenue'], 0, ',', ' '); ?> ₽</h3>
                    <p>Выручка</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-info">
                    <h3><?php echo $stats['reviews']; ?></h3>
                    <p>Отзывов</p>
                </div>
            </div>
        </div>

        <!-- Навигация -->
        <div class="admin-nav-grid">
            <a href="products.php" class="admin-nav-card">
                <span class="admin-nav-icon">📦</span>
                <h3>Управление товарами</h3>
                <p>Добавление, редактирование и удаление товаров</p>
            </a>
            
            <a href="manage_orders.php" class="admin-nav-card">
                <span class="admin-nav-icon">🛍️</span>
                <h3>Управление заказами</h3>
                <p>Просмотр и изменение статусов заказов</p>
            </a>
            
            <a href="manage_users.php" class="admin-nav-card">
                <span class="admin-nav-icon">👥</span>
                <h3>Управление пользователями</h3>
                <p>Добавление модераторов, управление аккаунтами</p>
            </a>
            
            <a href="manage_review.php" class="admin-nav-card">
                <span class="admin-nav-icon">💬</span>
                <h3>Управление отзывами</h3>
                <p>Просмотр и удаление отзывов</p>
            </a>
            
            <a href="settings.php" class="admin-nav-card">
                <span class="admin-nav-icon">⚙️</span>
                <h3>Настройки сайта</h3>
                <p>Изменение оформления сайта</p>
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
