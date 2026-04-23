<?php
require_once 'includes/header.php';
?>

<section class="hero">
    <div class="hero-content">
        <h1>Добро пожаловать в Волшебную ЛАВКУ</h1>
        <p>Откройте для себя удивительный мир магических товаров</p>
        <a href="/shop.php" class="btn btn-primary">Перейти в каталог</a>
    </div>
</section>

<section class="new-products">
    <h2>Новые товары</h2>
    <div class="products-grid">
        <?php
        $products = getProducts(null, 3);
        foreach ($products as $product):
        ?>
        <div class="product-card">
            <img src="/images/product/<?php echo $product['image']; ?>" alt="<?php echo sanitize($product['name']); ?>">
            <h3><?php echo sanitize($product['name']); ?></h3>
            <p class="price"><?php echo number_format($product['price'], 0, '', ' '); ?> ₽</p>
            <button class="add-to-basket btn btn-primary" data-product-id="<?php echo $product['id']; ?>" data-quantity="1">Добавить в корзину</button>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="recent-reviews">
    <h2>Последние отзывы</h2>
    <div class="reviews-grid">
        <?php
        $stmt = $conn->prepare("SELECT r.*, u.name, p.name as product_name 
                               FROM reviews r 
                               JOIN users u ON r.user_id = u.id 
                               JOIN products p ON r.product_id = p.id 
                               ORDER BY r.created_at DESC 
                               LIMIT 5");
        $stmt->execute();
        $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($reviews as $review):
        ?>
        <div class="review-card">
            <div class="review-header">
                <h4><?php echo sanitize($review['product_name']); ?></h4>
                <div class="rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?php if ($i <= $review['rating']) echo 'filled'; ?>">★</span>
                    <?php endfor; ?>
                </div>
            </div>
            <p class="review-text"><?php echo sanitize($review['comment']); ?></p>
            <p class="review-author">- <?php echo sanitize($review['name']); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
