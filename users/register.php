<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

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

    // Серверная валидация (дублирует клиентскую для безопасности)
    if (strlen($username) < 4 || strlen($username) > 10 || !preg_match('/^[a-zA-Zа-яА-ЯёЁ]+$/', $username)) {
        $errors[] = "Логин должен содержать от 4 до 10 букв без пробелов и спецсимволов.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат email.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Пароль должен быть не менее 6 символов.";
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

            redirect('profile.php');
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
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Дополнительные стили специально для формы регистрации */
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 200px);
            padding: 2rem 1rem;
        }

        .auth-card-wide {
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 700px; /* Увеличенная ширина */
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box; /* Важно для padding */
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Стили валидации */
        .form-control.success {
            border-color: #2ecc71;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232ecc71' width='20px' height='20px'%3E%3Cpath d='M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center; /* Галочка справа */
            background-size: 20px;
            padding-right: 40px; /* Место под галочку */
        }

        .form-control.error {
            border-color: #e74c3c;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23e74c3c' width='20px' height='20px'%3E%3Cpath d='M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center; /* Крестик справа */
            background-size: 20px;
            padding-right: 40px; /* Место под крестик */
        }

        /* Обертка для полей пароля */
        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper .form-control {
            padding-right: 80px; /* Место под глаз И галочку */
        }

        /* Кнопка глаза */
        .password-toggle {
            position: absolute;
            right: 45px; /* Слева от галочки/крестика */
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #777;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        /* Иконка глаза через CSS (SVG background) */
        .password-toggle::before {
            content: '';
            display: block;
            width: 20px;
            height: 20px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'%3E%3C/path%3E%3Ccircle cx='12' cy='12' r='3'%3E%3C/circle%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
            transition: opacity 0.2s;
        }

        /* Перечеркнутый глаз (активное состояние - пароль виден) */
        .password-toggle.active::before {
             background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24'%3E%3C/path%3E%3Cline x1='1' y1='1' x2='23' y2='23'%3E%3C/line%3E%3C/svg%3E");
        }

        .btn-block {
            width: 100%;
            padding: 0.8rem;
            font-size: 1.1rem;
            margin-top: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .alert-error {
            background-color: #fdecea;
            color: #c0392b;
            border: 1px solid #fadbd8;
        }
        .alert-success {
            background-color: #eafaf1;
            color: #27ae60;
            border: 1px solid #d5f5e3;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="auth-container">
        <div class="auth-card-wide">
            <h2 style="text-align: center; margin-bottom: 1.5rem;">Регистрация</h2>

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

            <p style="text-align: center; margin-top: 1.5rem;">
                Уже есть аккаунт? <a href="login.php">Войти</a>
            </p>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <!-- Подключение внешнего скрипта валидации -->
    <script>
        // Передаем базовый URL для API в внешний скрипт
        window.apiBaseUrl = '../';
    </script>
    <script src="../js/validation.js" defer></script>
</body>
</html>
