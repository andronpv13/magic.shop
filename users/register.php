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
                            <div class="input-wrapper">
                                <input type="text" id="username" name="username" class="form-control"
                                       value="<?php echo e($_POST['username'] ?? ''); ?>" required
                                       minlength="4" maxlength="10" pattern="[A-Za-zА-Яа-яЁё]+"
                                       autocomplete="off">
                                <span id="username-status" class="status-icon"></span>
                            </div>
                            <small id="username-feedback" class="feedback-message"></small>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <div class="input-wrapper">
                                <input type="email" id="email" name="email" class="form-control" required autocomplete="off">
                                <span id="email-status" class="status-icon"></span>
                            </div>
                            <small id="email-feedback" class="feedback-message"></small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Пароль</label>
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" class="form-control" required minlength="6">
                                <button type="button" class="password-toggle" data-target="password" aria-label="Показать пароль">👁️</button>
                                <span id="password-status" class="status-icon"></span>
                            </div>
                            <small id="password-feedback" class="feedback-message"></small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Повторите пароль</label>
                            <div class="input-wrapper">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Показать пароль">👁️</button>
                                <span id="confirm-password-status" class="status-icon"></span>
                            </div>
                            <small id="confirm-password-feedback" class="feedback-message"></small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn" disabled>Создать аккаунт</button>
                </form>

                <div class="auth-footer">
                    <p>Уже есть аккаунт? <a href="../login.php">Войти</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Скрипт валидации -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');

    // Элементы статусов
    const usernameStatus = document.getElementById('username-status');
    const emailStatus = document.getElementById('email-status');
    const passwordStatus = document.getElementById('password-status');
    const confirmPasswordStatus = document.getElementById('confirm-password-status');

    // Элементы сообщений
    const usernameFeedback = document.getElementById('username-feedback');
    const emailFeedback = document.getElementById('email-feedback');
    const passwordFeedback = document.getElementById('password-feedback');
    const confirmPasswordFeedback = document.getElementById('confirm-password-feedback');

    // Флаги валидности
    let isUsernameValid = false;
    let isEmailValid = false;
    let isPasswordValid = false;
    let isConfirmPasswordValid = false;

    // Debounce функция для AJAX запросов
    function debounce(func, delay) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // Проверка логина
    const checkUsername = debounce(function() {
        const value = usernameInput.value.trim();

        // Сброс состояния
        usernameStatus.className = 'status-icon';
        usernameFeedback.className = 'feedback-message';
        usernameFeedback.textContent = '';

        // Проверка длины
        if (value.length === 0) {
            isUsernameValid = false;
            updateSubmitButton();
            return;
        }

        if (value.length < 4) {
            usernameStatus.textContent = '✗';
            usernameStatus.classList.add('invalid');
            usernameFeedback.textContent = 'Минимум 4 символа';
            usernameFeedback.classList.add('invalid');
            isUsernameValid = false;
            updateSubmitButton();
            return;
        }

        if (value.length > 10) {
            usernameStatus.textContent = '✗';
            usernameStatus.classList.add('invalid');
            usernameFeedback.textContent = 'Максимум 10 символов';
            usernameFeedback.classList.add('invalid');
            isUsernameValid = false;
            updateSubmitButton();
            return;
        }

        // Проверка на буквы без пробелов и спецсимволов
        const letterPattern = /^[A-Za-zА-Яа-яЁё]+$/;
        if (!letterPattern.test(value)) {
            usernameStatus.textContent = '✗';
            usernameStatus.classList.add('invalid');
            usernameFeedback.textContent = 'Только буквы, без пробелов и спецсимволов';
            usernameFeedback.classList.add('invalid');
            isUsernameValid = false;
            updateSubmitButton();
            return;
        }

        // AJAX проверка на существование
        fetch('/users/check_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'type=username&value=' + encodeURIComponent(value)
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                usernameStatus.textContent = '✗';
                usernameStatus.classList.add('invalid');
                usernameFeedback.textContent = 'Логин уже занят';
                usernameFeedback.classList.add('invalid');
                isUsernameValid = false;
            } else {
                usernameStatus.textContent = '✓';
                usernameStatus.classList.add('valid');
                usernameFeedback.textContent = 'Логин доступен';
                usernameFeedback.classList.add('valid');
                isUsernameValid = true;
            }
            updateSubmitButton();
        })
        .catch(error => {
            console.error('Ошибка проверки логина:', error);
            isUsernameValid = false;
            updateSubmitButton();
        });
    }, 500);

    // Проверка email
    const checkEmail = debounce(function() {
        const value = emailInput.value.trim();

        // Сброс состояния
        emailStatus.className = 'status-icon';
        emailFeedback.className = 'feedback-message';
        emailFeedback.textContent = '';

        if (value.length === 0) {
            isEmailValid = false;
            updateSubmitButton();
            return;
        }

        // Проверка формата email
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(value)) {
            emailStatus.textContent = '✗';
            emailStatus.classList.add('invalid');
            emailFeedback.textContent = 'Некорректный формат email';
            emailFeedback.classList.add('invalid');
            isEmailValid = false;
            updateSubmitButton();
            return;
        }

        // AJAX проверка на существование
        fetch('/users/check_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'type=email&value=' + encodeURIComponent(value)
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                emailStatus.textContent = '✗';
                emailStatus.classList.add('invalid');
                emailFeedback.textContent = 'Email уже зарегистрирован';
                emailFeedback.classList.add('invalid');
                isEmailValid = false;
            } else {
                emailStatus.textContent = '✓';
                emailStatus.classList.add('valid');
                emailFeedback.textContent = 'Email доступен';
                emailFeedback.classList.add('valid');
                isEmailValid = true;
            }
            updateSubmitButton();
        })
        .catch(error => {
            console.error('Ошибка проверки email:', error);
            isEmailValid = false;
            updateSubmitButton();
        });
    }, 500);

    // Проверка пароля
    function checkPassword() {
        const value = passwordInput.value;

        // Сброс состояния
        passwordStatus.className = 'status-icon';
        passwordFeedback.className = 'feedback-message';
        passwordFeedback.textContent = '';

        if (value.length === 0) {
            isPasswordValid = false;
            checkConfirmPassword(); // Перепроверка подтверждения
            updateSubmitButton();
            return;
        }

        if (value.length < 6) {
            passwordStatus.textContent = '✗';
            passwordStatus.classList.add('invalid');
            passwordFeedback.textContent = 'Минимум 6 символов';
            passwordFeedback.classList.add('invalid');
            isPasswordValid = false;
        } else {
            passwordStatus.textContent = '✓';
            passwordStatus.classList.add('valid');
            passwordFeedback.textContent = '';
            passwordFeedback.classList.add('valid');
            isPasswordValid = true;
        }

        checkConfirmPassword(); // Перепроверка подтверждения пароля
        updateSubmitButton();
    }

    // Проверка подтверждения пароля
    function checkConfirmPassword() {
        const passwordValue = passwordInput.value;
        const confirmValue = confirmPasswordInput.value;

        // Сброс состояния
        confirmPasswordStatus.className = 'status-icon';
        confirmPasswordFeedback.className = 'feedback-message';
        confirmPasswordFeedback.textContent = '';

        if (confirmValue.length === 0) {
            isConfirmPasswordValid = false;
            updateSubmitButton();
            return;
        }

        if (passwordValue !== confirmValue) {
            confirmPasswordStatus.textContent = '✗';
            confirmPasswordStatus.classList.add('invalid');
            confirmPasswordFeedback.textContent = 'Пароли не совпадают';
            confirmPasswordFeedback.classList.add('invalid');
            isConfirmPasswordValid = false;
        } else {
            confirmPasswordStatus.textContent = '✓';
            confirmPasswordStatus.classList.add('valid');
            confirmPasswordFeedback.textContent = '';
            confirmPasswordFeedback.classList.add('valid');
            isConfirmPasswordValid = true;
        }

        updateSubmitButton();
    }

    // Обновление кнопки отправки
    function updateSubmitButton() {
        if (isUsernameValid && isEmailValid && isPasswordValid && isConfirmPasswordValid) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }

    // Обработчики событий
    usernameInput.addEventListener('input', checkUsername);
    emailInput.addEventListener('input', checkEmail);
    passwordInput.addEventListener('input', checkPassword);
    confirmPasswordInput.addEventListener('input', checkConfirmPassword);

    // Переключатель видимости пароля
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);

            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = '🙈';
            } else {
                input.type = 'password';
                this.textContent = '👁️';
            }
        });
    });
});
</script>

