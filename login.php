<?php
/**
 * Страница входа "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Вход';
require_once 'includes/config.php';

// Если уже авторизован - редирект на главную
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        // Поиск пользователя
        $stmt = $mysqli->prepare("SELECT id, username, password, role, first_name, last_name FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'Неверный логин или пароль';
        } else {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Успешный вход
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Редирект в зависимости от роли
                switch ($user['role']) {
                    case 'admin':
                        header('Location: /admin/index.php');
                        break;
                    case 'moderator':
                        header('Location: /moderator/index_md.php');
                        break;
                    default:
                        header('Location: /index.php');
                }
                exit;
            } else {
                $error = 'Неверный логин или пароль';
            }
        }
        
        $stmt->close();
    }
}

require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <h1 class="auth-title">Вход в аккаунт</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo e($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="username">Логин или Email:</label>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo e($_POST['username'] ?? ''); ?>" 
                               autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Войти</button>
                </form>

                <div class="auth-footer">
                    <p>Нет аккаунта? <a href="/users/register.php">Зарегистрироваться</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
