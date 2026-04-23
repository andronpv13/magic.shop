# 01 — Обзор проекта и база данных

## Тип и назначение проекта

**MagicShop** (брендирован как "Волшебная ЛАВКА") — полнофункциональный интернет-магазин с многоуровневой системой ролей (admin / moderator / customer), корзиной покупок, оформлением заказов, демо-оплатой и системой отзывов. Проект разработан командой АВВА.

## Технологический стек

| Компонент | Технология |
|---|---|
| Backend | PHP 7+ (mysqli, prepared statements) |
| Database | MySQL / MariaDB (InnoDB, utf8mb4) |
| Frontend | HTML5, CSS3 (CSS Variables, Grid, Flexbox), Vanilla JavaScript (Fetch API) |
| Session | PHP Sessions (автосмена ID каждые 30 мин, таймаут 10 мин) |
| Passwords | bcrypt (PASSWORD_BCRYPT) |
| Server | Apache/Nginx (Windows, путь `e:\serwers\home\shopai`) |

## Структура файлов

```
e:\serwers\home\shopai/
├── index.php                 # Главная: hero-секция, новые товары (3 шт), последние отзывы (3 шт)
├── shop.php                  # Каталог всех товаров + детальная страница товара (GET ?id=)
├── login.php                 # Универсальная авторизация для всех ролей
├── checkout.php              # Оформление заказа (требует авторизации)
├── pay.php                   # Страница выбора способа оплаты (демо-режим)
│
├── admin/                    # Панель администратора (тёмная тема #2c3e50)
│   ├── index.php             # Дашборд со статистикой
│   ├── cab.php               # Личный кабинет админа для смены пароля, email, имя, фамилия, изменение оформления сайта (фон, шрифт, логотип)
│   ├── add_product.php       # Добавление товара с загрузкой фото, указание продавца (чекбокс с возможностью отключения показа продавца)
│   ├── edit_product.php      # Редактирование товара (GET ?id=) и продавца (чекбокс с возможностью отключения показа продавца)
│   ├── products.php          # Список всех товаров с продавцами
│   ├── manage_orders.php     # Управление заказами (фильтр по статусу)
│   ├── order_details.php     # Детали заказа + смена статуса
│   ├── manage_users.php      # CRUD пользователей: добавление модераторов, удаление, сброс паролей
│   ├── manage_review.php     # Просмотр, изменение и удаление отзывов
│   └── logout.php            # Выход (уничтожение сессии)
│
├── moderator/                # Панель модератора (синяя тема #2980b9)
│   ├── index_md.php          # Дашборд модератора со статистикой
│   ├── cab_md.php            # Личный кабинет модератора для смены пароля, email, имя, фамилия
│   ├── add_product_md.php    # Добавление товара, указание продавца (чекбокс с возможностью отключения показа продавца), (created_by = модератор)
│   ├── edit_product_md.php   # Редактирование ТОЛЬКО своих товаров и продавца (чекбокс с возможностью отключения показа продавца)
│   ├── products_md.php       # Список "Мои товары" (фильтр по created_by)
│   ├── manage_orders_md.php  # Просмотр всех заказов (фильтр по статусу)
│   ├── order_details_md.php  # Детали заказа + смена статуса
│   └── logout_md.php         # Выход (уничтожение сессии)
│
├── users/                    # Личный кабинет покупателя
│   ├── register.php          # Регистрация нового пользователя
│   ├── profile.php           # Профиль: редактирование данных, смена пароля, история заказов, отзывы
│   ├── order_det_pay.php     # Детали заказа покупателя
│   └── logout_user.php       # Выход (уничтожение сессии)
│
├── cart/                     # Корзина (AJAX API)
│   ├── cart.php              # Страница корзины 
│   ├── add_to_cart.php       # JSON API: добавление товара
│   ├── remove_from_cart.php  # JSON API: удаление товара
│   └── update_cart.php       # JSON API: обновление количества в реальном времени
│
├── includes/                 # Ядро системы
│   ├── config.php            # Подключение к БД, настройки сессии, хелперы авторизации
│   ├── functions.php         # Функции покупателя: товары, корзина, регистрация, логин, заказы, отзывы
│   ├── functions_adm.php     # Функции админа: CRUD пользователей/товаров/заказов/отзывов, статистика
│   ├── functions_md.php      # Функции модератора: CRUD своих товаров, просмотр заказов
│   ├── header.php            # Шапка сайта (логотип, навигация, корзина, пользователь)
│   └── footer.php            # Подвал сайта (копирайт, команда АВВА)
│
├── css/style.css             # Основной CSS (CSS Variables, Grid, адаптивность, print styles)
├── js/script.js              # Основной JS (AJAX корзина, уведомления, анимации)
├── database                  # Установка базы данных
│   ├── schema.sql            # Схема БД + дефолтные пользователи
│   └── setup.php             # Скрипт утановки БД
└── images                  # Каталог изображений
     ├── product            # Каталог загружаемых фото товаров (создаётся динамически)
     └── background         # Каталог фонов для сайта (fon.jpg — фон body)
```

