<?php
/**
 * Добавление товара "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Добавление товара';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';

requireAdmin();

// Инициализация переменных для сообщений
$success = '';
$error = '';

// Обработка формы добавления товара
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!csrf_verify()) {
        $error = 'Ошибка безопасности: истек срок действия сессии. Попробуйте обновить страницу.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);

        // Логика категорий
        $use_categories = isset($_POST['use_categories']) && $_POST['use_categories'] === '1';
        $category = null;

        if ($use_categories) {
            $category = trim($_POST['category'] ?? '');
            $category_name = trim($_POST['category_name'] ?? '');

            if (!empty($category_name)) {
                $category = $category_name;
            }

            if ($category === '') {
                $category = null;
            }
        } else {
            $category = null;
        }

        $is_new = isset($_POST['is_new']) ? 1 : 0;

        if (empty($name)) {
            $error = 'Укажите название товара';
        } elseif ($price <= 0) {
            $error = 'Укажите корректную цену';
        } else {
            // Загрузка изображения
            $image_path = '';

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadProductImage($_FILES['image']);

                if ($upload_result['success']) {
                    $image_path = $upload_result['filename'];
                } else {
                    $error = $upload_result['message'];
                }
            }

            if (empty($error)) {
                $created_by = $_SESSION['user_id'];
                $result = addProduct($name, $description, $price, $category, $stock, $is_new, $image_path, $created_by);

                if ($result['success']) {
                    $success = 'Товар добавлен';
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

// Получаем список категорий
$categories = getCategoriesList();

// Получаем текущее состояние чекбокса "Использовать категории" из настроек
$use_categories = isset($_SESSION['use_categories']) ? $_SESSION['use_categories'] : false;
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/admin/products.php">Управление товарами</a>
        </nav>
        <h1 class="page-title">Добавление нового товара</h1>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <!-- Форма добавления товара -->
        <div class="add-product-form">
            <form method="POST" enctype="multipart/form-data" class="product-form">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="product-form-layout">
                    <div class="product-form-main">
                        <div class="form-group">
                            <label for="name">Название товара: *</label>
                            <input type="text" id="name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Описание:</label>
                            <textarea id="description" name="description" rows="6"></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Цена (₽): *</label>
                                <input type="number" id="price" name="price" step="0.01" min="0.01" required placeholder="Введите цену">
                            </div>

                            <div class="form-group">
                                <label for="stock">Остаток на складе: *</label>
                                <input type="number" id="stock" name="stock" min="0" required>
                            </div>
                        </div>

                        <!-- Блок управления категориями -->
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="use_categories" name="use_categories" value="1"
                                       onchange="toggleCategoryField(this)" <?php echo $use_categories ? 'checked' : ''; ?>>
                                Использовать категории
                            </label>
                        </div>

                        <div class="form-group" id="category-group" style="<?php echo $use_categories ? '' : 'display: none; opacity: 0.5;'; ?>">
<label for="category">Выберите категорию:</label>
                        <select id="category" name="category"
                               <?php echo $use_categories ? '' : 'disabled'; ?>>
                            <option value="">-- Без категории --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo e($cat['category']); ?>">
                                    <?php echo e($cat['category']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label for="category_name" style="margin-top: 10px; display: block;">Или введите новую:</label>
                            <input type="text" id="category_name" name="category_name"
                                   placeholder="Например: Зелья"
                                   <?php echo $use_categories ? '' : 'disabled'; ?>>
                            <?php if (!$use_categories): ?>
                                <p class="form-hint">Чтобы создать категорию, нужно включить категории галочкой выше</p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_new">
                                Новый товар
                            </label>
                        </div>
                    </div>

                    <div class="product-form-sidebar">
                        <div class="form-group">
                            <label for="image">Изображение:</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <p class="form-hint">JPG, PNG, GIF (макс. 5MB)</p>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-block">Добавить товар</button>
                            <a href="/admin/products.php" class="btn btn-outline btn-block">Отмена</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
    // Функция для переключения поля категории в форме
    function toggleCategoryField(checkbox) {
        const categoryGroup = document.getElementById('category-group');
        const categorySelect = document.getElementById('category');
        const categoryInput = document.getElementById('category_name');

        if (checkbox.checked) {
            categoryGroup.style.display = 'block';
            categoryGroup.style.opacity = '1';
            categorySelect.disabled = false;
            categoryInput.disabled = false;
        } else {
            categoryGroup.style.display = 'none';
            categoryGroup.style.opacity = '0.5';
            categorySelect.value = '';
            categoryInput.value = '';
            categorySelect.disabled = true;
            categoryInput.disabled = true;
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>