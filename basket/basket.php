<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
?>

<h1>Корзина</h1>

<div class="basket-container">
    <?php if (empty($_SESSION['basket'])): ?>
        <p>Ваша корзина пуста</p>
    <?php else: ?>
        <div class="basket-items">
            <?php foreach ($_SESSION['basket'] as $item): ?>
                <div class="basket-item">
                    <?php 
                    $imagePath = !empty($item['image']) ? 'product/' . $item['image'] : 'no_photo.png';
                    ?>
                    <img src="/images/<?php echo $imagePath; ?>" alt="<?php echo sanitize($item['name']); ?>">
                    <div class="basket-item-info">
                        <h3><?php echo sanitize($item['name']); ?></h3>
                        <p class="price"><?php echo number_format($item['price'], 0, '', ' '); ?> ₽</p>
                    </div>
                    <div class="basket-item-controls">
                        <div class="quantity-controls">
                            <button class="decrease-quantity" data-product-id="<?php echo $item['id']; ?>">-</button>
                            <input type="number" class="quantity-input" data-product-id="<?php echo $item['id']; ?>" value="<?php echo $item['quantity']; ?>" min="1">
                            <button class="increase-quantity" data-product-id="<?php echo $item['id']; ?>">+</button>
                        </div>
                        <span class="item-total" data-product-id="<?php echo $item['id']; ?>"><?php echo number_format($item['price'] * $item['quantity'], 0, '', ' '); ?> ₽</span>
                        <button class="remove-from-basket btn btn-danger" data-product-id="<?php echo $item['id']; ?>">Удалить</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="basket-summary">
            <p class="basket-total">Итого: <?php echo number_format(getBasketTotal(), 0, '', ' '); ?> ₽</p>
            <a href="/checkout.php" class="btn btn-primary">Оформить заказ</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
