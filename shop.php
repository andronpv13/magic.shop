<?php
/**
 * Каталог товаров "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Каталог';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

// Получаем параметры
$category = isset($_GET['category']) ? trim($_GET['category']) : null;
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;

// Получаем состояние чекбокса "Использовать категории"
$use_categories = isset($_SESSION['use_categories']) ? $_SESSION['use_categories'] : false;

// Режим детальной страницы товара
if ($product_id) {
    $product = getProductById($product_id);
    
    if (!$product) {
        echo '<div class="container section"><p class="empty-state">Товар не найден</p></div>';
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }
    
    $page_title = e($product['name']);
    $reviews = getProductReviews($product_id);
    $can_review = isLoggedIn() ? canReviewProduct($_SESSION['user_id'], $product_id) : false;
    ?>

    <!-- Детальная страница товара -->
    <section class="section">
        <div class="container">
            <nav class="breadcrumbs">
                <a href="/shop.php">Каталог</a>
                <span class="separator">/</span>
                <span class="current"><?php echo e($product['name']); ?></span>
            </nav>

            <div class="product-detail">
                <div class="product-detail-image">
                    <?php if ($product['image']): ?>
                        <img src="/images/<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                    <?php else: ?>
                        <div class="product-detail-placeholder">🎁</div>
                    <?php endif; ?>
                </div>

                <div class="product-detail-info">
                    <h1><?php echo e($product['name']); ?></h1>
                    <!-- Выводим категорию только если она не пуста и не равна 0 -->
                    <?php if (!empty($product['category']) && $product['category'] !== '0'): ?>
                        <p class="product-detail-category"><?php echo e($product['category']); ?></p>
                    <?php endif; ?>
                    
                    <p class="product-detail-price"><?php echo number_format($product['price'], 0, ',', ' '); ?> ₽</p>
                    <p class="product-detail-stock">
                        <?php if ($product['stock'] > 0): ?>
                            <span class="in-stock">✓ В наличии (<?php echo $product['stock']; ?> шт)</span>
                        <?php else: ?>
                            <span class="out-of-stock">✗ Нет в наличии</span>
                        <?php endif; ?>
                    </p>
                    <p class="product-detail-description"><?php echo nl2br(e($product['description'])); ?></p>
                    
                    <?php if ($product['stock'] > 0): ?>
                        <!-- Кнопка обернута в форму для работы с cart.js -->
                        <form class="add-to-cart-form" action="/cart/add_to_cart.php" method="post">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-lg btn-primary">
                                <span class="cart-icon">🛒</span>
                                <span class="cart-text">В корзину</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Отзывы -->
            <div class="product-reviews">
                <h2>Отзывы (<?php echo count($reviews); ?>)</h2>

                <?php if ($can_review): ?>
                    <div class="add-review-form">
                        <h3>Оставить отзыв</h3>
                        <form id="review-form" data-product-id="<?php echo $product_id; ?>">
                            <div class="form-group">
                                <label>Оценка:</label>
                                <div class="rating-input">
                                    <input type="radio" name="rating" value="5" id="star5" required>
                                    <label for="star5">⭐</label>
                                    <input type="radio" name="rating" value="4" id="star4">
                                    <label for="star4">⭐</label>
                                    <input type="radio" name="rating" value="3" id="star3">
                                    <label for="star3">⭐</label>
                                    <input type="radio" name="rating" value="2" id="star2">
                                    <label for="star2">⭐</label>
                                    <input type="radio" name="rating" value="1" id="star1">
                                    <label for="star1">⭐</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="review-comment">Комментарий:</label>
                                <textarea name="comment" id="review-comment" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Отправить отзыв</button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if (!empty($reviews)): ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <span class="review-author"><?php echo e($review['username']); ?></span>
                                    <span class="review-rating">
                                        <?php echo str_repeat('⭐', $review['rating']); ?>
                                    </span>
                                </div>
                                <p class="review-comment"><?php echo nl2br(e($review['comment'])); ?></p>
                                <time class="review-date" datetime="<?php echo $review['created_at']; ?>">
                                    <?php echo date('d.m.Y', strtotime($review['created_at'])); ?>
                                </time>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Отзывов пока нет</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Режим каталога
$products = getProducts($category);
$categories = getCategories();
?>

<!-- Каталог товаров -->
<section class="section">
    <div class="container">
        <h1 class="page-title">Каталог товаров</h1>

        <!-- Фильтр по категориям (показываем только если включено использование категорий) -->
        <?php if ($use_categories && !empty($categories)): ?>
            <div class="category-filter">
                <a href="/shop.php" class="category-tag <?php echo !$category ? 'active' : ''; ?>">
                    Все
                </a>
                <?php foreach ($categories as $cat): ?>
                    <!-- Не выводим категорию "0" и товары с ней -->
                    <?php if ($cat !== '0'): ?>
                        <a href="/shop.php?category=<?php echo urlencode($cat); ?>" 
                           class="category-tag <?php echo $category === $cat ? 'active' : ''; ?>">
                            <?php echo e($cat); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Сетка товаров -->
        <?php if (!empty($products)): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($product['image']): ?>
                                <img src="/images/<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                            <?php else: ?>
                                <div class="product-placeholder">🎁</div>
                            <?php endif; ?>
                            <?php if ($product['is_new']): ?>
                                <span class="badge badge-new">NEW</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name">
                                <a href="/shop.php?product_id=<?php echo $product['id']; ?>">
                                    <?php echo e($product['name']); ?>
                                </a>
                            </h3>
                            <!-- Не выводим категорию, если она пустая или 0 -->
                            <?php if ($use_categories && !empty($product['category']) && $product['category'] !== '0'): ?>
                                <p class="product-category"><?php echo e($product['category']); ?></p>
                            <?php endif; ?>
                            <div class="product-footer">
                                <span class="product-price"><?php echo number_format($product['price'], 0, ',', ' '); ?> ₽</span>
                                
                                <!-- Кнопка с иконкой слева и текстом справа -->
                                <form class="add-to-cart-form" action="/cart/add_to_cart.php" method="post">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <span class="cart-icon">🛒</span>
                                        <span class="cart-text">В корзину</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">Товары не найдены</p>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
