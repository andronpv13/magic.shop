<?php
/**
 * magic.shop — Страница входа
 * Универсальная авторизация для всех ролей
 */

// 🔗 Подключение ядра
require_once __DIR__ . '/includes/config.php';

// 🚫 Если уже авторизован — редирект по роли
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect_to('admin/index.php');
    } elseif (isModerator()) {
        redirect_to('moderator/index_md.php');
    } else {
        redirect_to('index.php');
    }
}

// 🔄 Возврат после успешного входа
$return_to = $_SESSION['return_to'] ?? 'index.php';
unset($_SESSION['return_to']);

// 📥 Обработка POST-запроса
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ✅ Проверка CSRF
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Ошибка безопасности (CSRF)';
        log_error('CSRF validation failed on login', 'SECURITY', ['ip' => $_SERVER['REMOTE_ADDR']]);
    } 
    // ✅ Валидация ввода
    elseif (empty($_POST['username']) || empty($_POST['password'])) {
        $error = 'Заполните логин и пароль';
    } 
    // ✅ Проверка rate limit (5 попыток за 15 минут)
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
        $attempts_key = "login_attempts_{$ip}";
        $attempts = $_SESSION[$attempts_key] ?? 0;
        $last_attempt = $_SESSION["{$attempts_key}_time"] ?? 0;
        
        if ($attempts >= 5 && (time() - $last_attempt) < 900) {
            $error = 'Слишком много попыток. Попробуйте позже';
            log_error('Login rate limit exceeded', 'SECURITY', ['ip' => $ip]);
        } else {
            $username = sanitize($_POST['username']);
            $password = $_POST['password'];
            
            // 🔍 Поиск пользователя (подготовленный запрос)
            $stmt = $conn->prepare("SELECT id, username, password, role, failed_attempts, locked_until FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // 🔒 Проверка блокировки
                if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $error = 'Аккаунт временно заблокирован';
                    log_action($user['id'], 'login_blocked', "Попытка входа в заблокированный аккаунт", $log_file);
                }
                // ✅ Проверка пароля
                elseif (password_verify($password, $user['password'])) {
                    // 🎉 Успешный вход
                    
                    // Сброс неудачных попыток
                    $upd = $conn->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?");
                    $upd->bind_param("i", $user['id']);
                    $upd->execute();
                    $upd->close();
                    
                    // 🧩 Установка сессии
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // регенерация
                    
                    log_action($user['id'], 'login_success', "Вход в систему", $log_file);
                    
                    // 🚀 Редирект по роли (используем redirect_to — исправлено!)
                    if ($user['role'] === 'admin') {
                        redirect_to('admin/index.php');
                    } elseif ($user['role'] === 'moderator') {
                        redirect_to('moderator/index_md.php');
                    } else {
                        redirect_to($return_to);
                    }
                    exit;
                    
                } else {
                    // ❌ Неверный пароль
                    $failed = (int)$user['failed_attempts'] + 1;
                    $locked_until = ($failed >= 5) ? date('Y-m-d H:i:s', time() + 900) : null;
                    
                    $upd = $conn->prepare("UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?");
                    $upd->bind_param("isi", $failed, $locked_until, $user['id']);
                    $upd->execute();
                    $upd->close();
                    
                    $error = 'Неверный логин или пароль';
                    log_action($user['id'], 'login_failed', "Неудачная попытка входа (попытка {$failed})", $log_file);
                }
            } else {
                // ❌ Пользователь не найден (не раскрываем, что именно не так)
                $error = 'Неверный логин или пароль';
                // Логирование попытки по несуществующему пользователю
                log_error("Login attempt for unknown user: {$username}", 'SECURITY', ['ip' => $ip]);
            }
            $stmt->close();
            
            // 📊 Обновление счётчика попыток в сессии (для rate limit)
            $_SESSION[$attempts_key] = $attempts + 1;
            $_SESSION["{$attempts_key}_time"] = time();
        }
    }
}

// 🎨 Заголовок страницы
$page_title = 'Вход в систему';
require_once INCLUDES_PATH . '/header.php';
?>

<main class="auth-container">
    <div class="auth-card">
        <h1>🔐 Вход в Волшебную ЛАВКУ</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?= site_url('login.php') ?>" class="auth-form">
            <?= csrf_field() ?>
            
            <div class="form-group">
                <label for="username">Логин или Email</label>
                <input type="text" id="username" name="username" 
                       value="<?= e($_POST['username'] ?? '') ?>" 
                       required autocomplete="username" autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" 
                       required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn btn-primary">Войти</button>
            
            <p class="auth-link">
                Нет аккаунта? <a href="<?= site_url('users/register.php') ?>">Зарегистрироваться</a>
            </p>
        </form>
    </div>
</main>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>