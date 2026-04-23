# 06 — Оформление заказа и оплата

## Оформление заказа

**Файл:** `checkout.php`

### Требования

- **Авторизация:** `requireLogin()` — если не авторизован, редирект на `/login.php`
- **Корзина:** должна содержать товары — если пуста, редирект на `/cart/cart.php`

### Подключение ядра

```php
require_once 'includes/config.php';
require_once 'includes/functions.php';
requireLogin();
```

### Обработка POST (создание заказа)

**Условие:** `$_SERVER['REQUEST_METHOD'] === 'POST'`

**Логика:**

1. Получение `customer_id` из `$_SESSION['user_id']`
2. Получение товаров корзины: `getCart()`
3. Проверка: корзина не пуста
4. Вызов `createOrder($customer_id, $cart_items)` — транзакция
5. При успехе:
   - `clearCart()` — очистка корзины
   - Редирект на `pay.php?order_id={$order_id}`
6. При ошибке — установка `$error` для отображения

### Отображение формы (GET)

**Блок данных покупателя:**

```php
$customer = getCustomer($_SESSION['user_id']);
```

**Отображаемые поля (только просмотр):**

- Имя: `first_name`
- Фамилия: `last_name`
- Email: `email`
- Телефон: `phone`
- Город: `city`
- Адрес: `address`

**Блок корзины:**

- Список товаров: название, количество, цена
- Общая сумма: `getCartTotal()`

**Кнопка:** "Оформить заказ" → POST-форма

### Структура страницы

```php
checkout.php
├── header.php
│
├── GET-режим (отображение)
│   ├── Данные покупателя (read-only)
│   │   ├── first_name, last_name
│   │   ├── email, phone
│   │   └── city, address
│   │
│   ├── Список товаров корзины
│   │   ├── name × quantity @ price
│   │   └── ИТОГО: getCartTotal()
│   │
│   └── Кнопка "Подтвердить заказ" (POST)
│
├── POST-режим (создание)
│   ├── createOrder(customer_id, cart_items)
│   │   ├── INSERT INTO orders (status='pending')
│   │   └── INSERT INTO order_items (× N товаров)
│   ├── clearCart()
│   └── redirect → pay.php?order_id=X
│
└── footer.php
```

## Страница оплаты

**Файл:** `pay.php`

### Требования для оплаты

- **Авторизация:** `requireLogin()`
- **Параметр:** GET `?order_id=` — ID заказа

### Проверка владения заказом

```php
$order_id = $_GET['order_id'];
$order = getOrder($order_id);

// Проверка: заказ принадлежит текущему пользователю
if (!$order || $order['customer_id'] != $_SESSION['user_id']) {
    header('Location: /users/profile.php');
    exit;
}
```

### Отображение информации о заказе

- Номер заказа: `id_or`
- Сумма: `total`
- Статус: `status` (pending / payment / completed / cancelled)
- Дата создания: `created_at`
- Список товаров: `getOrderItems($order_id)`

### Способы оплаты (демо-режим)

**Три варианта:**

| Способ | Иконка | Описание |
|---|---|---|
| Банковская карта | 💳 | Visa, MasterCard, МИР |
| СБП | 🏦 | Система быстрых платежей |
| Наличными | 💵 | При получении |

### JavaScript обработки оплаты

**Функция `processPayment(method)`**

```javascript
function processPayment(method) {
    // Демо-режим: показ alert и редирект
    const methods = {
        'card': 'Банковская карта',
        'sbp': 'СБП',
        'cash': 'Наличные'
    };
    
    alert(`Оплата через ${methods[method]} прошла успешно!`);
    window.location.href = '/users/profile.php';
}
```

**Примечание:** Это **демо-режим**. Реальная интеграция с платёжной системой не реализована.

### Статусы заказа и их отображение

| Статус | Цвет | Описание |
|---|---|---|
| `pending` | жёлтый (#f39c12) | Ожидает обработки |
| `payment` | синий (#3498db) | Ожидает оплаты |
| `completed` | зелёный (#27ae60) | Оплачено |
| `cancelled` | красный (#e74c3c) | Отменён |

## Полный поток заказа

```
1. Пользователь добавляет товары в корзину
   index.php / shop.php → POST /cart/add_to_cart.php → $_SESSION['cart']

2. Пользователь переходит в корзину
   /cart/cart.php → getCart() → таблица товаров
   (inline-редактирование количества, удаление)

3. Пользователь нажимает "Оформить заказ"
   /cart/cart.php → клик по кнопке → /checkout.php

4. checkout.php (требует авторизации)
   ├── GET: отображение данных покупателя и корзины
   └── POST: создание заказа
       ├── createOrder(customer_id, cart_items)
       │   ├── START TRANSACTION
       │   ├── INSERT INTO orders (status='pending')
       │   ├── INSERT INTO order_items (× N)
       │   └── COMMIT
       ├── clearCart()
       └── redirect → pay.php?order_id=X

5. pay.php?order_id=X
   ├── Проверка: customer_id == $_SESSION['user_id']
   ├── getOrder(order_id) → заказ
   ├── getOrderItems(order_id) → товары
   ├── Отображение 3 способов оплаты
   └── processPayment(method) → alert → redirect /users/profile.php

6. users/profile.php
   └── getCustomerOrders(customer_id) → история заказов
       └── Заказ отображается со статусом "pending"
```

## Транзакция создания заказа

**Файл:** `includes/functions.php` → `createOrder($customer_id, $cart_items)`

```php
function createOrder($customer_id, $cart_items) {
    global $mysqli;
    
    // Рассчёт общей суммы
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['product']['price'] * $item['quantity'];
    }
    
    // Начало транзакции
    $mysqli->begin_transaction();
    
    try {
        // 1. Создание заказа
        $stmt = $mysqli->prepare("
            INSERT INTO orders (customer_id, total, status, created_at) 
            VALUES (?, ?, 'pending', NOW())
        ");
        $stmt->bind_param("id", $customer_id, $total);
        $stmt->execute();
        $order_id = $mysqli->insert_id;
        
        // 2. Добавление позиций заказа
        $stmt = $mysqli->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
        foreach ($cart_items as $item) {
            $product_id = $item['product']['productId'];
            $quantity = $item['quantity'];
            $price = $item['product']['price'];
            $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
            $stmt->execute();
        }
        
        // Коммит
        $mysqli->commit();
        return $order_id;
        
    } catch (Exception $e) {
        // Откат
        $mysqli->rollback();
        return false;
    }
}
```

## Безопасность

| Аспект | Реализация |
|---|---|
| Авторизация | `requireLogin()` на checkout и pay |
| Владение заказом | Проверка `customer_id == $_SESSION['user_id']` в pay.php |
| Целостность | Транзакция (commit/rollback) при создании заказа |
| Пустая корзина | Редирект на /cart/cart.php |
| XSS | `e()` на всех выводах данных |
| SQL-инъекции | Prepared statements в createOrder |

## Демо-режим оплаты

**Важно:** `pay.php` работает в **демо-режиме**:

- Нет реальной интеграции с платёжными системами
- `processPayment()` просто показывает alert и редиректят
- Статус заказа **не меняется** на `payment` или `completed`
- Для изменения статуса администратор/модератор должен вручную сменить статус в панели управления

**Для продакшена потребуется:**

- Интеграция с платёжным агрегатором (ЮKassa, Robokassa, CloudPayments)
- Callback-обработка подтверждения оплаты
- Автоматическое обновление статуса заказа
- Обработка webhook'ов от платёжной системы
