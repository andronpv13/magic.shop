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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 40px 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #4a148c; margin-bottom: 20px; text-align: center; }
        .status { padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .status.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #4caf50; }
        .status.error { background: #ffebee; color: #c62828; border: 1px solid #f44336; }
        .status.warning { background: #fff3e0; color: #e65100; border: 1px solid #ff9800; }
        ul { margin: 10px 0; padding-left: 20px; }
        li { margin: 5px 0; }
        .next-steps { background: #e3f2fd; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .next-steps h3 { color: #1565c0; margin-bottom: 10px; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 4px; font-size: 14px; }
        a { color: #1565c0; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
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

        <p style="text-align: center; margin-top: 20px; color: #666;">
            Разработчик: АВВА © 2025
        </p>
    </div>
</body>
</html>
