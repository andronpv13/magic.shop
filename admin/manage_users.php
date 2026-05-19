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

// Проверка параметров URL для отображения сообщений
if (isset($_GET['success'])) {
    $success = 'Операция успешно выполнена';
}
if (isset($_GET['error'])) {
    $error = 'Произошла ошибка при выполнении операции';
}

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

        // Валидация на стороне сервера
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Заполните обязательные поля';
        } elseif (strlen($username) < 4 || strlen($username) > 10 || !preg_match('/^[a-zA-Zа-яА-ЯёЁ]+$/', $username)) {
            $error = 'Логин должен содержать от 4 до 10 букв (кириллица или латиница)';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Некорректный формат email';
        } elseif (strlen($password) < 6 || preg_match('/[\s\t]/', $password)) {
            $error = 'Пароль должен быть не менее 6 символов и не содержать пробелы и табуляцию';
        } else {
            $result = addUser($username, $email, $password, $first_name, $last_name, 'moderator');
            if ($result['success']) {
                $success = $result['message'];
                // Перенаправление для предотвращения повторной отправки формы
                header('Location: manage_users.php?success=1');
                exit;
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
                // Перенаправление для предотвращения повторной отправки формы
                header('Location: manage_users.php?success=1');
                exit;
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
                // Перенаправление для предотвращения повторной отправки формы
                header('Location: manage_users.php?success=1');
                exit;
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
            <button class="btn btn-outline" id="addModeratorBtn">
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
                                            <button class="btn btn-outline reset-password-btn"
                                                    data-modal-open="resetPasswordModal"
                                                    data-user-id="<?php echo $user['id']; ?>"
                                                    data-user-name="<?php echo e($user['username']); ?>">
                                                🔑
                                            </button>
                                            <form method="POST" class="form-inline"
                                                  onsubmit="return confirm('Удалить пользователя <?php echo e($user['username']); ?>?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <input type="hidden" name="delete_user" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-outline">🗑️</button>
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

        <a href="index.php" class="btn btn-outline">← Назад в панель</a>
    </div>
</section>

<!-- Модальное окно: Добавить модератора -->
<div id="addModeratorModal" class="modal">
    <div class="modal-content">
        <h2>Добавить модератора</h2>
        <form method="POST" id="addModeratorForm">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="form-group">
                <label for="mod_username">Логин: *</label>
                <div class="password-wrapper">
                    <input type="text" id="mod_username" name="username" required class="form-control" minlength="4">
                </div>
                <span class="form-hint" id="mod_username_hint"></span>
            </div>
            <div class="form-group">
                <label for="mod_email">Email: *</label>
                <div class="password-wrapper">
                    <input type="email" id="mod_email" name="email" required class="form-control">
                </div>
                <span class="form-hint" id="mod_email_hint"></span>
            </div>
            <div class="form-group">
                <label for="mod_password">Пароль: * (мин. 6 символов, без пробелов)</label>
                <div class="password-wrapper">
                    <input type="password" id="mod_password" name="password" required minlength="6" class="form-control">
                    <button type="button" class="password-toggle" data-tooltip="Показать пароль"></button>
                </div>
                <span class="form-hint" id="mod_password_hint"></span>
            </div>
            <div class="form-group">
                <label for="mod_first_name">Имя:</label>
                <input type="text" id="mod_first_name" name="first_name" class="form-control">
            </div>
            <div class="form-group">
                <label for="mod_last_name">Фамилия:</label>
                <input type="text" id="mod_last_name" name="last_name" class="form-control">
            </div>
            <div class="modal-actions">
                <button type="submit" name="add_moderator" class="btn btn-outline" id="modSubmitBtn" disabled>Добавить</button>
                <button type="button" class="btn btn-outline" data-modal-close>Отмена</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно: Сброс пароля -->
<div id="resetPasswordModal" class="modal">
    <div class="modal-content">
        <h2>Сброс пароля</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <input type="hidden" name="user_id" id="reset_user_id">
            <div class="form-group">
                <label for="new_password">Новый пароль: * (мин. 6 символов)</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" required minlength="6" class="form-control">
                    <button type="button" class="password-toggle" data-tooltip="Показать пароль"></button>
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" name="reset_password" class="btn btn-outline">Сбросить</button>
                <button type="button" class="btn btn-outline" data-modal-close>Отмена</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>