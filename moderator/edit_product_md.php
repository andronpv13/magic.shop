<?php
/**
 * Редактирование товара модератором "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Редактировать товар - Панель модератора';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_md.php';
require_once __DIR__ . '/../includes/functions.php';

requireModerator();

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: products_md.php');
    exit;
}

// Проверка владения
if (!isProductOwner($product_id, $_SESSION['user_id'])) {
    echo '<div class="container section"><div class="alert alert-error">У вас нет прав на редактирование этого товара</div></div>';
    require_once __DIR__ . '/../includes/footer.php';
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
            $result = editProductModerator($product_id, $name, $description, $price, $category, $stock, $is_new, $image_path, $_SESSION['user_id']);
            
            if ($result['success']) {
                $success = 'Товар обновлен';
                $product = getProductById($product_id);
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
                            <input type="number" id="price" name="price" step="0.01" min="0" required 
                                   value="<?php echo e($product['price']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="stock">Остаток на складе: *</label>
                            <input type="number" id="stock" name="stock" min="0" required 
                                   value="<?php echo e($product['stock']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category">Категория:</label>
                        <input type="text" id="category" name="category" 
                               value="<?php echo e($product['category'] ?? ''); ?>" 
                               list="categories-list">
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
                                <img src="/images/<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
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
                        <a href="products_md.php" class="btn btn-outline btn-block">Отмена</a>
                    </div>
                </div>
            </div>
        </form>

        <!-- Удаление товара -->
        <div class="delete-product-section">
            <h3>Удалить товар</h3>
            <p>Это действие нельзя отменить</p>
            <form method="POST" action="products_md.php" onsubmit="return confirm('Вы уверены, что хотите удалить этот товар?');">
                <input type="hidden" name="delete_product" value="<?php echo $product_id; ?>">
                <input type="hidden" name="moderator_id" value="<?php echo $_SESSION['user_id']; ?>">
                <button type="submit" class="btn btn-danger">Удалить товар</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
