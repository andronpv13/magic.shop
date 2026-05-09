<?php
/**
 * Выход из системы "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */
require_once __DIR__ . '/includes/config.php';

// Очищаем все данные сессии
session_unset();
// Уничтожаем сессию
session_destroy();

// Регенерируем сессию для защиты от фиксации сессии
session_start();
session_regenerate_id(true);

// Перенаправляем на главную страницу
header('Location: /index.php');
exit;
