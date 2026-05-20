<?php
/**
 * magic.shop — Главная страница
 */

// 🔗 Подключение ядра
require_once __DIR__ . '/includes/config.php';

// 📊 Получение новинок для слайдера
$news = [];
$stmt = $conn->prepare("SELECT id, name, price, image, description FROM products WHERE is_new = 1 AND active = 1 AND stock > 0 ORDER BY created_at DESC LIMIT 6");
$stmt->execute();
$news = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 🗣️ Получение последних одобренных отзывов
$reviews = [];
$stmt = $conn->prepare("SELECT r.rating, r.text, r.created_at, u.username, p.name as product_name, p.id as product_id 
                        FROM reviews r 
                        JOIN users u ON r.user_id = u.id 
                        JOIN products p ON r.product_id = p.id 
                        WHERE r.is_approved = 1 
                        ORDER BY r.created_at DESC LIMIT 4");
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 🎨 Мета-данные страницы
$page_title = 'Главная';
$breadcrumbs = [['title' => 'Главная', 'url' => '']];
require_once INCLUDES_PATH . '/header.php';
?>

<!-- 🎠 Слайдер новинок -->
<?php if (!empty($news)): ?>
<section class="hero-slider">
    <div class="container">
        <h2>✨ Новинки сезона</h2>
        <div class="slider-grid">
            <?php foreach ($news as $item): ?>
                <article class="product-card">
                    <a href="<?= site_url('shop.php?product=' . (int)$item['id']) ?>">
                        <img src="<?= $item['image'] ? site_url('images/product/' . e($item['image'])) : site_url('images/no_photo.png') ?>" 
                             alt="<?= e($item['name']) ?>" 
                             loading="lazy">
                        <h3><?= e($item['name']) ?></h3>
                        <p class="price"><?= number_format($item['price'], 0, '.', ' ') ?> ₽</p>
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <button class="btn btn-sm btn-add" 
                                data-product-id="<?= (int)$item['id'] ?>"
                                data-csrf="<?= $_SESSION['csrf_token'] ?>">
                            В корзину
                        </button>
                    <?php else: ?>
                        <a href="<?= site_url('login.php') ?>" class="btn btn-sm">Войти для покупки</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 🗣️ Отзывы покупателей -->
<section class="reviews-section">
    <div class="container">
        <h2>💬 Отзывы наших клиентов</h2>
        <?php if (!empty($reviews)): ?>
            <div class="reviews-grid">
                <?php foreach ($reviews as $rev): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <strong><?= e($rev['username']) ?></strong>
                            <span class="rating"><?= str_repeat('⭐', (int)$rev['rating']) ?></span>
                        </div>
                        <p class="review-product">
                            на товар: <a href="<?= site_url('shop.php?product=' . (int)$rev['product_id']) ?>">
                                <?= e($rev['product_name']) ?>
                            </a>
                        </p>
                        <p class="review-text"><?= e($rev['text']) ?></p>
                        <time class="review-date"><?= date('d.m.Y', strtotime($rev['created_at'])) ?></time>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-center">
                <a href="<?= site_url('shop.php') ?>" class="btn">Смотреть все товары →</a>
            </p>
        <?php else: ?>
            <p class="text-center">Пока нет отзывов. Будьте первым!</p>
        <?php endif; ?>
    </div>
</section>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>