# Документация проекта magic.shop (Волшебная ЛАВКА)

> Полная документация интернет-магазина "Волшебная ЛАВКА", разработанного командой АВВА.

# Волшебная ЛАВКА - Интернет-магазин магических товаров

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-7.0%2B-blue.svg)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/mysql-5.6%2B-blue.svg)](https://mysql.com)

## Технологический стек

- Backend: PHP 7+ (mysqli, prepared statements)
- Database: MySQL / MariaDB (InnoDB, utf8mb4)
- Frontend: HTML5, CSS3 (CSS Variables, Grid, Flexbox), Vanilla JavaScript (Fetch API)
- Authentication: PHP Sessions + bcrypt
- Environment: .env file for configuration
- Logging: File-based logging with rotation

## О проекте

**MagicShop** — полнофункциональный интернет-магазин поддерживающий адаптивность, с многоуровневой системой ролей (admin / moderator / customer), корзиной покупок, оформлением заказов, оплатой и системой отзывов, а также с панелями управления для администратора и модератора, поддерживающими CRUD операции, с возможностью настройки и управления магазином, а также с поддержкой адаптивности и responsivity.

**Основное правило:**

- Не вносить изменения в документацию, если они не были согласованы с авторами проекта.

---

## Оглавление документации

### Фаза 1: Основы проекта

| Документ | Описание |
|---|---|
| [01 — Обзор проекта и база данных] | Тип проекта, технологический стек, структура файлов, схема БД, дефолтные пользователи, зависимости между файлами |
| [02 — Аутентификация и безопасность] | Система сессий, хелперы авторизации, универсальный логин, регистрация, меры безопасности, смена/сброс пароля |
| [03 — Основные функции и API] | Функции для всех (functions.php), администратора (functions_adm.php), модератора (functions_md.php), AJAX JSON API |

### Фаза 2: Публичные страницы

| Документ | Описание |
|---|---|
| [04 — Публичные страницы] | Главная страница (index.php), каталог товаров (shop.php), страница входа (login.php), шапка (header.php) и подвал сайта (footer.php) |
| [05 — Система корзины] | Архитектура корзины (session-based), серверные функции, AJAX API, страница корзины (basket.php), клиентский JavaScript (basket.js) |
| [06 — Оформление заказа и оплата] | Оформление заказа (checkout.php), создание заказа (транзакция), страница оплаты (pay.php), демо-режим, статусы заказов |

### Фаза 3: Панели управления

| Документ | Описание |
|---|---|
| [07 — Панель администратора] | Дашборд, управление товарами/заказами/пользователями/отзывами, статистика, CRUD операции |
| [08 — Панель модератора] | Дашборд, управление своими товарами, просмотр всех заказов, проверка владения, ограничения модератора |
| [09 — Личный кабинет покупателя] | Регистрация, редактирование профиля, смена пароля, история заказов, система отзывов |

### Фаза 4: Фронтенд и развёртывание

| Документ | Описание |
|---|---|
| [10 — Фронтенд ресурсы] | CSS (variables, компоненты, адаптивность), JavaScript (AJAX корзина, уведомления, валидация), изображения |
| [11 — Развёртывание и настройка] | Системные требования, установка БД, настройка веб-сервера, конфигурация PHP, продакшен-настройки, чек-лист, troubleshooting |

---

## Быстрый старт

### 1. Создание базы данных

```sql
--- Удаление базы данных если существует ---
DROP DATABASE IF EXISTS shop_db;
--- Создание базы данных ---
CREATE DATABASE shop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Импорт схемы

```bash
mysql -u root -p shop_db < database/schema.sql
```

### 3. Настройка подключения

Отредактировать `includes/config/.env`:

```bash
DB_HOST=localhost
DB_NAME=shop_db
DB_USER=root
DB_PASS=toor
APP_ENV=development
SESSION_LIFETIME=600
```

**⚠️ Важно:** Проект использует переменные окружения через файл `.env`. Не редактируйте `config.php` напрямую.

### 4. Запуск

Открыть в браузере: `http://localhost`

### 5. Вход администратора

- URL: `http://localhost/login.php`
- Логин: `root`
- Пароль: `password` ваш пароль для дашборда, желательно сменить

---

## Роли пользователей

| Роль | Описание | Доступ |
|---|---|---|
| **admin** | Полный доступ | Все функции: товары, заказы, пользователи, отзывы, статистика |
| **moderator** | Управление товарами | Только свои товары, просмотр всех заказов, смена статуса заказов |
| **customer** | Покупатель | Каталог, корзина, заказы, отзывы, профиль |

---

## Структура проекта

```
MagicShop/
├── index.php                 # Главная страница со слайдером товаров (3 последних добавленных товара)
├── shop.php                  # Каталог всех товаров (фильтр по категориям, детальная страница товара)
├── login.php                 # Авторизация (единая страница входа для всех ролей)
├── logout.php                # Выход (единый выход из сессии авторизации)
├── checkout.php              # Оформление заказа (адрес доставки, способ доставки, способ оплаты)
├── pay.php                   # Оплата (СБП, банковская карта, наличные - демо-режим)
│
├── admin/                    # Панель администратора
│   ├── index.php             # Дашборд со статистикой (6 карточек: покупатели, модераторы, товары, заказы, выручка, отзывы)
│   ├── cab.php               # Личный кабинет админа: логин, email, имя, фамилия, телефон
│   ├── edit_cab.php          # Редактирование профиля админа: логин (проверка уникальности), пароль, email (проверка уникальности), имя, фамилия, телефон
│   ├── settings.php          # Настройки сайта: оформление, фон, шрифт, фавикон, логотип
│   ├── update_settings.php   # Обработчик обновления настроек сайта
│   ├── add_product.php       # Добавление товара (фото, описание, стоимость, категория, остаток, новый флаг)
│   ├── edit_product.php      # Редактирование товара (все поля + смена категории)
│   ├── products.php          # Список всех товаров с редактированием и удалением
│   ├── manage_category.php   # Управление категориями товаров (добавление, удаление, подсчёт товаров)
│   ├── manage_orders.php     # Управление заказами (фильтр по статусу, смена статуса)
│   ├── order_details.php     # Детали заказа + смена статуса
│   ├── update_order_status.php # API: обновление статуса заказа
│   ├── manage_users.php      # CRUD пользователей и модераторов (добавление, удаление, сброс пароля)
│   ├── manage_review.php     # Просмотр и удаление отзывов
│   └── hash.php              # Утилита генерации хеша пароля
│
├── moderator/                # Панель модератора
│   ├── index_md.php          # Дашборд модератора (3 карточки: мои товары, все заказы, общая стоимость)
│   ├── cab_md.php            # Личный кабинет модератора: логин, email, имя, фамилия, телефон
│   ├── edit_cab_md.php       # Редактирование профиля модератора: логин (проверка уникальности), пароль, email (проверка уникальности), имя, фамилия, телефон
│   ├── add_product_md.php    # Добавление товара (created_by = ID модератора)
│   ├── edit_product_md.php   # Редактирование ТОЛЬКО своих товаров (проверка владения)
│   ├── products_md.php       # Список "Мои товары" с редактированием и удалением
│   ├── manage_orders_md.php  # Управление заказами (как у админа, фильтр по статусу)
│   ├── order_details_md.php  # Детали заказов + смена статуса (как у админа)
│
├── users/                    # Личный кабинет покупателя
│   ├── register.php          # Регистрация (после успешной регистрации редирект на edit_profile.php)
│   ├── check_user.php        # AJAX: проверка уникальности логина и email, валидация пароля
│   ├── profile.php           # Профиль: данные профиля + ссылки на редактор, историю заказов, отзывы
│   ├── edit_profile.php      # Редактирование профиля (логин, имя, отчество, фамилия, индекс, область, населённый пункт, улица, дом, квартира, телефон, email, пароль)
│   ├── orders.php            # История заказов (вверху самый новый)
│   ├── order_detail.php      # Детали заказа покупателя
│   └── review.php            # Страница отзывов (только купленные товары, требует авторизации)
│
├── basket/                   # Корзина (AJAX API)
│   ├── basket.php            # Страница корзины
│   ├── add_basket.php        # API: добавление товара в корзину
│   ├── remove_basket.php     # API: удаление товара из корзины
│   └── update_basket.php     # API: обновление количества в реальном времени
│
├── includes/                 # Ядро системы
│   ├── config/
│   │   └── .env              # Переменные окружения (БД, настройки сессии, режим приложения)
│   ├── config.php            # Базовая конфигурация, подключение к БД, сессии, CSRF, логирование
│   ├── functions.php         # Основные функции для всех: товары, корзина, регистрация, заказы, отзывы
│   ├── functions_adm.php     # Функции только для администратора: CRUD пользователей/товаров/заказов/отзывов, статистика
│   ├── functions_md.php      # Функции только для модератора: CRUD своих товаров, модерация заказов
│   ├── header.php            # Шапка сайта: логотип, навигация, корзина, пользователь
│   └── footer.php            # Подвал сайта: копирайт, команда АВВА
│
├── css/                      # Стили сайта
│   └── magic.css             # Все стили: CSS Variables, Grid, Flexbox, адаптивность, print styles
│
├── js/                       # JavaScript скрипты
│   ├── basket.js             # Обработка операций корзины (AJAX)
│   ├── detail.js             # Скрипт детальной страницы товара
│   ├── main.js               # Общие AJAX функции для всех страниц
│   └── validation.js         # AJAX валидация форм (логин, email, пароль)
│
├── database/                 # Установка базы данных
│   ├── schema.sql            # Схема БД + дефолтные пользователи
│   └── setup.php             # Скрипт установки БД
│
└── images/                   # Каталог изображений
    ├── favicon.svg           # Фавиконка сайта
    ├── logo.png              # Логотип сайта
    ├── logo1.png             # Альтернативный логотип
    ├── logo_.png             # Дополнительный логотип
    ├── main.png              # Главное изображение
    ├── main8х1.png           # Баннер 8x1
    ├── no_photo.png          # Изображение по умолчанию для товаров без фото
    ├── index.html            # Защита от прямого доступа к файлам
    ├── product/              # Каталог загружаемых фото товаров (создаётся динамически)
    └── background/           # Каталог загрузки фонов для сайта (fon.jpg, fon.svg, fon.png)

└── logs/                     # Логи приложения (создаётся автоматически)
    ├── app_YYYY-MM-DD.log    # Ежедневные логи действий
    └── php_errors.log        # Логи ошибок PHP (в production режиме)
```

---

## Схема базы данных

### Таблицы

| Таблица | Описание | Основные поля |
|---|---|---|
| **users** | Пользователи (admin, moderator, customer) | id, username, password, email, first_name, last_name, middle_name, phone, zip_code, region, city, street, house, apartment, role, last_login, failed_attempts, locked_until, created_at |
| **categories** | Категории товаров | id, name, description, created_at |
| **products** | Товары с привязкой к создателю и категории | id, name, description, price, image, category_id, stock, is_new, active, created_by, created_at, updated_at |
| **orders** | Заказы покупателей | id, user_id, total, delivery_address, comment, payment_method, status, created_at, updated_at, order_token |
| **order_items** | Позиции заказов | id, order_id, product_id, quantity, price |
| **reviews** | Отзывы покупателей | id, user_id, product_id, rating, comment, is_approved, created_at, updated_at |

### Индексы

| Таблица | Индексы |
|---|---|
| **users** | idx_users_email, idx_users_username, idx_users_role |
| **categories** | idx_categories_name |
| **products** | idx_products_category_id, idx_products_price, idx_products_active, idx_products_is_new, idx_products_created_by |
| **orders** | idx_orders_user_id, idx_orders_status, idx_orders_created_at, idx_orders_token |
| **order_items** | idx_order_items_order_id, idx_order_items_product_id |
| **reviews** | idx_reviews_product_id, idx_reviews_user_id, idx_reviews_approved |

### Внешние ключи

| Таблица | Ссылка | Действие |
|---|---|---|
| products.category_id | categories.id | ON DELETE RESTRICT |
| products.created_by | users.id | ON DELETE SET NULL |
| orders.user_id | users.id | ON DELETE CASCADE |
| order_items.order_id | orders.id | ON DELETE CASCADE |
| order_items.product_id | products.id | ON DELETE RESTRICT |
| reviews.user_id | users.id | ON DELETE CASCADE |
| reviews.product_id | products.id | ON DELETE CASCADE |

### Статусы заказов

| Статус | Описание | Цвет |
|---|---|---|
| `pending` | Ожидает обработки | жёлтый |
| `payment` | Ожидает оплаты | синий |
| `completed` | Оплачен | зелёный |
| `cancelled` | Отменён | красный |

---

## AJAX API (корзина)

| Метод | URL | Параметры | Ответ |
|---|---|---|---|
| POST | `/basket/add_basket.php` | product_id, quantity | {success, message, basket_count} |
| POST | `/basket/remove_basket.php` | product_id | {success, message, basket_count, basket_total} |
| POST | `/basket/update_basket.php` | product_id, quantity | {success, message, basket_count, item_total, basket_total} |

---

## Безопасность

| Мера | Реализация |
|---|---|
| CSRF-защита | Токены во всех POST-формах, обновление токена после использования, проверка через hash_equals() |
| SQL-инъекции | Prepared statements (mysqli) везде, строгая типизация параметров |
| XSS | htmlspecialchars(ENT_QUOTES | ENT_HTML5, 'UTF-8') для всего пользовательского ввода |
| Пароли | bcrypt (password_hash с PASSWORD_DEFAULT), минимальная длина 6 символов |
| Session hijacking | httponly=true, samesite=Strict, use_only_cookies=1 |
| Session timeout | 10 мин неактивности (gc_maxlifetime=600) |
| Session fixation | Регенерация ID каждые 30 минут (SESSION_REGENERATION_INTERVAL) |
| File upload | Проверка MIME-типа (image/jpeg, png, gif, webp), лимит 5MB, уникальные имена файлов |
| Rate limiting | Ограничение попыток входа (5 попыток за 15 минут) |
| Environment variables | Конфигурация БД через .env файл |
| Error logging | Логирование ошибок в файлы (app_YYYY-MM-DD.log, php_errors.log) |
| Input validation | Валидация email с проверкой DNS, валидация длины полей |

---

## Поток заказа

```
1. Каталог (shop.php)
   └─→ Добавить в корзину (AJAX)
       └─→ Корзина (basket/basket.php)
           └─→ Оформление (checkout.php)
               └─→ Создание заказа (createOrder)
                   └─→ Оплата (pay.php) [демо]
                       └─→ Профиль (users/profile.php)
```

---

## Дефолтные пользователи

| Роль | Логин | Пароль |
|---|---|---|
| admin | root | password |
| moderator | moderator | password |

**⚠️ Обязательно смените пароли после установки!**

---

## Документация по файлам

### Ядро (includes/)

| Файл | Назначение |
|---|---|
| [config/.env] | Переменные окружения: DB_HOST, DB_NAME, DB_USER, DB_PASS, APP_ENV, SESSION_LIFETIME |
| [config.php] | Подключение к БД, настройки сессии, CSRF-токены, хелперы авторизации, логирование, rate limiting |
| [functions.php] | Функции покупателя: товары, категории, корзина, регистрация, заказы, отзывы, утилиты. **Важно:** функция `updateOrderStatus()` требует предварительной проверки прав через `requireAdmin()` или `requireModerator()` в вызывающем коде |
| [functions_adm.php] | Функции админа: CRUD пользователей/товаров/заказов/отзывов/категорий, статистика, загрузка изображений |
| [functions_md.php] | Функции модератора: CRUD своих товаров, модерация заказов, проверка владения товарами |
| [header.php] | Шапка сайта: логотип, навигация, корзина, пользователь, меню по ролям |
| [footer.php] | Подвал сайта: копирайт, команда АВВА, контактная информация |

### Публичные страницы

| Файл | Назначение |
|---|---|
| [index.php] | Главная: hero-баннер, новые товары (3), последние отзывы (5) |
| [shop.php] | Каталог всех товаров с фильтром по категориям + детальная страница товара |
| [login.php] | Универсальная авторизация для всех ролей (admin, moderator, customer) |
| [logout.php] | Выход для всех ролей (уничтожение сессии) |
| [checkout.php] | Оформление заказа (требует авторизации): адрес доставки, комментарий, способ оплаты |
| [pay.php] | Страница выбора способа оплаты (демо-режим): СБП, карта, наличные |

### Панель администратора

| Файл | Назначение |
|---|---|
| [admin/index.php] | Дашборд со статистикой (6 карточек: покупатели, модераторы, товары, заказы, выручка, отзывы) |
| [admin/cab.php] | Личный кабинет админа: просмотр профиля (логин, email, имя, фамилия, телефон) |
| [admin/edit_cab.php] | Редактирование профиля админа: логин (проверка уникальности), пароль, email (проверка уникальности), имя, фамилия, телефон |
| [admin/settings.php] | Настройки сайта: оформление, фон (svg/jpg/png), шрифт, фавикон, логотип |
| [admin/update_settings.php] | Обработчик обновления настроек сайта |
| [admin/add_product.php] | Добавление товара: название, описание, цена, категория, остаток, флаг "новый", загрузка фото |
| [admin/edit_product.php] | Редактирование любого товара: все поля + смена категории |
| [admin/products.php] | Список всех товаров с редактированием и удалением (мягкое удаление через active=0) |
| [admin/manage_category.php] | Управление категориями: добавление, удаление, подсчёт товаров в категории |
| [admin/manage_orders.php] | Управление заказами: фильтр по статусу, смена статуса |
| [admin/order_details.php] | Детали заказа: состав заказа, данные покупателя, смена статуса |
| [admin/update_order_status.php] | AJAX API: обновление статуса заказа |
| [admin/manage_users.php] | CRUD пользователей и модераторов: добавление, удаление, сброс пароля |
| [admin/manage_review.php] | Просмотр и удаление отзывов |
| [admin/hash.php] | Утилита генерации хеша пароля (для отладки) |

### Панель модератора

| Файл | Назначение |
|---|---|
| [moderator/index_md.php] | Дашборд модератора (3 карточки: мои товары, все заказы, общая стоимость товаров) |
| [moderator/cab_md.php] | Личный кабинет модератора: просмотр профиля (логин, email, имя, фамилия, телефон) |
| [moderator/edit_cab_md.php] | Редактирование профиля модератора: логин (проверка уникальности), пароль, email (проверка уникальности), имя, фамилия, телефон |
| [moderator/add_product_md.php] | Добавление товара (created_by = ID модератора) |
| [moderator/edit_product_md.php] | Редактирование ТОЛЬКО своих товаров (проверка владения через isProductOwner) |
| [moderator/products_md.php] | Список "Мои товары" с редактированием и удалением |
| [moderator/manage_orders_md.php] | Управление заказами (как у админа, фильтр по статусу) |
| [moderator/order_details_md.php] | Детали заказов + смена статуса (как у админа) |

### Личный кабинет покупателя

| Файл | Назначение |
|---|---|
| [users/register.php] | Регистрация нового пользователя (редирект на edit_profile.php после успеха) |
| [users/check_user.php] | AJAX: проверка уникальности логина и email, валидация пароля (длина, совпадение) |
| [users/profile.php] | Профиль: данные пользователя, ссылки на редактор профиля, историю заказов, отзывы |
| [users/edit_profile.php] | Редактирование профиля: логин, email, имя, отчество, фамилия, адрес (индекс, область, город, улица, дом, квартира), телефон, смена пароля |
| [users/orders.php] | История заказов покупателя (сортировка по дате, новые сверху) |
| [users/order_detail.php] | Детали заказа: состав заказа, статус, сумма |
| [users/review.php] | Оставить отзыв на купленный товар (требуется авторизация, только товары со статусом completed) |

### Корзина (AJAX API)

| Файл | Назначение |
|---|---|
| [basket/basket.php] | Страница корзины: карточки товаров с кнопками +, -, удалить, блок "Итого" (количество, сумма) |
| [basket/add_basket.php] | JSON API: добавление товара в корзину (product_id, quantity) |
| [basket/remove_basket.php] | JSON API: удаление товара из корзины (product_id) |
| [basket/update_basket.php] | JSON API: обновление количества в реальном времени (product_id, quantity) |

### Ресурсы

| Файл | Назначение |
|---|---|
| [css/magic.css] | Все стили: CSS Variables, Grid, Flexbox, адаптивность (@media), print styles |
| [js/basket.js] | JavaScript: AJAX операции корзины (добавление, удаление, обновление количества) |
| [js/detail.js] | JavaScript: скрипт детальной страницы товара |
| [js/main.js] | JavaScript: общие AJAX функции для всех страниц |
| [js/validation.js] | JavaScript: AJAX валидация форм в реальном времени (логин, email, пароль) |
| [database/schema.sql] | Схема БД: 6 таблиц, индексы, внешние ключи, дефолтные пользователи и категории |
| [database/setup.php] | Скрипт установки БД (опционально) |
| [images/no_photo.png] | Изображение-заглушка для товаров без фото (используется функцией getProductImage) |
| [images/favicon.svg] | Иконка для браузера |
| [images/logo.png] | Основной логотип сайта |
| [images/logo1.png] | Альтернативный логотип |
| [images/logo_.png] | Дополнительный логотип |
| [images/main.png] | Главное изображение для главной страницы |
| [images/main8х1.png] | Баннер 8:1 для главной страницы |

---

## Команда

**Разработчик:** АВВА
**Год:** 2025
