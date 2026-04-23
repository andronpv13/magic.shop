<?php
/**
 * Главная страница "Волшебная ЛАВКА"
 * Разработчик: АВВА ©2025
 */

// Включаем отображение всех ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// echo "Начало загрузки страницы<br>";

require_once 'includes/config.php';
// echo "Config загружен<br>";

require_once 'includes/functions.php';
// echo "Functions загружен<br>";

require_once 'includes/header.php';
//echo "Header загружен<br>";

$page_title = 'Главная';

// Получаем последние 3 товара
$latest_products = getLatestProducts(3);

// Проверяем, есть ли товары
if (empty($latest_products)) {
    echo '<div class="container section"><p class="empty-state">Товары не найдены</p></div>';
    require_once 'includes/footer.php';
    exit;
}
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Добро пожаловать в Волшебную ЛАВКУ</h1>
        
        <div class="products-slider">
            <h2>Последние товары</h2>
            <div class="slider-container">
                <?php foreach ($latest_products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (!empty($product['image'])): ?>
                                <img src="/images/<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                            <?php else: ?>
                                <div class="no-image">🎁</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3><?php echo e($product['name']); ?></h3>
                            
                            <!-- Отображаем категорию только если она есть -->
                            <?php if (!empty($product['category_name'])): ?>
                                <p class="product-category">
                                    <span class="category-label">Категория:</span>
                                    <?php echo e($product['category_name']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <p class="product-price"><?php echo number_format($product['price'], 0, ',', ' '); ?> ₽</p>
                            <a href="/shop.php" class="btn btn-primary">Подробнее</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php 
// echo "Конец загрузки страницы<br>";
require_once 'includes/footer.php'; 
?>
