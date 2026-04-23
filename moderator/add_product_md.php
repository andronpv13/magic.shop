<?php
/**
 * Добавление товара модератором "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Добавить товар - Панель модератора';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_md.php';
require_once __DIR__ . '/../includes/functions.php';

requireModerator();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    
    if (empty($name)) {
        $error = 'Укажите название товара';
    } elseif ($price <= 0) {
        $error = 'Укажите корректную цену';
    } else {
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadProductImage($_FILES['image']);
            
            if ($upload_result['success']) {
                $image_path = $upload_result['filename'];
            } else {
                $error = $upload_result['message'];
            }
        }
        
        if (empty($error)) {
            $result = addProductModerator($name, $description, $price, $category, $stock, $is_new, $image_path, $_SESSION['user_id']);
            
            if ($result['success']) {
                header('Location: products_md.php?success=added');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="index_md.php">Панель модератора</a>
            <span class="separator">/</span>
            <a href="products_md.php">Мои товары</a>
            <span class="separator">/</span>
            <span class="current">Добавить товар</span>
        </nav>

        <h1 class="page-title">Добавить товар</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="product-form">
            <div class="product-form-layout">
                <div class="product-form-main">
                    <div class="form-group">
                        <label for="name">Название товара: *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo e($_POST['name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Описание:</label>
                        <textarea id="description" name="description" rows="6"><?php echo e($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Цена (₽): *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required 
                                   value="<?php echo e($_POST['price'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="stock">Остаток на складе: *</label>
                            <input type="number" id="stock" name="stock" min="0" required 
                                   value="<?php echo e($_POST['stock'] ?? '0'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category">Категория:</label>
                        <input type="text" id="category" name="category" 
                               value="<?php echo e($_POST['category'] ?? ''); ?>" 
                               list="categories-list" placeholder="Выберите или введите новую">
                        <datalist id="categories-list">
                            <?php
                            $categories = getCategories();
                            foreach ($categories as $cat):
                            ?>
                                <option value="<?php echo e($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_new" <?php echo !isset($_POST['is_new']) || $_POST['is_new'] ? 'checked' : ''; ?>>
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
                        <a href="products_md.php" class="btn btn-outline btn-block">Отмена</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
