<?php
$page_title = 'Настройки администратора';
require_once '../includes/header.php';
require_once '../includes/functions_adm.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    if (updateAdminContactInfo($email, $phone)) { $_SESSION['success'] = 'Контакты обновлены'; } else { $_SESSION['error'] = 'Ошибка обновления'; }
    header('Location: /admin/cab.php'); exit;
}
$admin_info = getAdminContactInfo();
?>
<section class="section"><div class="container">
    <h1 class="page-title">Личный кабинет администратора</h1>
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?php echo e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <form method="POST" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo e($admin_info['email'] ?? ''); ?>" required></div>
        <div class="form-group"><label>Телефон</label><input type="tel" name="phone" value="<?php echo e($admin_info['phone'] ?? ''); ?>" required></div>
        <button type="submit" class="btn btn-primary">Сохранить</button>
    </form>
    <a href="/admin/index.php" class="back-link">← Назад</a>
</div></section>
<?php require_once '../includes/footer.php'; ?>