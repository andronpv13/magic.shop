<?php
$page_title = 'Оформление заказа';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) { header('Location: /login.php'); exit; }

$current_user = getCurrentUser();
$cart = $_SESSION['basket'] ?? [];
if (empty($cart)) { header('Location: /basket/basket.php'); exit; }
$cart_total = getBasketTotal();
$error = '';
/* Формируем адрес доставки по данным пользователя, если они есть. Это удобство для пользователей, которые уже заполнили свой профиль, чтобы не вводить адрес вручную при каждом заказе. Если данных нет, поле будет пустым и пользователь сможет ввести адрес самостоятельно.
 */
$address_parts = [];
   if (!empty($current_user['city'])) $address_parts[] = $current_user['city'];
   if (!empty($current_user['street'])) $address_parts[] = $current_user['street'];
   if (!empty($current_user['house'])) $address_parts[] = $current_user['house'];
   if (!empty($current_user['apartment'])) $address_parts[] = 'кв. ' . $current_user['apartment'];
   $default_address = implode(', ', $address_parts);
   $address_value = $_POST['delivery_address'] ?? $default_address;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!csrf_verify()) {
        $error = 'Ошибка безопасности (CSRF)';
    } else {
        $delivery_address = trim($_POST['delivery_address'] ?? '');
        $comment = trim($_POST['comment'] ?? '');
        /* Валидация адреса доставки. Убедимся, что пользователь ввел достаточно информации для доставки. Если адрес слишком короткий, это может быть ошибкой, и мы попросим пользователя указать более полный адрес.*/
        if (empty($delivery_address) || strlen($delivery_address) < 10) {
            $error = 'Укажите полный адрес доставки';
        }
        else {
            $result = createOrder($_SESSION['user_id'], $cart, $delivery_address, $comment);
            if ($result['success']) {
                $_SESSION['basket'] = [];
                header('Location: /pay.php?order_id=' . $result['order_id']); exit;
            } else $error = $result['message'];
        }
    }
}
?>
<section class="section"><div class="container">
    <h1 class="page-title">Оформление заказа</h1>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
    <div class="checkout-layout">
        <div class="checkout-form-section">
            <h2>Данные доставки</h2>
            <form method="POST" class="checkout-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group"><label for="delivery_address">Адрес доставки: *</label><textarea id="delivery_address" name="delivery_address" rows="4" required placeholder="Город, улица, дом, квартира"><?php echo e($address_value); ?></textarea></div>
                <div class="form-group"><label for="comment">Комментарий к заказу:</label><textarea id="comment" name="comment" rows="3" placeholder="Дополнительные пожелания"><?php echo e($_POST['comment'] ?? ''); ?></textarea></div>
                <button type="submit" class="btn btn-primary btn-lg">Оформить заказ</button>
            </form>
        </div>
        <div class="checkout-summary-section">
            <h2>Ваш заказ</h2>
            <div class="checkout-items">
                <?php foreach ($cart as $item): ?>
                <div class="checkout-item">
                    <div class="checkout-item-image"><img src="<?php echo getProductImage($item['image']); ?>" alt="<?php echo e($item['name']); ?>"></div>
                    <div class="checkout-item-info"><h4><?php echo e($item['name']); ?></h4><p class="checkout-item-quantity"><?php echo $item['quantity']; ?> шт × <?php echo formatPrice($item['price']); ?></p></div>
                    <div class="checkout-item-total"><?php echo formatPrice($item['price'] * $item['quantity']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="checkout-total"><span>Итого:</span><span class="checkout-total-amount"><?php echo formatPrice($cart_total); ?></span></div>
        </div>
    </div>
    <a href="/basket/basket.php" class="back-link">← Вернуться в корзину</a>
</div></section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
