# 03 — Основные функции и API

## Функции покупателя

**Файл:** `includes/functions.php`

### Товары

#### `getProducts($limit = null)`

Возвращает список товаров. Если `$limit` указан — ограничивает выборку (используется на главной для "новых товаров").

```sql
SELECT * FROM products ORDER BY created_at DESC [LIMIT ?]
```

#### `getProduct($productId)`

Возвращает один товар по ID.

```sql
SELECT * FROM products WHERE productId = ?
```

### Корзина (session-based)

Корзина хранится в `$_SESSION['cart']` как ассоциативный массив:

```php
$_SESSION['cart'] = [
    productId => ['quantity' => int, 'product' => array(...)],
    ...
]
```

#### `addToCart($productId, $quantity = 1)`

Добавляет товар в сессию. Если товар уже есть — увеличивает количество.

#### `getCart()`

Возвращает содержимое корзины с полной информацией о товарах (запрос к БД для каждого productId).

#### `getCartTotal()`

Возвращает общую сумму корзины: `Σ(price × quantity)`.

#### `getCartCount()`

Возвращает общее количество товаров в корзине: `Σ(quantity)`.

#### `removeFromCart($productId)`

Удаляет товар из `$_SESSION['cart']` по ключу productId.

#### `updateCartQuantity($productId, $quantity)`

Обновляет количество товара. Если `quantity <= 0` — удаляет товар.

#### `clearCart()`

Полная очистка `$_SESSION['cart']`.

### Регистрация и профиль

#### `registerCustomer($username, $email, $password, $first_name, $last_name, $phone, $city, $address)`

1. Проверка уникальности email
2. Проверка уникальности username
3. Хэширование пароля (bcrypt)
4. INSERT в `users` с ролью `customer`

#### `getCustomer($id)`

Возвращает профиль покупателя по ID.

#### `updateCustomerProfile($id, $first_name, $last_name, $phone, $city, $address)`

UPDATE полей профиля (не включает email/username/password).

#### `changePassword($user_id, $old_password, $new_password)`

1. SELECT password FROM users WHERE id = ?
2. password_verify($old_password, $hash)
3. Если верен — UPDATE с password_hash($new_password)

### Заказы

#### `createOrder($customer_id, $cart_items)`

**Транзакция:**

1. START TRANSACTION
2. INSERT INTO orders (customer_id, total, status='pending')
3. Получение `order_id` через `mysqli_insert_id()`
4. Для каждого товара: INSERT INTO order_items (order_id, product_id, quantity, price)
5. COMMIT

Возвращает `order_id` при успехе, `false` при ошибке.

#### `getCustomerOrders($customer_id)`

Возвращает все заказы покупателя, отсортированные по дате (новые сверху).

```sql
SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC
```

#### `getOrder($order_id)`

Возвращает заказ по ID.

#### `getOrderItems($order_id)`

Возвращает позиции заказа с информацией о товарах.

```sql
SELECT oi.*, p.name, p.image 
FROM order_items oi 
JOIN products p ON oi.product_id = p.productId 
WHERE oi.order_id = ?
```

### Отзывы

#### `addReview($product_id, $customer_id, $comment)`

Добавляет отзыв. UNIQUE KEY (product_id, customer_id) предотвращает дубликаты.

#### `getProductReviews($product_id)`

Возвращает все отзывы на товар с информацией об авторе.

```sql
SELECT r.*, u.username, u.first_name, u.last_name 
FROM reviews r 
JOIN users u ON r.customer_id = u.id 
WHERE r.product_id = ? 
ORDER BY r.created_at DESC
```

#### `getCustomerPurchasedProducts($customer_id)`

Возвращает список товаров, которые покупатель купил (из completed заказов).

```sql
SELECT DISTINCT p.* 
FROM products p
JOIN order_items oi ON p.productId = oi.product_id
JOIN orders o ON oi.order_id = o.id_or
WHERE o.customer_id = ? AND o.status = 'completed'
```

#### `canReviewProduct($customer_id, $product_id)`

Проверяет:

1. Купил ли покупатель этот товар (completed заказ)
2. Не оставлял ли он уже отзыв (нет записи в reviews)

#### `getLatestReviews($limit = 5)`

Возвращает последние отзывы для главной страницы.

### Загрузка изображений

#### `uploadProductImage($file)`

**Валидация:**

- Расширение: JPG, PNG, GIF, WebP
- Максимальный размер: 5MB
- Проверка `getimagesize()` для безопасности

**Обработка:**

- Генерация уникального имени: `uniqid() . '_' . original_name`
- Сохранение в `images/product/`
- Возврат пути к файлу

## Функции администратора

**Файл:** `includes/functions_adm.php`

### Пользователи

#### `getAllCustomers()`

Все пользователи кроме admin.

