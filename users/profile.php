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
        <h1 class="page-title">Личный кабинет</h1>
        <div class="profile-section">
            <h2>Информация о пользователе</h2>
            <div class="profile-data-wrapper">
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
                </div>
                <div class="profile-info">
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
                        <span class="info-value"><h4><?php
                            $address_parts = [];
                            if (!empty($user['zip_code'])) {
                                $address_parts[] = $user['zip_code'] . ',';
                            }
                            if (!empty($user['region'])) {
                                $address_parts[] = $user['region'] . ' ' .'обл.,';
                            }
                            if (!empty($user['city'])) {
                                $address_parts[] = $user['city'] . ',';
                            }
                            if (!empty($user['street'])) {
                                $address_parts[] = 'ул. ' . $user['street'] . ',';
                            }
                            if (!empty($user['house'])) {
                                $address_parts[] = 'дом № ' . $user['house'] . ',';
                            }
                            if (!empty($user['apartment'])) {
                                $address_parts[] = 'кв. ' . $user['apartment'];
                            }
                            echo e(implode(' ', $address_parts));
                        ?></h4></span>
                    </div>
                </div>
            </div>
            <div class="profile-actions">
                <a href="orders.php" class="btn btn-outline">История заказов</a>
                <a href="review.php" class="btn btn-outline">Мои отзывы</a>
                <a href="edit_profile.php" class="btn btn-outline">Редактировать данные</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>