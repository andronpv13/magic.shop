<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Управление товарами "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Управление товарами';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';

requireAdmin();

// Инициализируем переменные только для POST-запросов
$success = '';
$error = '';

// Обработка удаления товара
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    $result = deleteProduct($product_id);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Получаем список товаров
$products = getAllProducts();

// Получаем список категорий
$categories = getCategoriesList();

// Получаем текущее состояние чекбокса "Использовать категории" из настроек
$use_categories = isset($_SESSION['use_categories']) ? $_SESSION['use_categories'] : false;
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/admin/index.php">Админ-панель</a>
            <span class="separator">/</span>
            <span class="current">Товары</span>
        </nav>

        <h1 class="page-title">Управление товарами</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <!-- Панель управления категориями -->
        <div class="category-control-panel">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="use_categories" name="use_categories" value="1" 
                           onchange="toggleCategories(this)" <?php echo $use_categories ? 'checked' : ''; ?>>
                    Использовать категории
                </label>
            </div>
            
            <a href="/admin/manage_category.php" class="btn btn-primary">
                Управление категориями
            </a>
            
            <a href="/admin/add_product.php" class="btn btn-primary">
                Добавить товар
            </a>
        </div>

        <!-- Список товаров -->
        <div class="products-list">
            <h2>Список товаров</h2>
            
            <?php if (!empty($products)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Изображение</th>
                                <th>Название</th>
                                <th>Категория</th>
                                <th>Цена</th>
                                <th>Остаток</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <div class="table-image">
                                                <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo e($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                            </div>
                                        <?php else: ?>
                                            <div class="table-image-placeholder">🎁</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/admin/edit_product.php?id=<?php echo $product['id']; ?>">
                                            <?php echo e($product['name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($product['category_name'])): ?>
                                            <?php echo e($product['category_name']); ?>
                                        <?php else: ?>
                                            Без категории
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($product['price'], 0, ',', ' '); ?> ₽</td>
                                    <td><?php echo $product['stock']; ?></td>
                                    <td>
                                        <?php if ($product['is_new']): ?>
                                            <span class="badge badge-new">NEW</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/admin/edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-edit">
                                                Редактировать
                                            </a>
                                            <a href="/admin/products.php?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-delete"
                                               onclick="return confirm('Вы уверены, что хотите удалить этот товар?');">
                                                Удалить
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">Товары не найдены</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
    // При загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('use_categories');
        if (checkbox) {
            const localState = localStorage.getItem('use_categories');
            if (localState !== null) {
                checkbox.checked = localState === 'true';
            }
        }
    });

    // Функция для переключения использования категорий
    function toggleCategories(checkbox) {
        const formData = new FormData();
        formData.append('use_categories', checkbox.checked ? '1' : '0');
        formData.append('csrf_token', '<?php echo csrf_token(); ?>');

        fetch('/admin/update_settings.php?ajax=1', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                localStorage.setItem('use_categories', checkbox.checked ? 'true' : 'false');
            } else {
                alert('Ошибка при обновлении настроек: ' + (data.message || 'Неизвестная ошибка'));
            }
        })
        .catch(() => alert('Ошибка при отправке запроса'));
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
