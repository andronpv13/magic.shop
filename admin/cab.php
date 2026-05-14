<?php
$page_title = 'Личный кабинет администратора';
<<<<<<< HEAD
=======
require_once __DIR__ . '/../includes/config.php';
>>>>>>> 17aa9fe80430601b55ac05d1a95d326b8163eefa
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

<style>
.profile-section {
    background: var(--card-bg);
    padding: 2rem;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    max-width: 600px;
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

@media (max-width: 768px) {
    .info-row {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<<<<<<< HEAD
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
=======
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
>>>>>>> 17aa9fe80430601b55ac05d1a95d326b8163eefa