## Схема базы данных

База: `shop_db`, кодировка `utf8mb4`, движок `InnoDB`.

### Таблица `users`

| Колонка | Тип | Описание |
|---|---|---|
| id | INT AUTO_INCREMENT PK | ID пользователя |
| username | VARCHAR(100) UNIQUE | Логин |
| first_name | VARCHAR(100) | Имя |
| last_name | VARCHAR(100) | Фамилия |
| email | VARCHAR(255) UNIQUE | Email (используется как логин) |
| password | VARCHAR(255) | bcrypt hash |
| phone | VARCHAR(20) | Телефон |
| city | VARCHAR(100) | Город |
| address | TEXT | Адрес доставки |
| role | ENUM('admin','moderator','customer') | Роль |
| created_at | TIMESTAMP | Дата регистрации |

### Таблица `products`

| Колонка | Тип | Описание |
|---|---|---|
| productId | INT AUTO_INCREMENT PK | ID товара |
| name | VARCHAR(255) | Название |
| description | TEXT | Описание |
| price | DECIMAL(10,2) | Цена |
| image | VARCHAR(255) | Путь к фото (images/product/...) |
| created_by | INT FK → users(id) | Кто создал (ON DELETE SET NULL) |
| created_at | TIMESTAMP | Дата добавления |

### Таблица `orders`

| Колонка | Тип | Описание |
|---|---|---|
| id_or | INT AUTO_INCREMENT PK | ID заказа |
| customer_id | INT FK → users(id) | Покупатель (ON DELETE CASCADE) |
| total | DECIMAL(10,2) | Сумма |
| status | ENUM('pending','completed','cancelled','payment') | Статус |
| created_at | TIMESTAMP | Дата создания |

### Таблица `order_items`

| Колонка | Тип | Описание |
|---|---|---|
| id_it | INT AUTO_INCREMENT PK | ID позиции |
| order_id | INT FK → orders(id_or) | Заказ (ON DELETE CASCADE) |
| product_id | INT FK → products(productId) | Товар (ON DELETE CASCADE) |
| quantity | INT | Количество |
| price | DECIMAL(10,2) | Цена на момент покупки |

### Таблица `reviews`

| Колонка | Тип | Описание |
|---|---|---|
| id_rev | INT AUTO_INCREMENT PK | ID отзыва |
| product_id | INT FK → products(productId) | Товар (ON DELETE CASCADE) |
| customer_id | INT FK → users(id) | Автор (ON DELETE CASCADE) |
| comment | TEXT | Текст отзыва |
| created_at | TIMESTAMP | Дата |
| UNIQUE KEY (product_id, customer_id) | | Один отзыв на товар от покупателя |

## Дефолтные пользователи

| Роль | Логин | Пароль |
|---|---|---|
| admin | root | toor |
| moderator | moderator | toor |

## Зависимости между файлами

```
index.php           → includes/functions.php, includes/header.php, includes/footer.php, js/script.js
shop.php            → includes/functions.php, includes/header.php, includes/footer.php, js/script.js
login.php           → includes/functions.php (universalLogin), css/style.css
checkout.php        → includes/functions.php, includes/header.php, includes/footer.php
pay.php             → includes/functions.php, includes/header.php, includes/footer.php

admin/*.php         → includes/functions_adm.php + includes/functions.php (для shared функций)
moderator/*.php     → includes/functions_md.php + includes/functions.php
users/profile.php   → includes/functions.php
users/register.php  → includes/functions.php
users/order_det_pay.php → includes/functions.php

cart/cart.php       → includes/functions.php, includes/header.php, includes/footer.php, js/script.js
cart/add_to_cart.php → includes/functions.php
cart/remove_from_cart.php → includes/functions.php
cart/update_cart.php → includes/functions.php

includes/functions.php      → includes/config.php
includes/functions_adm.php  → includes/config.php
includes/functions_md.php   → includes/config.php
includes/header.php         → (автономный, session_start)
includes/footer.php         → (автономный)
```
