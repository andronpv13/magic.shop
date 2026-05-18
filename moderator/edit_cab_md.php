<?php
/**
* Редактирование профиля модератора "Волшебная ЛАВКА"
* Разработчик: АВВА © 2025
*/
$page_title = 'Редактирование профиля - Панель модератора';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_md.php';
requireModerator();

$current_user = getCurrentUser();
$success = '';
$error = '';
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    if (empty($username) || empty($email)) {
        $error = 'Логин и Email обязательны';
    } elseif (!empty($password) && (strlen($password) < 6 || preg_match('/[\s\t]/', $password))) {
        $error = 'Пароль должен быть не менее 6 символов и не содержать пробелы и табуляцию';
    } else {
        // Проверка уникальности логина и email (кроме текущего пользователя)
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $_SESSION['user_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Логин или Email уже заняты';
        } else {
            $hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
            if ($hash) {
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, password=? WHERE id=?");
                $stmt->bind_param("sssssi", $username, $email, $first_name, $last_name, $hash, $_SESSION['user_id']);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=? WHERE id=?");
                $stmt->bind_param("ssssi", $username, $email, $first_name, $last_name, $_SESSION['user_id']);
            }

            if ($stmt->execute()) {
                $_SESSION['username'] = $username;
                $success = 'Профиль успешно обновлен';
                $current_user = getCurrentUser();
            } else {
                $error = 'Ошибка при обновлении профиля';
            }
        }
    }
}
?>
<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="index_md.php">Панель модератора</a>
            <span class="separator">/</span>
            <span class="current">Редактирование профиля</span>
        </nav>
        <h1 class="page-title">Редактирование профиля</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="username" value="<?php echo e($current_user['username']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo e($current_user['email']); ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Имя</label>
                    <input type="text" name="first_name" value="<?php echo e($current_user['first_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Фамилия</label>
                    <input type="text" name="last_name" value="<?php echo e($current_user['last_name'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Новый пароль (оставьте пустым, чтобы не менять)</label>
                <input type="password" name="password">
            </div>
            <button type="submit" class="btn btn-outline">💾 Сохранить изменения</button>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>