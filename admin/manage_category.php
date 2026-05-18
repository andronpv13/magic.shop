<?php
/**
 * Управление категориями товаров "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

// Подключаем конфигурацию и функции
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';

// Проверяем права администратора
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!csrf_verify()) {
        echo json_encode(['success' => false, 'message' => 'Ошибка безопасности']);
        exit;
    }

    // Проверяем существование необходимых функций
    $required_functions = ['getCategoryByName', 'addCategory', 'getProductsCountByCategory', 'deleteCategory', 'countCategories'];
    foreach ($required_functions as $func) {
        if (!function_exists($func)) {
            echo json_encode(['success' => false, 'message' => 'Функция не найдена: ' . $func]);
            exit;
        }
    }

    // Обработка добавления категории
    if (isset($_POST['name'])) {
        $name = trim($_POST['name']);

        if (empty($name) || mb_strlen($name) > 50) {
            echo json_encode(['success' => false, 'message' => 'Название категории должно быть от 1 до 50 символов']);
            exit;
        }

        // Проверяем валидность названия категории
        if (!preg_match('/^[а-яёА-ЯЁa-zA-Z0-9\s\-\_\.&()#:\\/]+$/u', $name)) {
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
                if (function_exists('logAction')) {
                    logAction("Добавлена категория: $name");
                }
                echo json_encode(['success' => true, 'category' => $name]);
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
    if (isset($_POST['category'])) {
        $category = trim($_POST['category']);

        if ($category === '') {
            echo json_encode(['success' => false, 'message' => 'Некорректное название категории']);
            exit;
        }

        try {
            $products_count = getProductsCountByCategory($category);
            if ($products_count > 0) {
                echo json_encode(['success' => false, 'message' => 'Невозможно удалить категорию, в которой есть товары']);
                exit;
            }

            $result = deleteCategory($category);

            if ($result['success']) {
                if (function_exists('logAction')) {
                    logAction("Удалена категория: $category");
                }
                echo json_encode(['success' => true, 'message' => 'Категория удалена']);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
        } catch (Exception $e) {
            error_log('Ошибка базы данных: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
        }
        exit;
    }

    // Если ни одно из действий не выполнено
    echo json_encode(['success' => false, 'message' => 'Неверный запрос']);
    exit;
}

// Если это не POST-запрос, показываем страницу управления категориями
$categories = getCategoriesList();

$page_title = 'Управление категориями';

?>

<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/admin/products.php">Управление товарами</a>
        </nav>

        <h1 class="page-title">Управление категориями</h1>

        <div class="category-management">
            <div class="add-category-form">
                <form id="new-category-form">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="form-group">
                        <label for="new-category-name">Название:</label>
                        <input type="text" id="new-category-name" name="name" required>
                    </div>
                    <button type="submit" class="btn btn-outline">Добавить категорию</button>
                </form>
            </div>

            <div class="categories-list-block">
                <h2>Список категорий</h2>
                <?php if (!empty($categories)): ?>
                    <ul class="categories-list" id="categories-list">
                        <?php foreach ($categories as $cat): ?>
                            <li data-category="<?php echo e($cat['category']); ?>">
                                <span class="category-name"><?php echo e($cat['category']); ?></span>
                                <span class="category-count">(<?php echo isset($cat['product_count']) ? (int)$cat['product_count'] : 0; ?>)</span>
                                <button type="button" class="btn btn-sm btn-delete delete-category" data-category="<?php echo e($cat['category']); ?>">
                                    Удалить
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="empty-state" id="empty-state-text">Категории не найдены</p>
                    <ul class="categories-list" id="categories-list"></ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Подключение внешних скриптов -->
<script src="../js/admin/categories.js" defer></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>