<?php
/**
 * Скрипт установки базы данных для "Волшебная ЛАВКА"
 * Запустите этот файл через браузер: http://localhost/database/setup.php
 */

// Данные для подключения (измените при необходимости)
$host = 'localhost';
$db_user = 'root';
$db_pass = 'toor';
$db_name = 'shop_db';

// Подключение к MySQL серверу
$conn = new mysqli($host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("Ошибка подключения к MySQL: " . $conn->connect_error);
}

// Чтение SQL файла
$sql_file = __DIR__ . '/schema.sql';
if (!file_exists($sql_file)) {
    die("Файл schema.sql не найден!");
}

$sql = file_get_contents($sql_file);

// Разделение SQL на отдельные запросы
$queries = array_filter(array_map('trim', explode(';', $sql)));

// Выполнение каждого запроса
$errors = [];
$success = [];

foreach ($queries as $query) {
    if (empty($query)) continue;

    if ($conn->query($query)) {
        $success[] = "Выполнен: " . substr($query, 0, 50) . '...';
    } else {
        $errors[] = "Ошибка [" . $conn->error . "]: " . substr($query, 0, 50) . '...';
    }
}

$conn->close();

// Вывод результатов
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка БД - Волшебная ЛАВКА</title>
    <link rel="stylesheet" href="../css/magic.css">
</head>
<body>
    <div class="container">
        <h1>✨ Установка базы данных "Волшебная ЛАВКА"</h1>

        <?php if (empty($errors)): ?>
            <div class="status success">
                <h2>✅ Установка завершена успешно!</h2>
                <p>База данных <code><?php echo htmlspecialchars($db_name); ?></code> создана и настроена.</p>
            </div>
        <?php else: ?>
            <div class="status error">
                <h2>❌ Возникли ошибки при установке</h2>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="status success">
                <h3>Выполненные запросы:</h3>
                <ul>
                    <?php foreach ($success as $s): ?>
                        <li><?php echo htmlspecialchars($s); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="next-steps">
            <h3>Следующие шаги:</h3>
            <ol>
                <li>Проверьте подключение в <code>includes/config.php</code></li>
                <li>Откройте <a href="../index.php">главную страницу</a></li>
                <li>Войдите как администратор:
                    <ul>
                        <li>Логин: <code>root</code></li>
                        <li>Пароль: <code>toor</code></li>
                    </ul>
                </li>
                <li>⚠️ Обязательно смените пароль после первого входа!</li>
            </ol>
        </div>

        <p class="text-center mt-5 text-muted">
            Разработчик: АВВА © 2025
        </p>
    </div>
</body>
</html>