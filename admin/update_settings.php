<?php
/**
 * Обновление настроек "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';

requireAdmin();

// Обработка обновления настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!csrf_verify()) {
        echo json_encode(['success' => false, 'message' => 'Ошибка безопасности']);
        exit;
    }
    
    // Обновление настройки использования категорий
    if (isset($_POST['use_categories'])) {
        $_SESSION['use_categories'] = $_POST['use_categories'] === '1';
        // Не перезагружаем страницу, а просто возвращаем успех
        echo json_encode(['success' => true]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
    exit;
}

// Если запрос не POST, просто выходим (ничего не делаем)
exit;