<!-- Стили для валидации -->
<style>
    .form-row { display: flex; gap: 1rem; }
    .form-row .form-group { flex: 1; }

    /* Контейнер поля ввода с иконкой */
    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-wrapper input {
        width: 100%;
        padding-right: 3.5rem;
    }

    /* Иконка статуса (галочка/крестик) */
    .status-icon {
        position: absolute;
        right: 10px;
        font-size: 1.2rem;
        pointer-events: none;
    }

    .status-icon.valid {
        color: #32CD32;
    }

    .status-icon.invalid {
        color: #DC143C;
    }

    /* Кнопка просмотра пароля (глаз) */
    .password-toggle {
        position: absolute;
        right: 35px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.1rem;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #666;
    }

    .password-toggle:hover {
        color: #333;
    }

    /* Сообщение обратной связи */
    .feedback-message {
        display: block;
        margin-top: 0.3rem;
        font-size: 0.85rem;
        min-height: 1.2em;
    }

    .feedback-message.valid {
        color: #32CD32;
    }

    .feedback-message.invalid {
        color: #DC143C;
    }

    @media (max-width: 600px) {
        .form-row { flex-direction: column; gap: 0; }
        .password-toggle {
            right: 30px;
        }
        .input-wrapper input {
            padding-right: 3rem;
        }
    }
</style>
