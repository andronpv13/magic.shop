<?php
$page_title = 'Оставить отзыв';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
$purchased = getPurchasedProducts($_SESSION['user_id']);
$success = $error = '';

if (!isLoggedIn()) { header('Location: /login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!csrf_verify()) {
        $error = 'Ошибка безопасности (CSRF)';
    } else {
        $pid = (int)($_POST['product_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5 || empty($comment)) { $error = 'Укажите оценку и комментарий'; }
        else {
            if (addReview($_SESSION['user_id'], $pid, $rating, $comment)) {
                $success = 'Спасибо за отзыв! Он будет опубликован после проверки.';
                $purchased = getPurchasedProducts($_SESSION['user_id']);
            } else $error = 'Ошибка сохранения отзыва';
        }
    }
}
?>
<section class="section"><div class="container">
    <h1 class="page-title">Оставить отзыв на купленные товары</h1>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
    <?php if (empty($purchased)): ?>
        <p class="empty-state">У вас нет купленных товаров без отзыва. <a href="/shop.php">Перейти в каталог</a></p>
    <?php else: ?>
        <div class="products-grid">
        <?php foreach ($purchased as $prod): ?>
        <div class="product-card review-card">
            <img src="<?php echo getProductImage($prod['image']); ?>" alt="<?php echo e($prod['name']); ?>">
            <h3><?php echo e($prod['name']); ?></h3>
            <form method="POST" class="review-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                <select name="rating" required><option value="">Оценка</option><option value="5">⭐⭐⭐⭐⭐</option><option value="4">⭐⭐⭐⭐</option><option value="3">⭐⭐⭐</option><option value="2">⭐⭐</option><option value="1">⭐</option></select>
                <textarea name="comment" placeholder="Ваш отзыв..." required></textarea>
                <button type="submit" class="btn btn-primary btn-sm">Отправить</button>
            </form>
        </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <a href="/users/profile.php" class="back-link">← Вернуться в профиль</a>
</div></section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
