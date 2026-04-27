<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
if (!isLoggedIn()) { header('Location: /login.php'); exit; }
$page_title = 'Личный кабинет';
global $conn;
$uid = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?"); $stmt->bind_param("i", $uid); $stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
if (!$current_user) { echo '<div class="container section"><p class="empty-state">Пользователь не найден</p></div>'; require_once '../includes/footer.php'; exit; }
?>
<section class="section"><div class="container"><div class="profile-layout">
    <div class="profile-section">
        <div class="user-profile-header"><h2><?php echo e($current_user['first_name'] ?? $current_user['username']); ?></h2><p><?php echo e($current_user['username']); ?></p></div>
        <nav class="profile-nav">
            <a href="/users/profile.php" class="profile-nav-link active">👤 Личные данные</a>
            <a href="/users/orders.php" class="profile-nav-link">📦 История заказов</a>
            <a href="/users/edit_profile.php" class="profile-nav-link">✏️ Настройки профиля</a>
            <?php if (hasAnyRole(['admin', 'moderator'])): ?><a href="/admin/index.php" class="profile-nav-link">⚙️ Панель управления</a><?php endif; ?>
            <a href="/logout.php" class="profile-nav-link logout">🚪 Выход</a>
        </nav>
    </div>
    <div class="profile-section">
        <h2 class="section-title">Мои данные</h2>
        <div class="profile-info-card">
            <div class="info-row"><span>Логин:</span><span><?php echo e($current_user['username']); ?></span></div>
            <div class="info-row"><span>Email:</span><span><?php echo e($current_user['email']); ?></span></div>
            <div class="info-row"><span>Телефон:</span><span><?php echo e($current_user['phone'] ?? '-'); ?></span></div>
            <div class="info-row"><span>Адрес:</span><span><?php echo e($current_user['address'] ?? 'Не указан'); ?></span></div>
        </div>
    </div>
</div></div></section>
<?php require_once '../includes/footer.php'; ?>