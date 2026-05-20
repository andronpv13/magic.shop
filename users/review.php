<?php
$page_title = 'Оставить отзыв';
require_once __DIR__ . '/../includes/header.php';

// Обработка AJAX-запросов
if (isset($_GET['action']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!csrf_verify()) {
        echo json_encode(['success' => false, 'message' => 'Ошибка безопасности (CSRF)']);
        exit;
    }

    $action = $_GET['action'];

    if ($action === 'update_review') {
        $review_id = (int)($_POST['review_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5 || empty($comment)) {
            echo json_encode(['success' => false, 'message' => 'Укажите оценку и комментарий']);
            exit;
        }

        if (updateReview($review_id, $_SESSION['user_id'], $rating, $comment)) {
            echo json_encode(['success' => true, 'message' => 'Отзыв обновлен! Он будет опубликован после проверки.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка обновления отзыва']);
        }
        exit;
    }
}

$purchased = getPurchasedProducts($_SESSION['user_id']);
$userReviews = getReviewsByUser($_SESSION['user_id']);
$success = $error = '';

if (!isLoggedIn()) { header('Location: /login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action'])) {
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
                $success = 'Спасибо за отзыв! Система скоро проверит его на спам, оскорбления и т.д.. Он будет опубликован после проверки.';
                $purchased = getPurchasedProducts($_SESSION['user_id']);
                $userReviews = getReviewsByUser($_SESSION['user_id']);
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
                <div class="rating-wrapper">
                    <label for="rating_<?php echo $prod['id']; ?>">Оценка:</label>
                    <select name="rating" id="rating_<?php echo $prod['id']; ?>" required>
                        <option value="">Выберите</option>
                        <option value="5">⭐⭐⭐⭐⭐</option>
                        <option value="4">⭐⭐⭐⭐</option>
                        <option value="3">⭐⭐⭐</option>
                        <option value="2">⭐⭐</option>
                        <option value="1">⭐</option>
                    </select>
                </div>
                <textarea name="comment" placeholder="Ваш отзыв..." required></textarea>
                <button type="submit" class="btn btn-outline">Отправить</button>
            </form>
        </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Отзывы пользователя -->
    <?php if (!empty($userReviews)): ?>
    <h2 class="page-title" style="margin-top: 3rem;">Мои отзывы</h2>
    <div class="products-grid">
        <?php foreach ($userReviews as $review): ?>
        <div class="product-card review-card user-review-card" data-review-id="<?php echo $review['id']; ?>">
            <img src="<?php echo getProductImage($review['product_image']); ?>" alt="<?php echo e($review['product_name']); ?>">
            <h3><?php echo e($review['product_name']); ?></h3>

            <!-- Форма редактирования (скрыта по умолчанию) -->
            <form method="POST" class="review-edit-form" style="display: none;" data-review-id="<?php echo $review['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                <div class="review-rating-input-wrapper">
                    <label class="review-rating-label">Оценка:</label>
                    <select name="rating" required>
                        <option value="">Выберите</option>
                        <option value="5" <?php echo $review['rating'] == 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐</option>
                        <option value="4" <?php echo $review['rating'] == 4 ? 'selected' : ''; ?>>⭐⭐⭐⭐</option>
                        <option value="3" <?php echo $review['rating'] == 3 ? 'selected' : ''; ?>>⭐⭐⭐</option>
                        <option value="2" <?php echo $review['rating'] == 2 ? 'selected' : ''; ?>>⭐⭐</option>
                        <option value="1" <?php echo $review['rating'] == 1 ? 'selected' : ''; ?>>⭐</option>
                    </select>
                </div>
                <textarea name="comment" placeholder="Ваш отзыв..." required><?php echo e($review['comment']); ?></textarea>
                <div class="review-edit-actions">
                    <button type="submit" class="btn btn-sm btn-save-review">💾 Сохранить</button>
                    <button type="button" class="btn btn-sm btn-cancel-edit">✖ Отмена</button>
                </div>
            </form>

            <!-- Отображение отзыва -->
            <div class="review-display-content">
                <div class="review-rating-display">
                    <?php for ($i = 0; $i < $review['rating']; $i++): ?>⭐<?php endfor; ?>
                </div>
                <p class="review-comment"><?php echo e($review['comment']); ?></p>
                <div class="review-status <?php echo $review['is_approved'] ? 'approved' : 'pending'; ?>">
                    <?php echo $review['is_approved'] ? '✓ Опубликован' : '⏳ На модерации'; ?>
                </div>
                <div class="review-actions">
                    <button class="btn btn-sm btn-edit-review"
                            data-id="<?php echo $review['id']; ?>">
                        ✏️ Редактировать
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div></section>

<script src="/js/review.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>