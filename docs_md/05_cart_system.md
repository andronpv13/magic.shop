# 05 — Система корзины

## Архитектура

Корзина реализована на **PHP-сессиях** с **AJAX-обновлением** через Fetch API. Серверная логика хранит данные в `$_SESSION['cart']`, клиентский JS отправляет запросы к API-эндпоинтам и обновляет UI без перезагрузки.

## Структура данных

### Сессионная переменная

```php
$_SESSION['cart'] = [
    productId => [
        'quantity' => 3,
        'product' => [
            'productId' => 5,
            'name' => 'Товар',
            'price' => 500.00,
            'image' => 'images/product/abc.jpg',
            // ... остальные поля из БД
        ]
    ],
    7 => [
        'quantity' => 1,
        'product' => [...]
    ]
]
```

**Ключ:** `productId` (int)  
**Значение:** массив с количеством и полной информацией о товаре

## Серверные функции

**Файл:** `includes/functions.php`

### `addToCart($productId, $quantity = 1)`

```php
function addToCart($productId, $quantity = 1) {
    // Получаем товар из БД
    $product = getProduct($productId);
    if (!$product) return false;
    
    // Если товар уже в корзине — увеличиваем количество
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = [
            'quantity' => $quantity,
            'product' => $product
        ];
    }
    return true;
}
```

### `getCart()`

Возвращает `$_SESSION['cart']` целиком. Если корзина пуста — возвращает пустой массив.

### `getCartTotal()`

```php
function getCartTotal() {
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['product']['price'] * $item['quantity'];
    }
    return $total;
}
```

### `getCartCount()`

```php
function getCartCount() {
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
    return $count;
}
```

### `removeFromCart($productId)`

```php
function removeFromCart($productId) {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        return true;
    }
    return false;
}
```

### `updateCartQuantity($productId, $quantity)`

```php
function updateCartQuantity($productId, $quantity) {
    if ($quantity <= 0) {
        return removeFromCart($productId);
    }
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] = $quantity;
        return true;
    }
    return false;
}
```

### `clearCart()`

```php
function clearCart() {
    $_SESSION['cart'] = [];
}
```

## AJAX API эндпоинты

### POST `/cart/add_to_cart.php`

**Подключение:** `includes/config.php`, `includes/functions.php`

**Логика:**

1. Получение `product_id` и `quantity` из `$_POST`
2. Вызов `addToCart($product_id, $quantity)`
3. Получение `getCartCount()` для ответа

**Ответ (JSON):**

```json
{
    "success": true,
    "message": "Товар добавлен в корзину",
    "cart_count": 3
}
```

**При ошибке:**

```json
{
    "success": false,
    "message": "Товар не найден"
}
```

### POST `/cart/remove_from_cart.php`

**Логика:**

1. Получение `product_id` из `$_POST`
2. Вызов `removeFromCart($product_id)`
3. Получение `getCartCount()` и `getCartTotal()`

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

**Логика:**

1. Получение `product_id` и `quantity` из `$_POST`
2. Вызов `updateCartQuantity($product_id, $quantity)`
3. Пересчёт суммы для товара: `price × quantity`
4. Получение `getCartCount()` и `getCartTotal()`

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

## Страница корзины

**Файл:** `cart/cart.php`

### Подключение ядра

```php
require_once '../includes/config.php';
require_once '../includes/functions.php';
```

### Структура страницы

1. **Проверка:** если корзина пуста — показ сообщения "Корзина пуста"
2. **Таблица товаров:**
   - Изображение
   - Название (ссылка на `shop.php?id=`)
   - Цена за единицу
   - **Inline-редактирование количества** (input type="number")
   - Сумма (цена × количество)
   - Кнопка "Удалить"
3. **Итого:** общая сумма корзины
4. **Кнопка:** "Оформить заказ" → `checkout.php`

### Inline-редактирование количества

Каждое поле количества имеет атрибут `data-product-id`:

```html
<input type="number" value="3" min="1" data-product-id="5" class="cart-quantity">
```

**JavaScript-обработчик (js/script.js):**

