<?php
require_once __DIR__ . '/../includes/header.php';

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

        // Валидация телефона
        if (!empty($phone)) {
            $phoneDigits = preg_replace('/\D/', '', $phone);
            if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 15) {
                $errors[] = 'Телефон должен содержать от 10 до 15 цифр';
            }
        }

        // Валидация индекса
        if (!empty($zip_code)) {
            if (!preg_match('/^\d{6}$/', $zip_code)) {
                $errors[] = 'Индекс должен содержать ровно 6 цифр';
            }
        }

        if (!empty($password)) {
            // ✅ ИСПРАВЛЕНО: Добавлена проверка текущего пароля перед сменой
            $current_password_input = $_POST['current_password'] ?? '';
            if (empty($current_password_input)) {
                $errors[] = 'Введите текущий пароль для подтверждения';
            } else {
                // Проверяем текущий пароль
                if (!password_verify($current_password_input, $current_user['password'])) {
                    $errors[] = 'Неверный текущий пароль';
                }
            }
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
            <!-- Форма редактирования -->
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

                <form method="POST" class="edit-profile-form" id="editProfileForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="edit-profile-layout">
                        <!-- Первый столбец: Основные данные -->
                        <div class="edit-profile-column">
                            <h3>Основные данные</h3>

                            <div class="form-group">
                                <label for="username">Логин</label>
                                <input type="text" id="username" name="username" class="form-control"
                                       value="<?php echo e($current_user['username']); ?>" required
                                       data-validate="username">
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?php echo e($current_user['email']); ?>" required
                                       data-validate="email">
                            </div>

                            <div class="form-group">
                                <label for="first_name">Имя</label>
                                <input type="text" id="first_name" name="first_name" class="form-control"
                                       value="<?php echo e($current_user['first_name'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="last_name">Фамилия</label>
                                <input type="text" id="last_name" name="last_name" class="form-control"
                                       value="<?php echo e($current_user['last_name'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="middle_name">Отчество</label>
                                <input type="text" id="middle_name" name="middle_name" class="form-control"
                                       value="<?php echo e($current_user['middle_name'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="phone">Телефон</label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                       value="<?php echo e($current_user['phone'] ?? ''); ?>"
                                       placeholder="+7 (999) 999-99-99"
                                       data-validate="phone">
                            </div>
                        </div>

                        <!-- Второй столбец: Адрес доставки -->
                        <div class="edit-profile-column">
                            <h3>Адрес доставки</h3>
                            <h4>Заполните адрес для оформления доставки</h4>

                            <div class="form-group">
                                <label for="zip_code">Индекс</label>
                                <input type="text" id="zip_code" name="zip_code" class="form-control"
                                       value="<?php echo e($current_user['zip_code'] ?? ''); ?>"
                                       placeholder="123456"
                                       maxlength="6"
                                       data-validate="zip">
                            </div>

                            <div class="form-group">
                                <label for="region">Область</label>
                                <input type="text" id="region" name="region" class="form-control"
                                       value="<?php echo e($current_user['region'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="city">Город</label>
                                <input type="text" id="city" name="city" class="form-control"
                                       value="<?php echo e($current_user['city'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="street">Улица</label>
                                <input type="text" id="street" name="street" class="form-control"
                                       value="<?php echo e($current_user['street'] ?? ''); ?>">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="house">№ Дома</label>
                                    <input type="text" id="house" name="house" class="form-control"
                                           value="<?php echo e($current_user['house'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="apartment">№ Кв.</label>
                                    <input type="text" id="apartment" name="apartment" class="form-control"
                                           value="<?php echo e($current_user['apartment'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Третий столбец: Смена пароля -->
                        <div class="edit-profile-column">
                            <h3>Смена пароля</h3>
                            <h4>Оставьте поля пустыми, чтобы не менять пароль</h4>

                            <div class="form-group">
                                <label for="current_password">Текущий пароль</label>
                                <div class="password-wrapper">
                                    <input type="password" id="current_password" name="current_password"
                                           class="form-control" placeholder="Введите текущий пароль"
                                           data-validate="current_password">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password">Новый пароль</label>
                                <div class="password-wrapper">
                                    <input type="password" id="password" name="password"
                                           class="form-control" placeholder="Минимум 6 символов"
                                           data-validate="password">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password_confirm">Подтверждение пароля</label>
                                <div class="password-wrapper">
                                    <input type="password" id="password_confirm" name="password_confirm"
                                           class="form-control" placeholder="Повторите пароль"
                                           data-validate="confirm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопка сохранения внизу справа -->
                    <div class="edit-profile-actions">
                        <button type="submit" id="saveBtn" class="btn btn-outline">💾 Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script src="/js/validation.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>