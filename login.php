<?php
/**
* Страница входа "Волшебная ЛАВКА"
* Разработчик: АВВА © 2025
*/
$page_title = 'Вход в аккаунт';
require_once __DIR__ . '/includes/header.php';
// Если уже авторизован - редирект на главную
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF проверка
    if (!csrf_verify()) {
        $error = 'Ошибка безопасности (CSRF). Попробуйте снова.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username)) {
            $error = 'Введите логин или email';
        } elseif (empty($password)) {
            $error = 'Введите пароль';
        } elseif (preg_match('/[\s\t]/', $password)) {
            $error = 'Пароль не должен содержать пробелы и символы табуляции';
        } else {
            global $conn;
            $stmt = $conn->prepare("SELECT id, username, password, role, first_name, last_name FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $error = 'Неверный логин или пароль';
            } else {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Регенерация сессии после успешного логина для защиты от Session fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['first_name'] = $user['first_name'] ?? '';
                    $_SESSION['last_name'] = $user['last_name'] ?? '';

                    switch ($user['role']) {
                        case 'admin':     header('Location: /admin/index.php'); break;
                        case 'moderator': header('Location: /moderator/index_md.php'); break;
                        default:          header('Location: /index.php');
                    }
                    exit;
                } else {
                    $error = 'Неверный логин или пароль';
                }
            }
            $stmt->close();
        }
    }
}

?>

<section class="section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <h1 class="auth-title">✨ Вход</h1>
                <p class="auth-subtitle">Добро пожаловать в мир магии</p>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="form-group">
                        <label for="username">Логин или Email:</label>
                        <input type="text" id="username" name="username" class="form-control"
                               required value="<?php echo e($_POST['username'] ?? ''); ?>" autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль:</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" class="form-control">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-outline">Войти</button>
                </form>

                <div class="auth-footer">
                    <p>Нет аккаунта? <a href="/users/register.php">Зарегистрироваться</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Подключение скрипта валидации для функции показа/скрытия пароля -->
<script src="js/validation.js" defer></script>