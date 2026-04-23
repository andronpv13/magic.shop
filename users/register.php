<?php
// users/register.php
$page_title = 'Регистрация';

// 1. Подключаем конфиг и функции
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 2. Если уже вошел, перенаправляем на главную
if (isLoggedIn()) {
    header("Location: /index.php");
    exit;
}

$message = '';
$msgType = '';

// 3. Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Валидация на уровне PHP
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
        // Вызываем функцию регистрации
        $result = registerUser($username, $email, $password);
        
        if ($result['success']) {
            // --- ИЗМЕНЕНО: Редирект на редактирование профиля ---
            $_SESSION['just_registered'] = true;
            header("Location: /users/edit_profile.php");
            exit;
            // ------------------------------------------------
        } else {
            $message = $result['message'];
            $msgType = 'error';
        }
    }
}

require_once '../includes/header.php';
?>

    <script src="/js/validation.js" onerror="console.error('Ошибка загрузки register.js:', event);"></script>

<!-- Основной контент -->
<section class="section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Регистрация</h1>
                    <p class="auth-subtitle">Создайте аккаунт для покупок</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $msgType; ?>">
                        <?php echo e($message); ?>
                    </div>
                <?php endif; ?>

                <form action="/users/register.php" method="POST" class="auth-form" id="registerForm">
                    <div class="form-group">
                        <label for="username">Логин</label>
                        <div class="input-with-feedback">
                            <input type="text" id="username" name="username" 
                                   class="form-control" 
                                   value="<?php echo e($username ?? ''); ?>" 
                                   placeholder="Введите желаемый логин" 
                                   required autofocus>
                            <span class="input-status-icon"></span>
                        </div>
                        <small class="feedback-message"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-with-feedback">
                            <input type="email" id="email" name="email" 
                                   class="form-control" 
                                   placeholder="example@mail.com" 
                                   required>
                            <span class="input-status-icon"></span>
                        </div>
                        <small class="feedback-message"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" 
                               class="form-control" 
                               placeholder="Введите пароль (минимум 6 символов)" 
                               required 
                               autocomplete="off">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Подтвердите пароль</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-control" 
                               placeholder="Повторите пароль" 
                               required 
                               autocomplete="off">
                    </div>

                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <button type="submit" class="btn btn-primary btn-block btn-lg">Зарегистрироваться</button>
                </form>
                
                <div class="auth-footer">
                    <p>Уже есть аккаунт? <a href="../login.php">Войти</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Стили для иконок и сообщений -->
<style>
    .input-with-feedback {
        position: relative !important;
    }
    
    .input-status-icon {
        position: absolute !important;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        
        visibility: hidden !important;
        opacity: 0 !important;
        
        pointer-events: none; 
        z-index: 9999 !important;
        border-radius: 50%;
        padding: 2px;
        box-sizing: border-box;
    }
    
    .input-status-icon.success {
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    .input-status-icon.error {
        visibility: visible !important;
        opacity: 1 !important;
    }

    .feedback-message {
        display: block;
        margin-top: 5px;
        min-height: 18px;
        font-size: 0.85rem;
    }
    
    .feedback-message.error {
        color: #dc3545;
    }
    
    /* Добавляем отступ справа */
    .input-with-feedback input {
        padding-right: 45px !important; 
    }
    
    .form-control.is-invalid {
        border-color: #dc3545 !important;
    }
    
    .form-control.is-valid {
        border-color: #28a745 !important;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
