<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Получаем все категории
$categories = getCategories();

// Получаем ID категории из URL
$category = isset($_GET['category']) ? sanitize($_GET['category']) : null;

// Получаем ID товара из URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Если указан ID товара, показываем детальную страницу
if ($product_id) {
    $product = getProductById($product_id);
    if (!$product) {
        redirect('/shop.php');
    }
    ?>
    <div class="product-detail">
        <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>">
        <div class="product-info">
            <h1><?php echo sanitize($product['name']); ?></h1>
            <p class="price"><?php echo number_format($product['price'], 0, '', ' '); ?> ₽</p>
            <p class="description"><?php echo sanitize($product['description']); ?></p>
            <div class="product-actions">
                <div class="quantity-selector">
                    <button class="quantity-btn decrease" data-product-id="<?php echo $product['id']; ?>">-</button>
                    <input type="number" class="quantity-input" data-product-id="<?php echo $product['id']; ?>" value="1" min="1" max="99">
                    <button class="quantity-btn increase" data-product-id="<?php echo $product['id']; ?>">+</button>
                </div>
                <button class="add-to-basket btn btn-primary" data-product-id="<?php echo $product['id']; ?>" data-quantity="1">В корзину</button>
            </div>
        </div>
    </div>
    <?php
} else {
    // Показываем каталог товаров
    $products = getProducts($category);
    ?>
    <div class="shop-header">
        <h1 class="shop-title">Каталог товаров</h1>
        <div class="category-filter">
            <select id="category-select" onchange="if (this.value) window.location.href='?category=' + this.value; else window.location.href='?';">
                <option value="">Все товары</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>" <?php if ($category === $cat) echo 'selected'; ?>>
                        <?php echo sanitize($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="products-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <?php
                $imagePath = !empty($product['image']) ? 'product/' . $product['image'] : 'no_photo.png';
                ?>
                <a href="shop.php?id=<?php echo $product['id']; ?>" class="product-image-link">
                    <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>">
                    <span class="image-overlay">Подробнее</span>
                </a>
                <div class="card-content">
                    <h3><?php echo sanitize($product['name']); ?></h3>
                    <p class="price"><?php echo number_format($product['price'], 0, '', ' '); ?> ₽</p>
                    <div class="btn-container">
                        <button class="add-to-basket btn" data-product-id="<?php echo $product['id']; ?>" data-quantity="1">В корзину</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
?>

<?php require_once 'includes/footer.php'; ?>
