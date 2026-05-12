<?php
/**
 * Управление отзывами "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Отзывы - Админ-панель';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

// Удаление отзыва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    // Проверка CSRF
    if (!csrf_verify()) {
        $message = 'Ошибка безопасности: истек срок действия сессии.';
    } else {
        $review_id = (int)$_POST['delete_review'];
        $result = deleteReview($review_id);

        // Проверяем результат перед редиректом
        if ($result['success']) {
            header('Location: manage_review.php?message=' . urlencode($result['message']));
            exit;
        } else {
            // Можно передать ошибку через сессию или GET параметр, но здесь просто выведем
            $message = $result['message'];
        }
    }
}

$reviews = getAllReviews();
$message = $_GET['message'] ?? $message ?? '';
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Управление отзывами</h1>

        <?php if ($message): ?>
            <div class="alert <?php echo strpos($message, 'Ошибка') !== false ? 'alert-error' : 'alert-success'; ?>">
                <?php echo e($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($reviews)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Автор</th>
                            <th>Товар</th>
                            <th>Оценка</th>
                            <th>Комментарий</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td data-label="ID"><?php echo $review['id']; ?></td>
                                <td data-label="Автор"><?php echo e($review['username']); ?></td>
                                <td data-label="Товар">
                                    <a href="/shop.php?product_id=<?php echo $review['product_id']; ?>">
                                        <?php echo e($review['product_name']); ?>
                                    </a>
                                </td>
                                <td data-label="Оценка">
                                    <span class="review-stars">
                                        <?php echo str_repeat('⭐', $review['rating']); ?>
                                    </span>
                                </td>
                                <td data-label="Комментарий" class="review-comment-cell">
                                    <?php echo e($review['comment']); ?>
                                </td>
                                <td data-label="Дата"><?php echo date('d.m.Y', strtotime($review['created_at'])); ?></td>
                                <td data-label="Действия">
                                    <form method="POST" style="display: inline;"
                                          onsubmit="return confirm('Удалить этот отзыв?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="delete_review" value="<?php echo $review['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-delete">🗑️ Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty-state">Отзывов пока нет</p>
        <?php endif; ?>

        <a href="index.php" class="back-link">← Назад в панель</a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
