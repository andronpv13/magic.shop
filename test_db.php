<?php
/**
 * Тест подключения к базе данных
 */

// Включаем отображение всех ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
$mysqli = new mysqli('localhost', 'root', 'Andronpv13', 'shop_db');

if ($mysqli->connect_error) {
    die("Ошибка подключения к базе данных: " . $mysqli->connect_error);
}

echo "Подключение к базе данных успешно!<br>";

// Проверка существования базы данных
$result = $mysqli->query("SELECT DATABASE()");
$row = $result->fetch_row();
echo "База данных: " . $row[0] . "<br>";

// Проверка существования таблиц
$tables = ['users', 'products', 'categories', 'orders', 'order_items', 'reviews'];
foreach ($tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "Таблица $table существует<br>";
    } else {
        echo "Таблица $table не существует<br>";
    }
}

$mysqli->close();
