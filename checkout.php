<?php
/**
 * Оформление заказа "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Оформление заказа';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Требуем авторизацию
requireLogin();

// Получаем данные текущего пользователя (включая адрес)
$current_user = getCurrentUser();

$cart = getCart();

// Если корзина пуста - редирект
if (empty($cart)) {
    header('Location: /cart/cart.php');
    exit;
}

$cart_total = getCartTotal();
$error = '';
$success = '';

// Переменная для значения адреса в поле
// Приоритет: 1. Отправленный из формы (если была ошибка), 2. Адрес из профиля
$address_value = $_POST['delivery_address'] ?? ($current_user['address'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    
    if (empty($delivery_address)) {
        $error = 'Укажите адрес доставки';
    } else {
        $result = createOrder($delivery_address, $comment);
        
        if ($result['success']) {
            header('Location: /pay.php?order_id=' . $result['order_id']);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Оформление заказа</h1>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <div class="checkout-layout">
            <!-- Данные доставки -->
            <div class="checkout-form-section">
                <h2>Данные доставки</h2>
                
                <form method="POST" class="checkout-form">
                    <div class="form-group">
                        <label for="delivery_address">Адрес доставки: *</label>
                        <textarea 
                            id="delivery_address" 
                            name="delivery_address" 
                            rows="4" 
                            required 
                            placeholder="Город, улица, дом, квартира"
                        ><?php echo e($address_value); ?></textarea>
                        
                        <?php if (empty($current_user['address'])): ?>
                            <small class="text-muted" style="display: block; margin-top: 5px;">
                                Вы можете <a href="/users/profile.php">сохранить адрес в профиле</a>, чтобы в следующий раз он подставился автоматически.
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="comment">Комментарий к заказу:</label>
                        <textarea 
                            id="comment" 
                            name="comment" 
                            rows="3" 
                            placeholder="Дополнительные пожелания"
                        ><?php echo e($_POST['comment'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">Оформить заказ</button>
                </form>
            </div>

            <!-- Содержимое корзины -->
            <div class="checkout-summary-section">
                <h2>Ваш заказ</h2>
                
                <div class="checkout-items">
                    <?php foreach ($cart as $item): ?>
                        <div class="checkout-item">
                            <div class="checkout-item-image">
                                <?php if ($item['image']): ?>
                                    <img src="/images/<?php echo e($item['image']); ?>" alt="<?php echo e($item['name']); ?>">
                                <?php else: ?>
                                    <div class="checkout-item-placeholder">🎁</div>
                                <?php endif; ?>
                            </div>
                            <div class="checkout-item-info">
                                <h4><?php echo e($item['name']); ?></h4>
                                <p class="checkout-item-quantity"><?php echo $item['quantity']; ?> шт × <?php echo number_format($item['price'], 0, ',', ' '); ?> ₽</p>
                            </div>
                            <div class="checkout-item-total">
                                <?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> ₽
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="checkout-total">
                    <span>Итого:</span>
                    <span class="checkout-total-amount"><?php echo number_format($cart_total, 0, ',', ' '); ?> ₽</span>
                </div>
            </div>
        </div>

        <a href="/cart/cart.php" class="back-link">← Вернуться в корзину</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
