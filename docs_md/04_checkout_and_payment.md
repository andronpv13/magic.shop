# 04 - Оформление заказа и оплата

## Оформление заказа (Checkout)

**Файл:** `checkout.php`

### Требования

- **Авторизация:** `requireLogin()` -- редирект на `/login.php` если не авторизован
- **Наличие товаров в корзине:** если корзина пуста -- редирект на `/cart/cart.php`

### Структура страницы

#### Информационный блок покупателя

Отображаются данные из БД (`getCustomer($user_id)`):

- Имя и фамилия
- Email
- Телефон
- Город
- Адрес

> **Только просмотр** -- редактирование доступно через `/users/profile.php`

#### Блок заказа

- Таблица товаров из корзины:
  - Название
  - Количество
  - Цена
  - Сумма позиции
- **Итоговая сумма** (`getCartTotal()`)

#### Кнопка подтверждения

- POST-форма: "Оформить заказ"
- При отправке вызывается `createOrder($customer_id, $cart_items)`

---

## Создание заказа

**Файл:** `includes/functions.php` → `createOrder($customer_id, $cart_items)`

### Процесс (транзакция)

```php
// 1. Начало транзакции
$mysqli->begin_transaction();

// 2. Вставка в orders
$stmt = $mysqli->prepare("INSERT INTO orders (customer_id, total, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("id", $customer_id, $total);
$stmt->execute();
$order_id = $mysqli->insert_id;

// 3. Вставка позиций в order_items
foreach ($cart_items as $item) {
    $stmt = $mysqli->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
    $stmt->execute();
}

// 4. Коммит транзакции
$mysqli->commit();

// 5. Очистка корзины
clearCart();

// 6. Возврат ID заказа
return $order_id;
```

### Обработка ошибок

```php
try {
    // ... транзакция ...
} catch (Exception $e) {
    $mysqli->rollback();
    // Вывод ошибки пользователю
}
```

### После создания заказа

- Редирект на `/pay.php?order_id=X`

---

## Страница оплаты

**Файл:** `pay.php`

### Проверки

1. **Авторизация:** `requireLogin()`
2. **Наличие order_id:** GET параметр `?order_id=`
3. **Проверка владения заказом:**

   ```php
   $order = getOrder($order_id);
   if ($order['customer_id'] != $_SESSION['user_id']) {
       // Редирект -- доступ запрещён
   }
   ```

### Отображение

- Детали заказа (`getOrder($order_id)`):
  - Номер заказа
  - Дата создания
  - Сумма
  - Статус
- Позиции заказа (`getOrderItems($order_id)`)

### Способы оплаты (демо-режим)

| Способ | Описание | Иконка |
|---|---|---|
| **Банковская карта** | Visa, Mastercard, МИР | 💳 |
| **СБП** | Система быстрых платежей | 📱 |
| **Наличными** | При получении | 💵 |

> ⚠️ **Все способы оплаты работают в демо-режиме** -- реальные платёжные системы не интегрированы

---

## Обработка оплаты (клиентский JS)

**Файл:** `js/script.js` → `processPayment()`

### Процесс

1. Пользователь выбирает способ оплаты (клик по карточке)
2. Вызов `processPayment(method)`
3. Показ alert: "Оплата через [метод] выполнена успешно (демо)"
4. Редирект на `/users/profile.php` (история заказов)

---

## Детали заказа для покупателя

**Файл:** `users/order_det_pay.php`

### Доступ

- GET параметр `?order_id=`
- Проверка `customer_id` -- покупатель видит только **свои** заказы

### Отображение заказа

- Информация о заказе (`getOrder($order_id)`)
- Позиции заказа (`getOrderItems($order_id)`)
- Статус заказа с цветным бейджем

---

## Статусы заказов

| Статус | Описание | Когда устанавливается | Цвет |
|---|---|---|---|
| `pending` | Ожидает обработки | При создании заказа | жёлтый (#f39c12) |
| `payment` | Ожидает оплаты | В демо-режиме (опционально) | синий (#3498db) |
| `completed` | Оплачено | Админ/модератор подтвердил | зелёный (#27ae60) |
| `cancelled` | Отменён | Админ отменил заказ | красный (#e74c3c) |

---

## Функции для работы с заказами

**Файл:** `includes/functions.php`

| Функция | Параметры | Описание |
|---|---|---|
| `createOrder($customer_id, $cart_items)` | customer_id, cart_items | Создание заказа (транзакция) |
| `getCustomerOrders($customer_id)` | customer_id | История заказов покупателя |
| `getOrder($order_id)` | order_id | Детали одного заказа |
| `getOrderItems($order_id)` | order_id | Позиции заказа |
