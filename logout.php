<?php
/**
 * Выход из системы "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

session_start();
session_unset();
session_destroy();

header('Location: /index.php');
exit;
