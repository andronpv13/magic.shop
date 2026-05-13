<?php
/**
 * Мои товары модератора "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Мои товары - Панель модератора';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_md.php';

requireModerator();

// Удаление товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['delete_product'];
    $moderator_id = $_SESSION['user_id'];
    $result = deleteProductModerator($product_id, $moderator_id);
    header('Location: products_md.php?message=' . urlencode($result['message']));
    exit;
}

$products = getModeratorProducts($_SESSION['user_id']);
$message = $_GET['message'] ?? '';
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Мои товары</h1>
            <a href="add_product_md.php" class="btn btn-primary">+ Добавить товар</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($products)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
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
                                <td data-label="ID"><?php echo $product['id']; ?></td>
                                <td data-label="Изображение">
                                    <div class="table-image">
                                        <?php if ($product['image']): ?>
                                            <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                                        <?php else: ?>
                                            <div class="table-image-placeholder">🎁</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Название">
                                    <a href="/shop.php?product_id=<?php echo $product['id']; ?>">
                                        <?php echo e($product['name']); ?>
                                    </a>
                                    <?php if ($product['is_new']): ?>
                                        <span class="badge badge-new">NEW</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Категория"><?php echo e($product['category_name'] ?? '-'); ?></td>
                                <td data-label="Цена"><?php echo number_format($product['price'], 0, ',', ' '); ?> ₽</td>
                                <td data-label="Остаток">
                                    <?php if ($product['stock'] > 0): ?>
                                        <span class="in-stock"><?php echo $product['stock']; ?> шт</span>
                                    <?php else: ?>
                                        <span class="out-of-stock">Нет</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Статус">
                                    <?php if ($product['active'] == 1): ?>
                                        <span class="badge badge-success">Активен</span>
                                    <?php else: ?>
                                        <span class="badge badge-error">Удалён</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Действия">
                                    <div class="table-actions">
                                        <?php if ($product['active'] == 1): ?>
                                            <a href="edit_product_md.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-edit">
                                                ✏️
                                            </a>
                                            <form method="POST" style="display: inline;"
                                                  onsubmit="return confirm('Удалить товар <?php echo e($product['name']); ?>?');">
                                                <input type="hidden" name="delete_product" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <button type="submit" class="btn btn-sm btn-delete">🗑️</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="form-hint">Товар удалён</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty-state">У вас пока нет товаров. <a href="add_product_md.php">Добавить первый товар</a></p>
        <?php endif; ?>

        <a href="index_md.php" class="back-link">← Назад в панель</a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>