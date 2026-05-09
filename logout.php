<?php
/**
 * Выход из системы "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */
require_once __DIR__ . '/includes/config.php';
session_unset();
session_destroy();

header('Location: /index.php');
exit;
