<?php
/**
 * Личный кабинет модератора "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Профиль - Панель модератора';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_md.php';

requireModerator();

// Объявляем $mysqli глобальной, чтобы она была доступна в этом файле
global $mysqli;

$current_user = getCurrentUser();
$success = '';
$error = '';

// Обновление профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($email)) {
        $error = 'Email обязателен';
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Этот email уже используется';
        } else {
            $stmt = $mysqli->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = 'Профиль обновлен';
                // Обновляем данные пользователя в сессии
                $current_user = getCurrentUser();
            } else {
                $error = 'Ошибка при обновлении';
            }
            $stmt->close();
        }
    }
}

// Смена пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Заполните все поля';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        $stmt = $mysqli->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!password_verify($old_password, $user['password'])) {
            $error = 'Неверный старый пароль';
        } else {
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $password_hash, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = 'Пароль изменен';
            } else {
                $error = 'Ошибка при смене пароля';
            }
            $stmt->close();
        }
    }
}
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="index_md.php">Панель модератора</a>
            <span class="separator">/</span>
            <span class="current">Профиль</span>
        </nav>

        <h1 class="page-title">Настройки профиля</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="settings-grid">
            <!-- Редактирование профиля -->
            <div class="settings-card">
                <h2>Профиль</h2>
                <form method="POST" class="settings-form">
                    <div class="form-group">
                        <label for="username">Логин:</label> <!-- ИСПРАВЛЕНО: Заменил </label> на > -->
                        <input type="text" id="username" value="<?php echo e($current_user['username']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">Имя:</label> <!-- ИСПРАВЛЕНО -->
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo e($current_user['first_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Фамилия:</label> <!-- ИСПРАВЛЕНО -->
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo e($current_user['last_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label> <!-- ИСПРАВЛЕНО -->
                        <input type="email" id="email" name="email" required 
                               value="<?php echo e($current_user['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Телефон:</label> <!-- ИСПРАВЛЕНО -->
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo e($current_user['phone'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">Сохранить</button>
                </form>
            </div>

            <!-- Смена пароля -->
            <div class="settings-card">
                <h2>Смена пароля</h2>
                <form method="POST" class="settings-form">
                    <div class="form-group">
                        <label for="old_password">Старый пароль:</label> <!-- ИСПРАВЛЕНО -->
                        <input type="password" id="old_password" name="old_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Новый пароль:</label> <!-- ИСПРАВЛЕНО -->
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Подтверждение:</label> <!-- ИСПРАВЛЕНО -->
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">Изменить пароль</button>
                </form>
            </div>
        </div>

        <a href="index_md.php" class="back-link">← Назад в панель</a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
