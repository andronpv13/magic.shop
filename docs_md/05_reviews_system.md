# 05 - Система отзывов

## Бизнес-логика

### Правила

| Правило | Описание |
|---|---|
| **Только после покупки** | Отзыв можно оставить на товар из заказа со статусом `completed` |
| **Один отзыв на товар** | Один покупатель = один отзыв на один товар (UNIQUE KEY) |
| **Текстовый комментарий** | Рейтинг (звёзды) **отсутствует** |
| **Только покупатели** | Роли admin/moderator не могут оставлять отзывы |

---

## Функции для работы с отзывами

**Файл:** `includes/functions.php`

| Функция | Параметры | Описание |
|---|---|---|
| `addReview($product_id, $customer_id, $comment)` | product_id, customer_id, comment | Добавление отзыва |
| `getProductReviews($product_id)` | product_id | Все отзывы на товар |
| `getCustomerPurchasedProducts($customer_id)` | customer_id | Купленные товары покупателя |
| `canReviewProduct($customer_id, $product_id)` | customer_id, product_id | Можно ли оставить отзыв |
| `getLatestReviews($limit)` | limit (опционально) | Последние отзывы для главной |

---

## Проверка возможности отзыва

**Функция:** `canReviewProduct($customer_id, $product_id)`

### Логика проверки

```php
// 1. Проверяем, что покупатель купил этот товар
SELECT oi.product_id
FROM order_items oi
JOIN orders o ON o.id_or = oi.order_id
WHERE o.customer_id = ? AND oi.product_id = ? AND o.status = 'completed'

// 2. Проверяем, что отзыв ещё не оставлен
SELECT id_rev FROM reviews WHERE product_id = ? AND customer_id = ?
```

**Возврат:** `true` если оба условия выполнены, иначе `false`

---

## Добавление отзыва

**Функция:** `addReview($product_id, $customer_id, $comment)`

### Процесс

1. **Валидация:** проверка, что комментарий не пустой
2. **Проверка UNIQUE:** попытка вставки с обработкой дубликата
3. **Вставка в БД:**

   ```sql
   INSERT INTO reviews (product_id, customer_id, comment) VALUES (?, ?, ?)
   ```

4. **Возврат:** ID нового отзыва или false при ошибке

---

## Отображение отзывов

### На странице товара (`shop.php?id=X`)

```php
$reviews = getProductReviews($product_id);
foreach ($reviews as $review) {
    // Отображение:
    // - Автор (username)
    // - Дата (created_at)
    // - Текст комментария (comment)
}
```

### На главной странице (`index.php`)

```php
$latestReviews = getLatestReviews(3);
// Отображение последних 3 отзывов в отдельной секции
```

### Формат отображения

---
👤 [username] | 📅 [дата]
━━━━━━━━━━━━━━━━━━━━
[текст комментария]
---

---

## Форма добавления отзыва

**Расположение:** `shop.php` в режиме детального просмотра товара

### Условия отображения

```php
if (isLoggedIn() && canReviewProduct($user_id, $product_id)) {
    // Показать форму
}
```

### Форма

```html
<form method="POST">
    <textarea name="comment" placeholder="Ваш отзыв..." required></textarea>
    <button type="submit" name="add_review">Отправить отзыв</button>
</form>
```

### Обработка POST

```php
if (isset($_POST['add_review'])) {
    $result = addReview($product_id, $user_id, $_POST['comment']);
    if ($result) {
        // Успех -- перезагрузка страницы
    } else {
        // Ошибка -- вывод сообщения
    }
}
```

---

## Управление отзывами (Admin)

**Файл:** `admin/manage_review.php`

### Функции администратора (`includes/functions_adm.php`)

| Функция | Параметры | Описание |
|---|---|---|
| `getAllReviews()` | — | Все отзывы с информацией об авторе и товаре |
| `deleteReview($review_id)` | review_id | Удаление отзыва |

### Интерфейс

- Таблица всех отзывов:
  - ID
  - Товар (название)
  - Автор (username)
  - Дата
  - Текст комментария
  - Действие: **Удалить** (с подтверждением)

---

## Ограничения для модератора

| Действие | Доступ |
|---|---|
| Просмотр отзывов | ❌ Нет доступа |
| Удаление отзывов | ❌ Нет доступа |
| Добавление отзывов | ❌ Только покупатели |

---

## Таблица `reviews` (схема)

| Колонка | Тип | Описание |
|---|---|---|
| id_rev | INT AUTO_INCREMENT PK | ID отзыва |
| product_id | INT FK -> products(productId) | Товар (ON DELETE CASCADE) |
| customer_id | INT FK -> users(id) | Автор (ON DELETE CASCADE) |
| comment | TEXT | Текст отзыва |
| created_at | TIMESTAMP | Дата создания |
| UNIQUE KEY | (product_id, customer_id) | Уникальность отзыва |
