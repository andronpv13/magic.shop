<?php
$page_title = 'Личный кабинет администратора';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';
requireRole('admin');

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
?>
<section class="section"><div class="container">
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
        <a href="/admin/edit_cab.php" class="btn btn-primary">Редактировать данные</a>
    </div>
    <a href="/admin/index.php" class="back-link">← Назад</a>
</div></section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>