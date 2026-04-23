<?php
/**
 * Управление категориями товаров "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

// Подключаем конфигурацию и функции
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_adm.php';

// Проверяем права администратора
requireAdmin();

// Устанавливаем заголовок JSON
header('Content-Type: application/json');

// Проверяем, что это POST-запрос
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

// Проверка CSRF токена
if (!csrf_verify()) {
    echo json_encode(['success' => false, 'message' => 'Ошибка безопасности']);
    exit;
}

// Проверяем существование необходимых функций
$required_functions = ['getCategoryByName', 'addCategory', 'getProductsCountByCategory', 'deleteCategory', 'getCategoryById'];
foreach ($required_functions as $func) {
    if (!function_exists($func)) {
        echo json_encode(['success' => false, 'message' => 'Функция не найдена: ' . $func]);
        exit;
    }
}

// Обработка добавления категории
if (isset($_POST['name'])) {
    $name = trim($_POST['name']);
    
    // Проверяем длину названия категории
    if (empty($name) || strlen($name) > 10) {
        echo json_encode(['success' => false, 'message' => 'Название категории должно быть от 1 до 10 символов']);
        exit;
    }
    
    // Проверяем валидность названия категории
    if (!preg_match('/^[а-яёА-ЯЁa-zA-Z0-9\s\-_\.&()#:\/]+$/u', $name)) {
        echo json_encode(['success' => false, 'message' => 'Название категории содержит недопустимые символы']);
        exit;
    }
    
    // Проверяем максимальное количество категорий
    $max_categories = 20;
    $current_categories = countCategories();
    if ($current_categories >= $max_categories) {
        echo json_encode(['success' => false, 'message' => 'Достигнуто максимальное количество категорий']);
        exit;
    }
    
    try {
        // Проверяем, существует ли уже такая категория
        $existing_cat = getCategoryByName($name);
        if ($existing_cat) {
            echo json_encode(['success' => false, 'message' => 'Категория с таким названием уже существует']);
            exit;
        }
        
        // Добавляем новую категорию
        $result = addCategory($name);
        
        if ($result['success']) {
            // Логируем действие
            logAction("Добавлена категория: $name");
            echo json_encode(['success' => true, 'id' => $result['id']]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
    } catch (Exception $e) {
        error_log("Ошибка базы данных: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
    }
    exit;
}

// Обработка удаления категории
if (isset($_POST['category_id'])) {
    $category_id = (int)$_POST['category_id'];
    
    if ($category_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Некорректный ID категории']);
        exit;
    }
    
    try {
        // Проверяем, есть ли товары в этой категории
        $products_count = getProductsCountByCategory($category_id);
        if ($products_count > 0) {
            echo json_encode(['success' => false, 'message' => 'Невозможно удалить категорию, в которой есть товары']);
            exit;
        }
        
        // Получаем название категории для логирования
        $category_info = getCategoryById($category_id);
        if (!$category_info) {
            echo json_encode(['success' => false, 'message' => 'Категория не найдена']);
            exit;
        }
        
        // Удаляем категорию
        $result = deleteCategory($category_id);
        
        if ($result['success']) {
            // Логируем действие
            logAction("Удалена категория: " . $category_info['name']);
            echo json_encode(['success' => true, 'message' => 'Категория удалена']);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
    } catch (Exception $e) {
        error_log("Ошибка базы данных: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
    }
    exit;
}

// Если ни одно из действий не выполнено
echo json_encode(['success' => false, 'message' => 'Неверный запрос']);
exit;
