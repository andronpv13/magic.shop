<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
?>
<section class="hero">
<div class="hero-content">
    <h1>Добро пожаловать в Волшебную ЛАВКУ</h1>
    <p>Откройте для себя удивительный мир магических товаров</p>
    <a href="/shop.php" class="btn btn-primary">Перейти в каталог</a>
</div>
</section>

<section class="new-products">
<h2>Наши новинки</h2>
<div class="products-slider">
<?php
$stmt = $conn->prepare("SELECT p.*, u.username as creator_name FROM products p LEFT JOIN users u ON p.created_by = u.id WHERE p.active = 1 ORDER BY p.created_at DESC LIMIT 3");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($products as $product):
    $imagePath = !empty($product['image']) ? 'product/' . $product['image'] : 'no_photo.png';
?>
<div class="product-card">
    <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
    <div class="card-content">
        <h3><?php echo e($product['name']); ?></h3>
        <p class="price"><?php echo number_format($product['price'], 0, '', ' '); ?> ₽</p>
        <div class="btn-container">
            <a href="/shop.php?id=<?php echo $product['id']; ?>" class="btn">Подробнее</a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
</section>

<section class="recent-reviews">
<h2>Последние отзывы</h2>
<div class="reviews-grid">
<?php
$stmt = $conn->prepare("SELECT r.*, u.username, p.name as product_name FROM reviews r JOIN users u ON r.user_id = u.id JOIN products p ON r.product_id = p.id ORDER BY r.created_at DESC LIMIT 5");
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($reviews as $review):
?>
<div class="review-card">
    <div class="review-header">
        <h4><?php echo e($review['product_name']); ?></h4>
        <div class="rating">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <span class="star <?php if ($i <= $review['rating']) echo 'filled'; ?>">★</span>
        <?php endfor; ?>
        </div>
    </div>
    <p class="review-text"><?php echo e($review['comment']); ?></p>
    <p class="review-author">- <?php echo e($review['username']); ?></p>
</div>
<?php endforeach; ?>
</div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
