<?php
/**
 * Личный кабинет администратора "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Настройки - Админ-панель';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$current_user = getCurrentUser();
$success = '';
$error = '';

// Путь к файлу настроек сайта
$settings_file = __DIR__ . '/../includes/config/site_settings.json';

// Загружаем текущие настройки
$site_settings = [];
if (file_exists($settings_file)) {
    $site_settings = json_decode(file_get_contents($settings_file), true) ?? [];
}

// Обновление профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Проверка CSRF
    if (!csrf_verify()) {
        $error = 'Ошибка безопасности. Попробуйте обновить страницу.';
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($email)) {
            $error = 'Email обязателен';
        } else {
            // Проверка уникальности email
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Этот email уже используется';
            } else {
                $stmt = $mysqli->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success = 'Профиль обновлен';
                    // Обновляем данные пользователя в сессии
                    $_SESSION['user_data']['first_name'] = $first_name;
                    $_SESSION['user_data']['last_name'] = $last_name;
                    $_SESSION['user_data']['email'] = $email;
                    $_SESSION['user_data']['phone'] = $phone;
                    $current_user = getCurrentUser(); // Перезапрашиваем для отображения
                } else {
                    $error = 'Ошибка при обновлении';
                }
            }
            $stmt->close();
        }
    }
}

// Смена пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Проверка CSRF
    if (!csrf_verify()) {
        $error = 'Ошибка безопасности. Попробуйте обновить страницу.';
    } else {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Заполните все поля';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Пароли не совпадают';
        } elseif (strlen($new_password) < 6) {
            $error = 'Пароль должен быть не менее 6 символов';
        } else {
            // Проверка старого пароля
            $stmt = $mysqli->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!password_verify($old_password, $user['password'])) {
                $error = 'Неверный старый пароль';
            } else {
                $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $password_hash, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success = 'Пароль изменен';
                } else {
                    $error = 'Ошибка при смене пароля';
                }
                $stmt->close();
            }
        }
    }
}

// Настройки сайта (фон, шрифт, логотип)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_site_settings'])) {
    // Проверка CSRF
    if (!csrf_verify()) {
        $error = 'Ошибка безопасности. Попробуйте обновить страницу.';
    } else {
        $site_background = trim($_POST['site_background'] ?? '');
        $site_font = trim($_POST['site_font'] ?? '');
        
        // Загрузка логотипа
        $logo_path = $site_settings['logo_path'] ?? ''; // Сохраняем старый логотип, если новый не загружен
        
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../images/background/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $error = 'Недопустимый формат логотипа';
            } elseif ($_FILES['site_logo']['size'] > $max_size) {
                $error = 'Логотип слишком большой (макс. 2MB)';
            } else {
                // Генерируем безопасное имя файла
                $new_filename = 'logo_' . md5(time() . $_FILES['site_logo']['name']) . '.' . $file_extension;
                $destination = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $destination)) {
                    // Удаляем старый логотип, если он был
                    if (!empty($site_settings['logo_path']) && file_exists(__DIR__ . '/../images/' . $site_settings['logo_path'])) {
                        unlink(__DIR__ . '/../images/' . $site_settings['logo_path']);
                    }
                    $logo_path = 'background/' . $new_filename;
                } else {
                    $error = 'Ошибка при загрузке файла';
                }
            }
        }
        
        if (empty($error)) {
            // Сохраняем настройки в JSON файл
            $settings_to_save = [
                'background' => $site_background,
                'font' => $site_font,
                'logo_path' => $logo_path
            ];
            
            if (file_put_contents($settings_file, json_encode($settings_to_save))) {
                $success = 'Настройки сайта обновлены';
                $site_settings = $settings_to_save; // Обновляем для отображения
            } else {
                $error = 'Не удалось сохранить настройки (проверьте права доступа к папке includes/config)';
            }
        }
    }
}
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="index.php">Админ-панель</a>
            <span class="separator">/</span>
            <span class="current">Настройки</span>
        </nav>

        <h1 class="page-title">Настройки профиля</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="settings-grid">
            <!-- Редактирование профиля -->
            <div class="settings-card">
                <h2>Профиль</h2>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="form-group">
                        <label for="username">Логин:</label>
                        <input type="text" id="username" value="<?php echo e($current_user['username']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">Имя:</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo e($current_user['first_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Фамилия:</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo e($current_user['last_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo e($current_user['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Телефон:</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo e($current_user['phone'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">Сохранить</button>
                </form>
            </div>

            <!-- Смена пароля -->
            <div class="settings-card">
                <h2>Смена пароля</h2>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="form-group">
                        <label for="old_password">Старый пароль:</label>
                        <input type="password" id="old_password" name="old_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Новый пароль:</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Подтверждение:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">Изменить пароль</button>
                </form>
            </div>

            <!-- Настройки сайта -->
            <div class="settings-card">
                <h2>Оформление сайта</h2>
                <form method="POST" enctype="multipart/form-data" class="settings-form">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="form-group">
                        <label for="site_background">Фон сайта (CSS color или URL):</label>
                        <input type="text" id="site_background" name="site_background" 
                               value="<?php echo e($site_settings['background'] ?? ''); ?>"
                               placeholder="#ffffff или url(/images/background/fon.jpg)">
                    </div>
                    
                    <div class="form-group">
                        <label for="site_font">Шрифт сайта:</label>
                        <input type="text" id="site_font" name="site_font" 
                               value="<?php echo e($site_settings['font'] ?? ''); ?>"
                               placeholder="Arial, sans-serif">
                    </div>
                    
                    <div class="form-group">
                        <label for="site_logo">Логотип:</label>
                        <input type="file" id="site_logo" name="site_logo" accept="image/*">
                        <?php if (!empty($site_settings['logo_path'])): ?>
                            <div class="current-logo">
                                <p>Текущий логотип:</p>
                                <img src="/images/<?php echo e($site_settings['logo_path']); ?>" alt="Логотип" style="max-height: 50px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" name="update_site_settings" class="btn btn-primary">Обновить оформление</button>
                </form>
            </div>
        </div>

        <a href="index.php" class="back-link">← Назад в панель</a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