```sql
SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC
```

#### `getUserById($user_id)`

Пользователь по ID.

#### `addModerator($username, $email, $password, $first_name, $last_name)`

Создание пользователя с ролью `moderator`.

#### `deleteUser($user_id)`

Удаление пользователя. **Защита:** если роль = 'admin' — возврат false.

#### `resetUserPassword($user_id, $new_password)`

Сброс пароля (bcrypt хэширование).

### Добавление товара

#### `addProductAdmin($name, $description, $price, $image)`

INSERT товара с `created_by = admin_id`.

#### `updateProduct($productId, $name, $description, $price, $image = null)`

UPDATE товара. Если `$image` не указан — старое изображение сохраняется.

#### `deleteProduct($productId)`

DELETE товара (CASCADE удалит связанные order_items и reviews).

#### `getAllProductsWithSeller()`

Все товары с информацией о продавце.

```sql
SELECT p.*, u.username as seller_name 
FROM products p 
LEFT JOIN users u ON p.created_by = u.id 
ORDER BY p.created_at DESC
```

#### `getProductWithSeller($productId)`

Товар с продавцом по ID.

### Все заказы

#### `getAllOrders($status = null)`

Все заказы. Если `$status` указан — фильтрация.

```sql
SELECT o.*, u.username, u.email 
FROM orders o 
JOIN users u ON o.customer_id = u.id 
[WHERE o.status = ?] 
ORDER BY o.created_at DESC
```

#### `updateOrderStatus($order_id, $status)`

UPDATE статуса заказа.

### Все отзывы

#### `getAllReviews()`

Все отзывы с информацией о товаре и авторе.

```sql
SELECT r.*, u.username, p.name as product_name 
FROM reviews r 
JOIN users u ON r.customer_id = u.id 
JOIN products p ON r.product_id = p.productId 
ORDER BY r.created_at DESC
```

#### `deleteReview($review_id)`

DELETE отзыва по ID.

### Статистика

#### `getAdminStats()`

Возвращает массив:

| Ключ | Запрос |
|---|---|
| total_products | COUNT(*) FROM products |
| total_orders | COUNT(*) FROM orders |
| total_customers | COUNT(*) WHERE role='customer' |
| total_moderators | COUNT(*) WHERE role='moderator' |
| total_revenue | SUM(total) WHERE status='completed' |
| pending_orders | COUNT(*) WHERE status='pending' |

## Функции модератора

**Файл:** `includes/functions_md.php`

### Товары модератора

#### `addProductModerator($name, $description, $price, $image, $moderator_id)`

INSERT товара с `created_by = moderator_id`.

#### `getModeratorProducts($moderator_id)`

Только товары, созданные этим модератором.

```sql
SELECT * FROM products WHERE created_by = ? ORDER BY created_at DESC
```

#### `isProductOwner($product_id, $moderator_id)`

Проверяет, что `created_by = moderator_id`.

#### `updateProductModerator($productId, $name, $description, $price, $image, $moderator_id)`

UPDATE только если модератор владеет товаром (через `isProductOwner`).

#### `deleteProductModerator($productId, $moderator_id)`

DELETE только если модератор владеет товаром.

### Все заказы модератор

#### `getAllOrdersModerator($status = null)`

Все заказы (аналогично `getAllOrders` админа).

#### `updateOrderStatusModerator($order_id, $status)`

Смена статуса заказа (аналогично админу).

### Статистика модератор

#### `getModeratorStats($moderator_id)`

| Ключ | Запрос |
|---|---|
| my_products | COUNT(*) WHERE created_by = ? |
| total_orders | COUNT(*) FROM orders |
| pending_orders | COUNT(*) WHERE status = 'pending' |

## AJAX JSON API (корзина)

### POST `/cart/add_to_cart.php`

**Параметры (POST body):**

- `product_id` (int)
- `quantity` (int, по умолчанию 1)

**Ответ (JSON):**

```json
{
    "success": true,
    "message": "Товар добавлен в корзину",
    "cart_count": 3
}
```

### POST `/cart/remove_from_cart.php`

**Параметры:**

- `product_id` (int)

**Ответ:**

```json
{
    "success": true,
    "message": "Товар удалён из корзины",
    "cart_count": 2,
    "cart_total": 1500.00
}
```

### POST `/cart/update_cart.php`

**Параметры:**

- `product_id` (int)
- `quantity` (int)

**Ответ:**

```json
{
    "success": true,
    "message": "Корзина обновлена",
    "cart_count": 3,
    "item_total": 500.00,
    "cart_total": 2000.00
}
```

## Хелперы

**Файл:** `includes/config.php`

### `e($string)`

Экранирование XSS:

```php
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
```

### `checkConnection($mysqli)`

Проверка подключения к БД, вывод ошибки при failure.
