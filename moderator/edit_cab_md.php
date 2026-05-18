<?php
/**
* Редактирование профиля модератора "Волшебная ЛАВКА"
* Разработчик: АВВА © 2025
*/
$page_title = 'Редактирование профиля - Панель модератора';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_md.php';
requireModerator();

$current_user = getCurrentUser();
$success = '';
$error = '';
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $current_password_input = $_POST['current_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    if (empty($username) || empty($email)) {
        $error = 'Логин и Email обязательны';
    } elseif (!empty($password)) {
        // Проверка пароля на пробелы и табуляцию
        if (preg_match('/[\s\t]/', $password)) {
            $error = 'Пароль не должен содержать пробелы и табуляцию';
        } elseif (strlen($password) < 6) {
            $error = 'Пароль должен быть не менее 6 символов';
        } elseif ($password !== $password_confirm) {
            $error = 'Пароли не совпадают';
        } else {
            // Проверка текущего пароля
            if (empty($current_password_input)) {
                $error = 'Введите текущий пароль';
            } elseif (preg_match('/[\s\t]/', $current_password_input)) {
                $error = 'Текущий пароль не должен содержать пробелы и табуляцию';
            } elseif (!password_verify($current_password_input, $current_user['password'])) {
                $error = 'Неверный текущий пароль';
            } else {
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
                    $error = 'Новый пароль слишком похож на текущий. Придумайте более сложный пароль';
                } else {
                    // Проверка уникальности логина и email (кроме текущего пользователя)
                    $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                    $stmt->bind_param("ssi", $username, $email, $_SESSION['user_id']);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $error = 'Логин или Email уже заняты';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, password=? WHERE id=?");
                        $stmt->bind_param("sssssi", $username, $email, $first_name, $last_name, $hash, $_SESSION['user_id']);

                        if ($stmt->execute()) {
                            $_SESSION['username'] = $username;
                            $success = 'Профиль успешно обновлен';
                            $current_user = getCurrentUser();
                        } else {
                            $error = 'Ошибка при обновлении профиля';
                        }
                    }
                }
            }
        }
    } else {
        // Пароль не меняется - просто обновляем данные
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=? WHERE id=?");
        $stmt->bind_param("ssssi", $username, $email, $first_name, $last_name, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            $success = 'Профиль успешно обновлен';
            $current_user = getCurrentUser();
        } else {
            $error = 'Ошибка при обновлении профиля';
        }
    }
}
?>
<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="index_md.php">Панель модератора</a>
            <span class="separator">/</span>
            <span class="current">Редактирование профиля</span>
        </nav>
        <h1 class="page-title">Редактирование профиля</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form" id="editCabMdForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="username" value="<?php echo e($current_user['username']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo e($current_user['email']); ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Имя</label>
                    <input type="text" name="first_name" value="<?php echo e($current_user['first_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Фамилия</label>
                    <input type="text" name="last_name" value="<?php echo e($current_user['last_name'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Текущий пароль (обязательно при смене пароля)</label>
                <input type="password" id="current_password" name="current_password">
            </div>
            <div class="form-group">
                <label>Новый пароль (оставьте пустым, чтобы не менять)</label>
                <input type="password" id="password" name="password">
            </div>
            <div class="form-group">
                <label>Подтверждение нового пароля</label>
                <input type="password" id="password_confirm" name="password_confirm">
            </div>
            <button type="submit" id="saveBtn" class="btn btn-outline">💾 Сохранить изменения</button>
        </form>
    </div>
</section>
<script src="../js/validation.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>