```javascript
document.querySelectorAll('.cart-quantity').forEach(input => {
    input.addEventListener('change', function() {
        const productId = this.dataset.productId;
        const quantity = parseInt(this.value);
        
        fetch('/cart/update_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}&quantity=${quantity}`
        })
        .then(res => res.json())
        .then(data => {
            // Обновление суммы для строки: data.item_total
            // Обновление общей суммы: data.cart_total
            // Обновление бейджа: data.cart_count
            updateRowTotal(productId, data.item_total);
            updateCartTotal(data.cart_total);
            updateCartCount(data.cart_count);
        });
    });
});
```

### Удаление товара

Кнопка "Удалить" с `data-product-id`:

```javascript
document.querySelectorAll('.remove-from-cart').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        
        fetch('/cart/remove_from_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}`
        })
        .then(res => res.json())
        .then(data => {
            // Удаление строки из таблицы
            removeRow(productId);
            updateCartTotal(data.cart_total);
            updateCartCount(data.cart_count);
            
            // Если корзина пуст — перезагрузка
            if (data.cart_count === 0) {
                location.reload();
            }
        });
    });
});
```

## Клиентский JavaScript

**Файл:** `js/script.js` → `initCart()`

### Добавление в корзину с каталога/главной

```javascript
function initCart() {
    document.addEventListener('click', function(e) {
        const button = e.target.closest('.add-to-cart');
        if (!button) return;
        
        const productId = button.dataset.productId;
        const quantity = 1; // По умолчанию
        
        addToCart(productId, quantity, button);
    });
}
```

### `addToCart(productId, quantity, button)`

```javascript
function addToCart(productId, quantity, button) {
    fetch('/cart/add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(err => showNotification('Ошибка при добавлении', 'error'));
}
```

### `updateCartCount(count)`

Обновляет бейдж в шапке с CSS-анимацией:

```javascript
function updateCartCount(count) {
    const badge = document.querySelector('.cart-count');
    if (badge) {
        badge.textContent = count;
        badge.classList.add('animate');
        setTimeout(() => badge.classList.remove('animate'), 300);
    }
}
```

### `showNotification(message, type)`

Toast-уведомление с авто-скрытием через 3 секунды:

```javascript
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px;
        padding: 15px 25px; border-radius: 8px;
        background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
        color: white; z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
```

## Поток данных

```php
index.php / shop.php
  │
  ├─ Пользователь кликает "В корзину"
  │   │
  │   └─ JS: addToCart(productId, 1, button)
  │       │
  │       └─ POST /cart/add_to_cart.php
  │           │
  │           ├─ addToCart($productId, $quantity)
  │           │   └─ $_SESSION['cart'][id] = {...}
  │           │
  │           └─ JSON {success, cart_count}
  │               │
  │               └─ JS: updateCartCount(), showNotification()
  │
  └─ Пользователь переходит в /cart/cart.php
      │
      ├─ getCart() ────────────────────→ $_SESSION['cart']
      ├─ getCartTotal() ───────────────→ сумма
      └─ HTML таблица с товарами
          │
          ├─ Изменение количества
          │   └─ JS: fetch /cart/update_cart.php
          │       ├─ updateCartQuantity()
          │       │   └─ $_SESSION['cart'][id]['quantity'] = new
          │       └─ JSON {item_total, cart_total, cart_count}
          │           └─ JS: обновление UI
          │
          └─ Клик "Удалить"
              └─ JS: fetch /cart/remove_from_cart.php
                  ├─ removeFromCart()
                  │   └─ unset($_SESSION['cart'][id])
                  └─ JSON {cart_total, cart_count}
                      └─ JS: удаление строки, авто-перезагрузка если пусто
```

## Особенности

| Аспект | Реализация |
|---|---|
| Хранение | PHP-сессия (не БД) |
| Обновление | AJAX без перезагрузки |
| Валидация | Проверка существования товара при добавлении |
| Пустая корзина | Авто-перезагрузка страницы корзины |
| Бейдж корзины | CSS-анимация при обновлении |
| Уведомления | Toast-сообщения с slideIn/slideOut |
| Количество | Min = 1, при 0 — удаление товара |
