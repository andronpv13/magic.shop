<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
?>
<div class="basket-container">
    <h1 class="page-title">Корзина</h1>
    
    <?php if (empty($_SESSION['basket'])): ?>
        <div class="empty-state">
            <div class="empty-cart-icon">🛒</div>
            <h2>Ваша корзина пуста</h2>
            <p>Перейдите в каталог, чтобы выбрать что-нибудь интересное.</p>
            <a href="/shop.php" class="btn btn-primary">Перейти в каталог</a>
        </div>
    <?php else: ?>
        <div class="basket-items">
            <?php foreach ($_SESSION['basket'] as $item): ?>
                <div class="basket-item" data-product-id="<?php echo $item['id']; ?>">
                    <?php
                    $imagePath = !empty($item['image']) ? 'product/' . $item['image'] : 'no_photo.png';
                    ?>
                    <div class="basket-item-image">
                        <img src="<?php echo getProductImage($item['image']); ?>" alt="<?php echo sanitize($item['name']); ?>">
                    </div>
                    
                    <div class="basket-item-info">
                        <h3><?php echo e($item['name']); ?></h3>
                        <p class="price"><?php echo formatPrice($item['price']); ?></p>
                    </div>
                    
                    <div class="basket-item-controls">
                        <div class="quantity-controls">
                            <button class="decrease-quantity" data-product-id="<?php echo $item['id']; ?>">-</button>
                            <input type="number" class="quantity-input" data-product-id="<?php echo $item['id']; ?>" value="<?php echo $item['quantity']; ?>" min="1">
                            <button class="increase-quantity" data-product-id="<?php echo $item['id']; ?>">+</button>
                        </div>
                        <span class="item-total" data-product-id="<?php echo $item['id']; ?>"><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                        <button class="remove-from-basket btn btn-sm btn-danger" data-product-id="<?php echo $item['id']; ?>">Удалить</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- БЛОК ИТОГО -->
        <div class="basket-summary">
            <div class="summary-info">
                <!-- ✅ Здесь выводится то же количество, что и в шапке -->
                <p class="basket-count-text">Всего товаров: <span id="cart-page-count" class="highlight-text"><?php echo getBasketCount(); ?></span></p>
                <p class="basket-total">Итого к оплате: <?php echo formatPrice(getBasketTotal()); ?></p>
            </div>
            <a href="/checkout.php" class="btn btn-primary btn-lg">Оформить заказ</a>
        </div>
    <?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>