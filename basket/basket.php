<?php
/**
 * Корзина товаров "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Корзина';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

// УБРАНО: requireLogin(); - теперь корзина видна всем

$cart = getCart();

if (empty($cart)) {
    ?>
    <section class="section">
        <div class="container">
            <h1 class="page-title">Ваша корзина</h1>
            <div class="empty-state">
                <div class="empty-cart-icon">🛒</div>
                <h2>Ваша корзина пуста</h2>
                <p>Перейдите в каталог, чтобы выбрать товары.</p>
                <a href="/shop.php" class="btn btn-primary">Перейти в каталог</a>
            </div>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$cart_count = getCartCount();
$cart_total = getCartTotal();
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Ваша корзина</h1>

        <div class="cart-layout">
            <!-- Левая колонка: Список товаров -->
            <div class="cart-items-list">
                <?php foreach ($cart as $product_id => $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <?php if ($item['image']): ?>
                                <img src="/images/<?php echo e($item['image']); ?>" alt="<?php echo e($item['name']); ?>">
                            <?php else: ?>
                                <div class="cart-item-placeholder">🎁</div>
                            <?php endif; ?>
                        </div>

                        <div class="cart-item-info">
                            <h3 class="cart-item-name">
                                <a href="/shop.php?product_id=<?php echo $item['product_id']; ?>">
                                    <?php echo e($item['name']); ?>
                                </a>
                            </h3>
                            <p class="cart-item-price">
                                <?php echo number_format($item['price'], 0, ',', ' '); ?> ₽
                            </p>
                        </div>

                        <div class="cart-item-actions">
                            <!-- Управление количеством -->
                            <div class="cart-quantity-control">
                                <!-- Кнопка МИНУС (Круглая) -->
                                <button type="button" 
                                        class="btn-icon btn-quantity-minus" 
                                        data-action="decrease" 
                                        data-product-id="<?php echo $item['product_id']; ?>"
                                        aria-label="Уменьшить">
                                    -
                                </button>
                                
                                <input type="number" 
                                       class="cart-quantity-input" 
                                       name="quantity" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="<?php echo $item['stock']; ?>"
                                       data-product-id="<?php echo $item['product_id']; ?>"
                                       readonly>
                                        
                                <!-- Кнопка ПЛЮС (Круглая) -->
                                <button type="button" 
                                        class="btn-icon btn-quantity-plus" 
                                        data-action="increase" 
                                        data-product-id="<?php echo $item['product_id']; ?>"
                                        aria-label="Увеличить">
                                    +
                                </button>
                            </div>

                            <!-- Сумма по товару -->
                            <div class="cart-item-total">
                                <?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> ₽
                            </div>

                            <!-- Кнопка УДАЛИТЬ (Иконка корзины) -->
                            <button class="btn-icon btn-delete-item" 
                                    data-product-id="<?php echo $item['product_id']; ?>" 
                                    title="Удалить">
                                🗑️
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Правая колонка: Итого и оформление -->
            <div class="cart-summary">
                <div class="cart-card">
                    <div class="cart-summary-details">
                        <div class="cart-summary-row">
                            <span>Товаров:</span>
                            <span id="cart-summary-count"><?php echo $cart_count; ?> шт.</span>
                        </div>
                    </div>

                    <div class="cart-total-row">
                        <span>Сумма к оплате:</span>
                        <span class="cart-total-amount"><?php echo number_format($cart_total, 0, ',', ' '); ?> ₽</span>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <a href="/checkout.php" class="btn btn-primary btn-block btn-lg">Оформить заказ</a>
                    <?php else: ?>
                        <div class="auth-notice">
                            <p>Для заказа товара нужно авторизоваться.</p>
                            <a href="/login.php" class="btn btn-primary btn-block">Войти в аккаунт</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <a href="/shop.php" class="back-link">← Вернуться в каталог</a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
