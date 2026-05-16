<?php
require_once __DIR__ . '/includes/header.php';

// Если пользователь уже авторизован, перенаправляем
if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin': redirect('/admin/index.php'); break;
        case 'moderator': redirect('/moderator/index_md.php'); break;
        default: redirect('/index.php');
    }
}

$errors = [];
$success = '';

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Волшебная ЛАВКА</title>
    <link rel="stylesheet" href="../css/magic.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card-wide">
            <h2 class="text-center mb-6">Регистрация</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="">
                <!-- Логин -->
                <div class="form-group">
                    <label for="username">Логин (4-10 букв)</label>
                    <input type="text"
                           id="username"
                           name="username"
                           class="form-control"
                           placeholder="Введите логин"
                           required
                           autocomplete="off">
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email"
                           id="email"
                           name="email"
                           class="form-control"
                           placeholder="example@mail.ru"
                           required
                           autocomplete="off">
                </div>

                <!-- Пароль -->
                <div class="form-group">
                    <label for="password">Пароль (мин. 6 символов)</label>
                    <div class="password-wrapper">
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control"
                               placeholder="Введите пароль"
                               required>
                        <!-- Кнопка глаза добавляется через JS -->
                    </div>
                </div>

                <!-- Подтверждение пароля -->
                <div class="form-group">
                    <label for="confirm_password">Подтвердите пароль</label>
                    <div class="password-wrapper">
                        <input type="password"
                               id="confirm_password"
                               name="confirm_password"
                               class="form-control"
                               placeholder="Повторите пароль"
                               required>
                        <!-- Кнопка глаза добавляется через JS -->
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="btn btn-primary btn-block">Зарегистрироваться</button>
            </form>

            <p class="text-center mt-6">
                Уже есть аккаунт? <a href="login.php">Войти</a>
            </p>
        </div>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <!-- Подключение внешнего скрипта валидации -->
    <script>
        // Передаем базовый URL для API в внешний скрипт
        window.apiBaseUrl = '../';
    </script>
    <script src="../js/validation.js" defer></script>
</body>
</html>