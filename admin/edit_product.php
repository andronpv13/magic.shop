<?php
/**
 * Редактирование товара "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Редактировать товар - Админ-панель';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';

requireAdmin();

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: products.php');
    exit;
}

$product = getProductById($product_id);

if (!$product) {
    echo '<div class="container section"><p class="empty-state">Товар не найден</p></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$success = '';
$error = '';

// Определяем состояние чекбокса категорий
// Если у товара есть категория, включаем чекбокс
$use_categories = !empty($product['category']);

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
            // Загрузка нового изображения (если есть)
            $image_path = null; // По умолчанию null - оставляем старое изображение

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadProductImage($_FILES['image']);

                if ($upload_result['success']) {
                    // Удаляем старое изображение, если оно было
                    if (!empty($product['image'])) {
                        $old_image_path = __DIR__ . '/../images/' . $product['image'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                    $image_path = $upload_result['filename'];
                } else {
                    $error = $upload_result['message'];
                }
            }

            if (empty($error)) {
                $result = editProduct($product_id, $name, $description, $price, $category, $stock, $is_new, $image_path);

                if ($result['success']) {
                    $success = 'Товар обновлен';
                    // Обновляем данные товара для отображения актуальной информации
                    $product = getProductById($product_id);
                    // Обновляем состояние чекбокса после сохранения
                    $use_categories = !empty($product['category']);
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

// Получаем список категорий
$all_categories = getCategoriesList();
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="index.php">Админ-панель</a>
            <span class="separator">/</span>
            <a href="products.php">Товары</a>
            <span class="separator">/</span>
            <span class="current">Редактирование</span>
        </nav>

        <h1 class="page-title">Редактирование товара</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="product-form">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="product-form-layout">
                <div class="product-form-main">
                    <div class="form-group">
                        <label for="name">Название товара: *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo e($product['name']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Описание:</label>
                        <textarea id="description" name="description" rows="6"><?php echo e($product['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Цена (₽): *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0.01" required
                                   value="<?php echo e($product['price']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="stock">Остаток на складе: *</label>
                            <input type="number" id="stock" name="stock" min="0" required
                                   value="<?php echo e($product['stock']); ?>">
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

                    <div class="form-group" id="category-group" class="<?php echo $use_categories ? '' : 'hidden'; ?>">
                        <label for="category">Выберите категорию:</label>
                        <select id="category" name="category"
                               <?php echo $use_categories ? '' : 'disabled'; ?>>
                            <option value="">-- Без категории --</option>
                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?php echo e($cat['category']); ?>"
                                        <?php echo ($product['category'] ?? '') === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo e($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="category_name" class="form-label">Или введите новую:</label>
                        <input type="text" id="category_name" name="category_name"
                               placeholder="Например: Зелья"
                               <?php echo $use_categories ? '' : 'disabled'; ?>>
                        <?php if (!$use_categories): ?>
                            <p class="form-hint">Чтобы создать категорию, нужно включить категории галочкой выше</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_new" <?php echo $product['is_new'] ? 'checked' : ''; ?>>
                            Новый товар
                        </label>
                    </div>
                </div>

                <div class="product-form-sidebar">
                    <div class="form-group">
                        <label>Текущее изображение:</label>
                        <?php if ($product['image']): ?>
                            <div class="current-image">
                                <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                            </div>
                        <?php else: ?>
                            <p class="form-hint">Изображение не загружено</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="image">Новое изображение:</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <p class="form-hint">JPG, PNG, GIF (макс. 5MB)</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-block">Сохранить</button>
                        <a href="products.php" class="btn btn-outline btn-block">Отмена</a>
                    </div>
                </div>
            </div>
        </form>

        <!-- Удаление товара -->
        <div class="delete-product-section">
            <h3>Удалить товар</h3>
            <p>Это действие нельзя отменить</p>
            <form method="POST" action="products.php" onsubmit="return confirm('Вы уверены, что хотите удалить этот товар?');">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="delete_product" value="<?php echo $product_id; ?>">
                <button type="submit" class="btn btn-danger">Удалить товар</button>
            </form>
        </div>
    </div>
</section>

<!-- Подключение внешних скриптов -->
<script src="../js/admin/settings.js" defer></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>