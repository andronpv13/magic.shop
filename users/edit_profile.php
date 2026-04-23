<?php
// ВРЕМЕННО: Отключаем редиректы, чтобы видеть ошибки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Редактирование профиля "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */
require_once '../includes/header.php';

// --- НАСТРОЙКИ ПОДКЛЮЧЕНИЯ К БД ---
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'Andronpv13'; 
$db_name = 'shop_db';
// ----------------------------------

if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$page_title = 'Настройки профиля';
$current_user = [];
$errors = [];
$success = false;

// --- ПРОВЕРКА ФЛАГА НОВОЙ РЕГИСТРАЦИИ ---
$show_welcome = isset($_SESSION['just_registered']) && $_SESSION['just_registered'];
if ($show_welcome) {
    // Удаляем флаг, чтобы сообщение показывалось только один раз
    unset($_SESSION['just_registered']);
}
// --------------------------------------

// 1. Подключаемся к БД
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_error) {
    die("Ошибка подключения БД: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

// 2. Получаем текущего пользователя
$uid = (int)$_SESSION['user_id'];
$res = $mysqli->query("SELECT * FROM users WHERE id = $uid");
if ($res) {
    $current_user = $res->fetch_assoc();
} else {
    die("Ошибка получения пользователя: " . $mysqli->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    $zip_code = trim($_POST['zip_code'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $house = trim($_POST['house'] ?? '');
    $apartment = trim($_POST['apartment'] ?? '');

    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($username)) $errors[] = 'Введите логин';
    if (empty($email)) $errors[] = 'Введите email';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный формат Email';
    
    if (!empty($password)) {
        if ($password !== $password_confirm) $errors[] = 'Пароли не совпадают';
        if (strlen($password) < 6) $errors[] = 'Пароль должен быть не менее 6 символов';
    }

    if (empty($errors)) {
        $password_hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : '';

        // --- БЛОК ОБНОВЛЕНИЯ ---
        $fields_to_update = [
            'username' => $username, 'email' => $email, 'last_name' => $last_name,
            'first_name' => $first_name, 'middle_name' => $middle_name, 'phone' => $phone,
            'zip_code' => $zip_code, 'region' => $region, 'city' => $city,
            'street' => $street, 'house' => $house, 'apartment' => $apartment
        ];

        if (!empty($password_hash)) $fields_to_update['password'] = $password_hash;

        $set_parts = [];
        $types = "";
        $params = [];
        
        foreach ($fields_to_update as $key => $value) {
            $set_parts[] = "$key = ?";
            $types .= "s";
            $params[] = $value;
        }
        
        $params[] = $uid;
        $types .= "i";

        $sql = "UPDATE users SET " . implode(', ', $set_parts) . " WHERE id = ?";

        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            $errors[] = "Ошибка подготовки запроса: " . $mysqli->error;
        } else {
            $refs = [];
            foreach ($params as $key => $value) {
                $refs[$key] = &$params[$key];
            }
            array_unshift($refs, $types);
            call_user_func_array([$stmt, 'bind_param'], $refs);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success = true;
                    
                    // --- ОБНОВЛЕНИЕ ДАННЫХ В СЕССИИ ---
                    $_SESSION['user']['username'] = $username;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['user']['first_name'] = $first_name;
                    $_SESSION['user']['last_name'] = $last_name;
                    // --------------------------------

                    // Обновляем локальную переменную для текущей страницы
                    $current_user['username'] = $username;
                    $current_user['email'] = $email;
                    $current_user['first_name'] = $first_name;
                    $current_user['last_name'] = $last_name;
                    $current_user['middle_name'] = $middle_name;
                    $current_user['phone'] = $phone;
                    $current_user['zip_code'] = $zip_code;
                    $current_user['region'] = $region;
                    $current_user['city'] = $city;
                    $current_user['street'] = $street;
                    $current_user['house'] = $house;
                    $current_user['apartment'] = $apartment;

                } else {
                    $errors[] = 'Данные не изменились. Вы ввели те же значения, что уже есть в профиле.';
                }
            } else {
                $errors[] = "Ошибка БД: " . $stmt->error;
            }
            $stmt->close();
        }
        // ------------------------
    }
}
?>

<section class="section">
    <div class="container">
        <div class="profile-layout">
            <!-- Левая колонка: Меню -->
            <div class="profile-section">
                <div class="user-profile-header">
                    <div class="user-avatar-lg">
                        <?php 
                        $name = $current_user['username'] ?? 'User';
                        echo mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8'); 
                        ?>
                    </div>
                    <h2><?php echo e($current_user['first_name'] ?? $current_user['username'] ?? ''); ?></h2>
                    <p class="text-muted"><?php echo e($current_user['username'] ?? ''); ?></p>
                </div>

                <nav class="profile-nav">
                    <a href="/users/profile.php" class="profile-nav-link">
                        <span class="nav-icon">👤</span> Личные данные
                    </a>
                    <a href="/users/orders.php" class="profile-nav-link">
                        <span class="nav-icon">📦</span> История заказов
                    </a>
                    <a href="/users/edit_profile.php" class="profile-nav-link active">
                        <span class="nav-icon">✏️</span> Настройки профиля
                    </a>
                    <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'moderator')): ?>
                        <a href="/admin/index.php" class="profile-nav-link">
                            <span class="nav-icon">⚙️</span> Панель управления
                        </a>
                    <?php endif; ?>
                    <a href="/logout.php" class="profile-nav-link logout">
                        <span class="nav-icon">🚪</span> Выход
                    </a>
                </nav>
            </div>

            <!-- Правая колонка: Форма -->
            <div class="profile-section">
                <h2 class="section-title">Настройки профиля</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <strong>Ошибки:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Данные профиля успешно обновлены!
                    </div>
                <?php endif; ?>

                <?php if ($show_welcome): ?>
                    <div class="alert alert-info" style="margin-bottom: 20px;">
                        <strong>Добро пожаловать в Волшебную ЛАВКУ!</strong><br>
                        Вы успешно зарегистрировались. Пожалуйста, заполните данные профиля, особенно <strong>адрес доставки</strong>, чтобы мы могли отправлять вам заказы.
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="auth-form">
                    <h3>Основные данные</h3>
                    <div class="form-group">
                        <label for="username">Логин</label>
                        <input type="text" id="username" name="username" value="<?php echo e($current_user['username'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo e($current_user['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="last_name">Фамилия</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo e($current_user['last_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="first_name">Имя</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo e($current_user['first_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="middle_name">Отчество</label>
                        <input type="text" id="middle_name" name="middle_name" value="<?php echo e($current_user['middle_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Номер телефона</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo e($current_user['phone'] ?? ''); ?>">
                    </div>

                    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

                    <h3>Адрес доставки</h3>
                    <div class="form-group">
                        <label for="zip_code">Почтовый индекс</label>
                        <input type="text" id="zip_code" name="zip_code" value="<?php echo e($current_user['zip_code'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="region">Область / Регион</label>
                        <input type="text" id="region" name="region" value="<?php echo e($current_user['region'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="city">Населённый пункт</label>
                        <input type="text" id="city" name="city" value="<?php echo e($current_user['city'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="street">Улица</label>
                        <input type="text" id="street" name="street" value="<?php echo e($current_user['street'] ?? ''); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="house">Номер дома</label>
                            <input type="text" id="house" name="house" value="<?php echo e($current_user['house'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="apartment">Номер квартиры</label>
                            <input type="text" id="apartment" name="apartment" value="<?php echo e($current_user['apartment'] ?? ''); ?>">
                        </div>
                    </div>

                    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

                    <h3>Смена пароля</h3>
                    <div class="form-group">
                        <label for="password">Новый пароль (оставьте пустым, если не хотите менять)</label>
                        <input type="password" id="password" name="password">
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Подтверждение пароля</label>
                        <input type="password" id="password_confirm" name="password_confirm">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Сохранить изменения</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
