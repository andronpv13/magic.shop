<?php
/**
* Обновление настроек сайта "Волшебная ЛАВКА"
* Разработчик: АВВА © 2025
*/
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

// Проверка CSRF токена
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Ошибка безопасности']);
    exit;
}

// Обработка изменения использования категорий
if (isset($_POST['use_categories'])) {
    $_SESSION['use_categories'] = $_POST['use_categories'] === '1';
    echo json_encode(['success' => true]);
    exit;
}

// Обработка изменения оформления (фон, фавикон)
if (isset($_FILES['site_background']) || isset($_FILES['site_favicon'])) {
    $upload_dir = __DIR__ . '/../../images/background/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
    $response = ['success' => true, 'message' => 'Настройки обновлены'];

    // Загрузка фона
    if (isset($_FILES['site_background']) && $_FILES['site_background']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['site_background'];
        if (in_array($file['type'], $allowed_types)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'fon.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                $_SESSION['site_background'] = $new_name;
            } else {
                $response['success'] = false;
                $response['message'] = 'Ошибка загрузки фона';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Недопустимый формат фона';
        }
    }

    // Загрузка фавиконки
    if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['site_favicon'];
        if ($file['type'] === 'image/svg+xml' || $file['type'] === 'image/x-icon') {
            $new_name = 'favicon.' . ($file['type'] === 'image/svg+xml' ? 'svg' : 'ico');
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                $_SESSION['site_favicon'] = $new_name;
            } else {
                $response['success'] = false;
                $response['message'] = 'Ошибка загрузки фавиконки';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Фавиконка должна быть SVG или ICO';
        }
    }

    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Неизвестный запрос']);
exit;
?>