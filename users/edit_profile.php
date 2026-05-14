<?php
<<<<<<< HEAD
require_once __DIR__ . '/../includes/header.php';
=======
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
>>>>>>> 17aa9fe80430601b55ac05d1a95d326b8163eefa

if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$page_title = 'Настройки профиля';
global $conn;
$uid = (int)$_SESSION['user_id'];

// Получаем данные пользователя
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!csrf_verify()) {
        $errors[] = 'Ошибка безопасности (CSRF)';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
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
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный Email';

        if (!empty($password)) {
            if ($password !== $password_confirm) $errors[] = 'Пароли не совпадают';
            if (strlen($password) < 6) $errors[] = 'Пароль минимум 6 символов';
        }

        if (empty($errors)) {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // Запрос с обновлением пароля
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, middle_name=?, phone=?, zip_code=?, region=?, city=?, street=?, house=?, apartment=?, password=? WHERE id=?");
                $stmt->bind_param("sssssssssssssi", $username, $email, $first_name, $last_name, $middle_name, $phone, $zip_code, $region, $city, $street, $house, $apartment, $hash, $uid);
            } else {
                // Запрос без обновления пароля
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, middle_name=?, phone=?, zip_code=?, region=?, city=?, street=?, house=?, apartment=? WHERE id=?");
                $stmt->bind_param("ssssssssssssi", $username, $email, $first_name, $last_name, $middle_name, $phone, $zip_code, $region, $city, $street, $house, $apartment, $uid);
            }

            if ($stmt->execute()) {
                $_SESSION['username'] = $username;
                // Обновляем локальные данные для отображения
                $current_user = array_merge($current_user, [
                    'username' => $username, 'email' => $email,
                    'first_name' => $first_name, 'last_name' => $last_name, 'middle_name' => $middle_name,
                    'phone' => $phone, 'zip_code' => $zip_code, 'region' => $region,
                    'city' => $city, 'street' => $street, 'house' => $house, 'apartment' => $apartment
                ]);
                $success = true;
            } else {
                $errors[] = 'Ошибка обновления: ' . $conn->error;
            }
        }
    }
}
?>
<section class="section">
    <div class="container">
        <div class="profile-layout">
            <!-- Меню -->
            <div class="profile-section">
                <div class="user-profile-header">
                    <h2><?php echo e($current_user['first_name'] ?? $current_user['username']); ?></h2>
                    <p><?php echo e($current_user['username']); ?></p>
                </div>
                <nav class="profile-nav">
                    <a href="/users/profile.php" class="profile-nav-link">👤 Личные данные</a>
                    <a href="/users/orders.php" class="profile-nav-link">📦 История заказов</a>
                    <a href="/users/edit_profile.php" class="profile-nav-link active">✏️ Настройки профиля</a>
                    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'moderator'])): ?>
                        <a href="/admin/index.php" class="profile-nav-link">⚙️ Панель управления</a>
                    <?php endif; ?>
                    <a href="/logout.php" class="profile-nav-link logout">🚪 Выход</a>
                </nav>
            </div>

            <!-- Форма -->
            <div class="profile-section">
                <h2 class="section-title">Настройки профиля</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul><?php foreach ($errors as $err) echo "<li>$err</li>"; ?></ul>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">Данные успешно обновлены!</div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <h3>Основные данные</h3>
                    <div class="form-group"><label>Логин</label><input type="text" name="username" value="<?php echo e($current_user['username']); ?>" required></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo e($current_user['email']); ?>" required></div>

                    <div class="form-row">
                        <div class="form-group"><label>Имя</label><input type="text" name="first_name" value="<?php echo e($current_user['first_name'] ?? ''); ?>"></div>
                        <div class="form-group"><label>Фамилия</label><input type="text" name="last_name" value="<?php echo e($current_user['last_name'] ?? ''); ?>"></div>
                    </div>
                    <div class="form-group"><label>Отчество</label><input type="text" name="middle_name" value="<?php echo e($current_user['middle_name'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Телефон</label><input type="tel" name="phone" value="<?php echo e($current_user['phone'] ?? ''); ?>"></div>

                    <h3>Адрес доставки</h3>
                    <h4>Пожалуйста заполните ваш адрес для возможности оформления доставки</h4>
                    <div class="form-row">
                        <div class="form-group"><label>Индекс</label><input type="text" name="zip_code" value="<?php echo e($current_user['zip_code'] ?? ''); ?>"></div>
                        <div class="form-group"><label>Область</label><input type="text" name="region" value="<?php echo e($current_user['region'] ?? ''); ?>"></div>
                    </div>
                    <div class="form-group"><label>Город</label><input type="text" name="city" value="<?php echo e($current_user['city'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Улица</label><input type="text" name="street" value="<?php echo e($current_user['street'] ?? ''); ?>"></div>
                    <div class="form-row">
<<<<<<< HEAD
                        <div class="form-group"><label>№ Дома</label><input type="text" name="house" value="<?php echo e($current_user['house'] ?? ''); ?>"></div>
                        <div class="form-group"><label>№ Кв.</label><input type="text" name="apartment" value="<?php echo e($current_user['apartment'] ?? ''); ?>"></div>
=======
                        <div class="form-group"><label>Дом</label><input type="text" name="house" value="<?php echo e($current_user['house'] ?? ''); ?>"></div>
                        <div class="form-group"><label>кв.</label><input type="text" name="apartment" value="<?php echo e($current_user['apartment'] ?? ''); ?>"></div>
>>>>>>> 17aa9fe80430601b55ac05d1a95d326b8163eefa
                    </div>

                    <h3>Смена пароля</h3>
                    <h4>Оставьте поля пустыми, чтобы не менять пароль</h4>
                    <div class="form-group"><label>Новый пароль</label><input type="password" name="password"><label>Подтверждение</label><input type="password" name="password_confirm"></div>

                    <button type="submit" class="btn btn-primary btn-block">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</section>
<<<<<<< HEAD
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
=======
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
>>>>>>> 17aa9fe80430601b55ac05d1a95d326b8163eefa
