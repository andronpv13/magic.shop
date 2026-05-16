<?php
/**
* Страница регистрации "Волшебная ЛАВКА"
* Разработчик: АВВА © 2025
*/
$page_title = 'Регистрация аккаунта';
require_once __DIR__ . '/../includes/header.php';

// Если пользователь уже авторизован, перенаправляем
if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin': redirect('/admin/index.php'); break;
        case 'moderator': redirect('/moderator/index_md.php'); break;
        default: redirect('/index.php');
    }
}

$errors = [];

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF проверка
    if (!csrf_verify()) {
        $errors[] = 'Ошибка безопасности (CSRF). Попробуйте снова.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Серверная валидация (дублирует клиентскую для безопасности - это правильная практика:
        // клиентская валидация для UX, серверная для безопасности)
        if (strlen($username) < 4 || strlen($username) > 10 || !preg_match('/^[a-zA-Zа-яА-ЯёЁ]+$/', $username)) {
            $errors[] = "Логин должен содержать от 4 до 10 букв без пробелов и спецсимволов.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Некорректный формат email.";
        }

        if (strlen($password) < 6 || preg_match('/[\s\t]/', $password)) {
            $errors[] = "Пароль должен быть не менее 6 символов и не содержать пробелы и табуляцию.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Пароли не совпадают.";
        }

        // Проверка на существование (если нет ошибок формата)
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->fetch_assoc()) {
                $errors[] = "Пользователь с таким логином или email уже существует.";
            }
            $stmt->close();
        }

        // Регистрация
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'customer'; // По умолчанию покупатель

            try {
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
                $stmt->execute();

                // Регенерация сессии после успешной регистрации для защиты от Session fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;

                redirect('edit_profile.php');
            } catch (Exception $e) {
                $errors[] = "Ошибка регистрации: " . $e->getMessage();
            } finally {
                if (isset($stmt)) {
                    $stmt->close();
                }
            }
        }
    }
}
?>

<section class="section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <h1 class="auth-title">✨ Регистрация</h1>
                <p class="auth-subtitle">Создайте аккаунт в мире магии</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo e($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="form-group">
                        <label for="username">Логин (4-10 букв):</label>
                        <input type="text" id="username" name="username" class="form-control"
                               placeholder="Введите логин"
                               required value="<?php echo e($_POST['username'] ?? ''); ?>" autofocus autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="example@mail.ru"
                               required value="<?php echo e($_POST['email'] ?? ''); ?>" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль (мин. 6 символов):</label>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Введите пароль"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Подтвердите пароль:</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                               placeholder="Повторите пароль"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">Зарегистрироваться</button>
                </form>

                <div class="auth-footer">
                    <p>Уже есть аккаунт? <a href="/login.php">Войти</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Подключение внешнего скрипта валидации -->
<script>
    // Передаем базовый URL для API в внешний скрипт
    window.apiBaseUrl = '../';
</script>
<script src="../js/validation.js" defer></script>