<?php
/**
* Редактирование профиля администратора "Волшебная ЛАВКА"
* Разработчик: АВВА © 2025
*/
$page_title = 'Редактирование профиля - Админ-панель';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$current_user = getCurrentUser();
$errors = [];
$success = false;
global $conn;

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
        $current_password_input = $_POST['current_password'] ?? '';
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

        // Валидация смены пароля
        if (!empty($password)) {
            // Проверка текущего пароля перед сменой
            if (empty($current_password_input)) {
                $errors[] = 'Введите текущий пароль для подтверждения';
            } elseif (preg_match('/[\s\t]/', $current_password_input)) {
                $errors[] = 'Текущий пароль не должен содержать пробелы и символы табуляции';
            } else {
                // Проверяем текущий пароль
                if (!password_verify($current_password_input, $current_user['password'])) {
                    $errors[] = 'Неверный текущий пароль';
                }
            }
            if ($password !== $password_confirm) $errors[] = 'Пароли не совпадают';
            if (strlen($password) < 6) $errors[] = 'Пароль минимум 6 символов';
            // Проверка на наличие пробелов и tab в пароле
            if (preg_match('/[\s\t]/', $password)) {
                $errors[] = 'Пароль не должен содержать пробелы и символы табуляции';
            }
            // Проверка схожести паролей
            function arePasswordsSimilar($newPass, $currPass) {
                $newLower = mb_strtolower($newPass);
                $currLower = mb_strtolower($currPass);

                if ($newLower === $currLower) return true;
                if (mb_strlen($currLower) >= 4 && strpos($newLower, $currLower) !== false) return true;
                if (mb_strlen($newLower) >= 4 && strpos($currLower, $newLower) !== false) return true;

                // Расстояние Левенштейна
                $len1 = mb_strlen($newLower);
                $len2 = mb_strlen($currLower);
                $minLen = min($len1, $len2);
                if ($minLen === 0) return false;

                $matrix = [];
                for ($i = 0; $i <= $len1; $i++) {
                    $matrix[$i] = [$i];
                }
                for ($j = 0; $j <= $len2; $j++) {
                    $matrix[0][$j] = $j;
                }
                for ($i = 1; $i <= $len1; $i++) {
                    for ($j = 1; $j <= $len2; $j++) {
                        $cost = mb_substr($newLower, $i - 1, 1) === mb_substr($currLower, $j - 1, 1) ? 0 : 1;
                        $matrix[$i][$j] = min(
                            $matrix[$i - 1][$j] + 1,
                            $matrix[$i][$j - 1] + 1,
                            $matrix[$i - 1][$j - 1] + $cost
                        );
                    }
                }

                $distance = $matrix[$len1][$len2];
                $threshold = floor($minLen * 0.3);

                return $distance <= $threshold && $distance > 0;
            }

            if (arePasswordsSimilar($password, $current_password_input)) {
                $errors[] = 'Новый пароль слишком похож на текущий. Придумайте более сложный пароль';
            }
        }

        if (empty($errors)) {
            // Проверка уникальности логина и email (кроме текущего пользователя)
            $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->bind_param("ssi", $username, $email, $_SESSION['user_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = 'Логин или Email уже заняты';
            } else {
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, middle_name=?, phone=?, password=? WHERE id=?");
                    $stmt->bind_param("sssssssi", $username, $email, $first_name, $last_name, $middle_name, $phone, $hash, $_SESSION['user_id']);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, middle_name=?, phone=? WHERE id=?");
                    $stmt->bind_param("ssssssi", $username, $email, $first_name, $last_name, $middle_name, $phone, $_SESSION['user_id']);
                }

                if ($stmt->execute()) {
                    $_SESSION['username'] = $username;
                    $success = true;
                    $current_user = getCurrentUser();
                } else {
                    $errors[] = 'Ошибка обновления: ' . $conn->error;
                }
            }
        }
    }
}
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">Редактирование профиля</h1>

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

            <div class="edit-cab-layout">
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

                <!-- Второй столбец: Смена пароля -->
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
</section>

<script src="/js/validation.js"></script>
<script src="/js/admin/users.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>