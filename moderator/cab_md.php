<?php
$page_title = 'Личный кабинет модератора';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_md.php';
requireModerator();

$current_user = getCurrentUser();
?>
<section class="section"><div class="container">
    <h1 class="page-title">Личный кабинет</h1>
    <div class="profile-section">
        <h2>Информация о пользователе</h2>
        <div class="profile-info">
            <div class="info-row">
                <span class="info-label">Логин:</span>
                <span class="info-value"><?php echo e($current_user['username']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo e($current_user['email']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Имя:</span>
                <span class="info-value"><?php echo e($current_user['first_name'] ?? ''); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Фамилия:</span>
                <span class="info-value"><?php echo e($current_user['last_name'] ?? ''); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Отчество:</span>
                <span class="info-value"><?php echo e($current_user['middle_name'] ?? ''); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Телефон:</span>
                <span class="info-value"><?php echo e($current_user['phone'] ?? ''); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Адрес:</span>
                <span class="info-value"><?php
                    $address_parts = array_filter([
                        $current_user['region'] ?? '',
                        $current_user['city'] ?? '',
                        $current_user['street'] ?? '',
                        $current_user['house'] ?? '',
                        $current_user['apartment'] ?? ''
                    ]);
                    echo e(implode(' ', $address_parts));
                ?></span>
            </div>
        </div>
        <a href="/moderator/edit_cab_md.php" class="btn btn-outline">Редактировать данные</a>
    </div>
    <a href="/moderator/index_md.php" class="back-link">← Назад</a>
</div></section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>