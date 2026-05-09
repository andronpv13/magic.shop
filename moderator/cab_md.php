<?php
$page_title = 'Профиль модератора';
require_once '../includes/header.php';
require_once '../includes/functions_md.php';
requireModerator();
$success = $error = '';
$current_user = getCurrentUser();
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fn = trim($_POST['first_name'] ?? ''); $ln = trim($_POST['last_name'] ?? '');
    $em = trim($_POST['email'] ?? ''); $ph = trim($_POST['phone'] ?? '');
    if (empty($em)) $error = 'Email обязателен';
    else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?"); $stmt->bind_param("si", $em, $_SESSION['user_id']); $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $error = 'Email уже используется';
        else {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE id=?");
            $stmt->bind_param("ssssi", $fn, $ln, $em, $ph, $_SESSION['user_id']);
            if ($stmt->execute()) { $success = 'Профиль обновлен'; $current_user = getCurrentUser(); } else $error = 'Ошибка обновления';
        }
    }
}
?>
<section class="section"><div class="container">
    <h1 class="page-title">Настройки профиля</h1>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
    <form method="POST" class="settings-form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="form-group"><label>Логин</label><input type="text" value="<?php echo e($current_user['username']); ?>" disabled></div>
        <div class="form-group"><label>Имя</label><input type="text" name="first_name" value="<?php echo e($current_user['first_name'] ?? ''); ?>"></div>
        <div class="form-group"><label>Фамилия</label><input type="text" name="last_name" value="<?php echo e($current_user['last_name'] ?? ''); ?>"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo e($current_user['email']); ?>" required></div>
        <div class="form-group"><label>Телефон</label><input type="tel" name="phone" value="<?php echo e($current_user['phone'] ?? ''); ?>"></div>
        <button type="submit" name="update_profile" class="btn btn-primary">Сохранить</button>
    </form>
    <a href="/moderator/index_md.php" class="back-link">← Назад</a>
</div></section>
<?php require_once '../includes/footer.php'; ?>
