<?php
/**
 * Дашборд модератора "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Панель модератора';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_md.php';

requireModerator();

$stats = getModeratorStats($_SESSION['user_id']);
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Панель модератора</h1>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <h3><?php echo $stats['products']; ?></h3>
                    <p>Мои товары</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🛍️</div>
                <div class="stat-info">
                    <h3><?php echo $stats['orders']; ?></h3>
                    <p>Всего заказов</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_value'], 0, ',', ' '); ?> ₽</h3>
                    <p>Стоимость товаров</p>
                </div>
            </div>
        </div>

        <!-- Навигация -->
        <div class="admin-nav-grid">
            <a href="products_md.php" class="admin-nav-card">
                <span class="admin-nav-icon">📦</span>
                <h3>Мои товары</h3>
                <p>Управление своими товарами</p>
            </a>
            
            <a href="manage_orders_md.php" class="admin-nav-card">
                <span class="admin-nav-icon">🛍️</span>
                <h3>Заказы</h3>
                <p>Просмотр и управление заказами</p>
            </a>
            
            <a href="cab_md.php" class="admin-nav-card">
                <span class="admin-nav-icon">⚙️</span>
                <h3>Профиль</h3>
                <p>Редактирование профиля и смена пароля</p>
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
