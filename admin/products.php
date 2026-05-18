<?php
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

// Обработка удаления товара (только POST с CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    if (!csrf_verify()) {
        $error = 'Ошибка безопасности (CSRF)';
    } else {
        $product_id = (int)$_POST['delete'];
        $result = deleteProduct($product_id);

        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
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
        <h1 class="page-title">Управление товарами</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <!-- Панель управления категориями -->
        <div class="category-control-panel">
            <a href="/admin/manage_category.php" class="btn btn-outline">
                Управление категориями
            </a>

            <a href="/admin/add_product.php" class="btn btn-outline">
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
                                    <td data-label="Изображение">
                                        <?php if ($product['image']): ?>
                                            <div class="table-image">
                                                <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                                            </div>
                                        <?php else: ?>
                                            <div class="table-image-placeholder">🎁</div>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Название">
                                        <a href="/admin/edit_product.php?id=<?php echo $product['id']; ?>">
                                            <?php echo e($product['name']); ?>
                                        </a>
                                    </td>
                                    <td data-label="Категория">
                                        <?php if (!empty($product['category_name'])): ?>
                                            <?php echo e($product['category_name']); ?>
                                        <?php else: ?>
                                            Без категории
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Цена"><?php echo number_format($product['price'], 0, ',', ' '); ?> ₽</td>
                                    <td data-label="Остаток"><?php echo $product['stock']; ?></td>
                                    <td data-label="Статус">
                                        <?php if ($product['is_new']): ?>
                                            <span class="badge badge-new">NEW</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Действия">
                                        <div class="table-actions">
                                            <a href="/admin/edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-edit" title="Редактировать">
                                                ✏️
                                            </a>
                                            <form method="POST" class="form-inline" onsubmit="return confirm('Вы уверены, что хотите удалить этот товар?');">
                                                <input type="hidden" name="delete" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <button type="submit" class="btn btn-sm btn-delete" title="Удалить">🗑️</button>
                                            </form>
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

<!-- Подключение внешних скриптов -->
<script src="../js/admin/settings.js" defer></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>