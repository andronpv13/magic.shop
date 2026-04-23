<?php
/**
 * Конфигурация "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

// Включаем отображение всех ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Настройки подключения к базе данных
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Andronpv13');
define('DB_NAME', 'shop_db');

// Пути к файлам
define('ROOT_PATH', __DIR__);
define('INCLUDES_PATH', __DIR__ . '/../includes');
define('CSS_PATH', __DIR__ . '/../css');
define('JS_PATH', __DIR__ . '/../js');
define('ADMIN_PATH', __DIR__ . '/../admin');
define('MODERATOR_PATH', __DIR__ . '/../moderator');
define('IMAGES_PATH', __DIR__ . '/../images');

// Настройки сайта
define('SITE_NAME', 'Волшебная ЛАВКА');
define('SITE_EMAIL', 'info@magic.shop');
define('SITE_PHONE', '+7 (999) 123-45-67');

// Сессия
session_start();
