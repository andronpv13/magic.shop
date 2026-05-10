<?php
/**
* Страница регистрации "Волшебная ЛАВКА"
* Разработчик: АВВА © 2025
*/
$page_title = 'Регистрация';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header("Location: /index.php");
    exit;
}

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!csrf_verify()) {
        $message = "Ошибка безопасности (CSRF).";
        $msgType = 'error';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($password) || empty($email)) {
            $message = "Все поля обязательны для заполнения.";
            $msgType = 'error';
        } elseif ($password !== $confirm_password) {
            $message = "Пароли не совпадают.";
            $msgType = 'error';
        } elseif (strlen($password) < 6) {
            $message = "Пароль должен быть не менее 6 символов.";
            $msgType = 'error';
        } else {
            $result = registerUser($username, $email, $password);
            if ($result['success']) {
                $_SESSION['just_registered'] = true;
                header("Location: /users/edit_profile.php");
                exit;
            } else {
                $message = $result['message'];
                $msgType = 'error';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card auth-card-wide">
                <h1 class="auth-title">✨ Регистрация</h1>
                <p class="auth-subtitle">Станьте частью волшебного сообщества</p>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $msgType; ?>"><?php echo e($message); ?></div>
                <?php endif; ?>

                <form action="/users/register.php" method="POST" class="auth-form" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Логин</label>
                            <input type="text" id="username" name="username" class="form-control"
                                   value="<?php echo e($_POST['username'] ?? ''); ?>" required>
                            <small id="username-feedback" class="feedback-message"></small>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                            <small id="email-feedback" class="feedback-message"></small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Пароль</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <small id="password-feedback" class="feedback-message"></small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Повторите пароль</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            <small id="confirm-password-feedback" class="feedback-message"></small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">Создать аккаунт</button>
                </form>

                <div class="auth-footer">
                    <p>Уже есть аккаунт? <a href="../login.php">Войти</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Скрипт валидации -->
<script src="/js/validation.js"></script>

<!-- Стили для валидации -->
<style>
    .form-row { display: flex; gap: 1rem; }
    .form-row .form-group { flex: 1; }
    .feedback-message.valid { color: #32CD32; }
    .feedback-message.invalid { color: #DC143C; }

    @media (max-width: 600px) {
        .form-row { flex-direction: column; gap: 0; }
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
