# 03 - Каталог товаров и система корзины

## Работа с товарами

**Файл:** `includes/functions.php`

### Функции для покупателей

| Функция | Параметры | Описание |
|---|---|---|
| `getProducts($limit)` | $limit (опционально) | Все товары, опционально с LIMIT |
| `getProduct($productId)` | $productId | Товар по ID |

### Файл `index.php` (Главная страница)

- Hero-секция с приветствием
- **Новые товары:** последние 6 товаров из БД (`getProducts(6)`)
- **Последние отзывы:** 5 последних отзывов (`getLatestReviews(5)`)
- Кнопки "В корзину" с AJAX-обработкой

### Файл `shop.php` (Каталог)

- Отображение **всех товаров** в сетке
- При GET `?id=X` -- режим детального просмотра товара:
  - Полное описание
  - Фото
  - Цена
  - Форма добавления в корзину (количество)
  - Отзывы на товар (`getProductReviews($product_id)`)
  - Форма добавления отзыва (если `canReviewProduct()`)

---

## Корзина: Хранение и логика

**Хранение:** `$_SESSION['cart']` -- ассоциативный массив:

```php
$_SESSION['cart'] = [
    productId => [
        'quantity' => int,
        'name' => string,
        'price' => decimal,
        'image' => string
    ],
    ...
];
```

### Функции корзины (`includes/functions.php`)

| Функция | Параметры | Описание | Возврат |
|---|---|---|---|
| `addToCart($productId, $quantity)` | productId, quantity | Добавление товара в сессию | void |
| `getCart()` | — | Получить содержимое корзины | array |
| `getCartTotal()` | — | Общая сумма корзины | decimal |
| `getCartCount()` | — | Общее количество товаров | int |
| `removeFromCart($productId)` | productId | Удаление товара | void |
| `updateCartQuantity($productId, $quantity)` | productId, quantity | Обновление количества | void |
| `clearCart()` | — | Очистка корзины | void |

---

## AJAX API корзины

Все эндпоинты возвращают JSON и подключают `includes/functions.php`.

### POST `/cart/add_to_cart.php`

**Параметры (POST):**

- `product_id` -- ID товара
- `quantity` -- количество (по умолчанию 1)

**Ответ:**

```json
{
    "success": true,
    "message": "Товар добавлен в корзину",
    "cart_count": 3
}
```

### POST `/cart/remove_from_cart.php`

**Параметры (POST):**

- `product_id` -- ID товара

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

**Параметры (POST):**

- `product_id` -- ID товара
- `quantity` -- новое количество

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

---

## Клиентский JS

**Файл:** `js/script.js`

### Функция `initCart()`

- Навешивает обработчики событий на кнопки `.add-to-cart` через делегирование
- Предотвращает стандартную отправку формы
- Вызывает `addToCart(productId, quantity, button)`

### Функция `addToCart(productId, quantity, button)`

1. Собирает данные через `FormData`
2. Отправляет Fetch POST на `/cart/add_to_cart.php`
3. При успехе:
   - Обновляет бейдж корзины через `updateCartCount(count)`
   - Показывает уведомление `showNotification(message, 'success')`

### Функция `updateCartCount(count)`

- Обновляет текст бейджа в шапке
- Добавляет CSS-анимацию `scale` для привлечения внимания

### Функция `showNotification(message, type)`

- Создаёт toast-уведомление (`div.notification`)
- Типы: `success` (зелёный), `error` (красный)
- Автоматическое скрытие через 3 секунды
- CSS keyframes `slideIn`/`slideOut` инъекцируются через JS

---

## Страница корзины

**Файл:** `cart/cart.php`

### Структура страницы

- Таблица товаров в корзине:
  - Фото, название, цена
  - **Inline-редактирование количества** (input + кнопка "Обновить")
  - Кнопка "Удалить"
  - Сумма позиции
- Итоговая сумма
- Кнопка "Оформить заказ" → `/checkout.php`

### AJAX-обновление на странице

- При изменении количества: POST на `/cart/update_cart.php`
- При удалении: POST на `/cart/remove_from_cart.php`
- Обновление сумм **без перезагрузки** страницы
- При опустошении корзины -- **автоперезагрузка** страницы

---

## Загрузка изображений товаров

**Файл:** `includes/functions.php` → `uploadProductImage($file)`

### Валидация

| Параметр | Значение |
|---|---|
| Допустимые форматы | JPG, PNG, GIF, WebP |
| Максимальный размер | 5 MB |
| Имя файла | `uniqid()` + оригинальное расширение |
| Путь сохранения | `images/product/` |

### Процесс

1. Проверка `error` ключа файла
2. Проверка расширения через `pathinfo()`
3. Проверка размера (5MB лимит)
4. Генерация уникального имени
5. Перемещение через `move_uploaded_file()`
6. Возврат пути к файлу или false при ошибке
