<?php
/**
 * Управление пользователями "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Пользователи - Админ-панель';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';

requireAdmin();

$success = '';
$error = '';

// Добавление модератора
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_moderator'])) {
    // Проверка CSRF
    if (!csrf_verify()) {
        $error = 'Ошибка безопасности. Попробуйте обновить страницу.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');

        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Заполните обязательные поля';
        } elseif (strlen($password) < 6) {
            $error = 'Пароль должен быть не менее 6 символов';
        } else {
            $result = addUser($username, $email, $password, $first_name, $last_name, 'moderator');
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Удаление пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    // Проверка CSRF
    if (!csrf_verify()) {
        $error = 'Ошибка безопасности. Попробуйте обновить страницу.';
    } else {
        $user_id = (int)$_POST['delete_user'];

        // Проверка: нельзя удалить самого себя (на уровне PHP)
        if ($user_id === $_SESSION['user_id']) {
            $error = 'Вы не можете удалить свой аккаунт.';
        } else {
            $result = deleteUser($user_id);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Сброс пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    // Проверка CSRF
    if (!csrf_verify()) {
        $error = 'Ошибка безопасности. Попробуйте обновить страницу.';
    } else {
        $user_id = (int)$_POST['user_id'];
        $new_password = $_POST['new_password'] ?? '';

        if (empty($new_password)) {
            $error = 'Введите новый пароль';
        } elseif (strlen($new_password) < 6) {
            $error = 'Пароль должен быть не менее 6 символов';
        } else {
            $result = resetUserPassword($user_id, $new_password);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

$users = getAllUsers();
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Управление пользователями</h1>
            <button class="btn btn-primary" onclick="document.getElementById('addModeratorModal').style.display='block'">
                + Добавить модератора
            </button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($users)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Логин</th>
                            <th>Email</th>
                            <th>Имя</th>
                            <th>Роль</th>
                            <th>Дата регистрации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td data-label="ID"><?php echo $user['id']; ?></td>
                                <td data-label="Логин"><?php echo e($user['username']); ?></td>
                                <td data-label="Email"><?php echo e($user['email']); ?></td>
                                <td data-label="Имя"><?php echo e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                                <td data-label="Роль">
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php
                                        $role_names = ['admin' => 'Админ', 'moderator' => 'Модератор', 'customer' => 'Покупатель'];
                                        echo $role_names[$user['role']] ?? $user['role'];
                                        ?>
                                    </span>
                                </td>
                                <td data-label="Дата регистрации"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                <td data-label="Действия">
                                    <div class="table-actions">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-reset-password"
                                                    onclick="document.getElementById('resetPasswordModal').style.display='block'; document.getElementById('reset_user_id').value=<?php echo $user['id']; ?>">
                                                🔑
                                            </button>
                                            <form method="POST" class="form-inline"
                                                  onsubmit="return confirm('Удалить пользователя <?php echo e($user['username']); ?>?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <input type="hidden" name="delete_user" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-delete">🗑️</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="form-hint">Это вы</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty-state">Пользователей не найдено</p>
        <?php endif; ?>

        <a href="index.php" class="back-link">← Назад в панель</a>
    </div>
</section>

<!-- Модальное окно: Добавить модератора -->
<div id="addModeratorModal" class="modal" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-content">
        <h2>Добавить модератора</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="form-group">
                <label for="username">Логин: *</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email: *</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Пароль: * (мин. 6 символов)</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="first_name">Имя:</label>
                <input type="text" id="first_name" name="first_name">
            </div>
            <div class="form-group">
                <label for="last_name">Фамилия:</label>
                <input type="text" id="last_name" name="last_name">
            </div>
            <div class="modal-actions">
                <button type="submit" name="add_moderator" class="btn btn-primary">Добавить</button>
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal').style.display='none'">Отмена</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно: Сброс пароля -->
<div id="resetPasswordModal" class="modal" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-content">
        <h2>Сброс пароля</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <input type="hidden" name="user_id" id="reset_user_id">
            <div class="form-group">
                <label for="new_password">Новый пароль: * (мин. 6 символов)</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
            </div>
            <div class="modal-actions">
                <button type="submit" name="reset_password" class="btn btn-primary">Сбросить</button>
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal').style.display='none'">Отмена</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>