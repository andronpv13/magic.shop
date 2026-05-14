// js/main.js
console.log("Main.js загружен");

document.addEventListener('DOMContentLoaded', function() {
    // --- 1. БУТЕРБРОД-МЕНЮ (HAMBURGER MENU) ---
    const hamburger = document.getElementById('hamburger-menu');
    const navLinks = document.getElementById('nav-links');

    if (hamburger && navLinks) {
        console.log('Hamburger menu found, adding click listener');

        // Обработчик клика по кнопке гамбургера
        hamburger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Переключаем классы active
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('active');

            console.log('Menu toggled:', navLinks.classList.contains('active'));
        });

        // Закрываем меню при клике вне его области
        document.addEventListener('click', function(e) {
            if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
                hamburger.classList.remove('active');
                navLinks.classList.remove('active');
            }
        });

        // Закрываем меню при клике на ссылку
        const navLinkItems = navLinks.querySelectorAll('.nav-link');
        navLinkItems.forEach(function(link) {
            link.addEventListener('click', function() {
                hamburger.classList.remove('active');
                navLinks.classList.remove('active');
            });
        });
    } else {
        console.log('Hamburger or nav-links not found - may be on non-header page');
    }

    // --- 2. МЕНЮ ПОЛЬЗОВАТЕЛЯ ---
    const userMenuTrigger = document.querySelector('.js-user-menu-trigger');
    const userDropdownMenu = document.getElementById('user-dropdown-menu');

    if (userMenuTrigger && userDropdownMenu) {
        // Скрываем меню по умолчанию через JS (чтобы не мерцало при загрузке, если нужно)
        // Но лучше оставить display: none в CSS, как мы делали ранее.
        // userDropdownMenu.style.display = 'none';

        userMenuTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isVisible = userDropdownMenu.style.display === 'block';
            userDropdownMenu.style.display = isVisible ? 'none' : 'block';
        });

        document.addEventListener('click', function(e) {
            if (!userMenuTrigger.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.style.display = 'none';
            }
        });
    } else {
        // console.log("initUserMenu: Элементы меню пользователя не найдены. Проверьте ID в HTML.");
    }

    // --- 3. ФИЛЬТРЫ КАТЕГОРИЙ НА СТРАНИЦЕ МАГАЗИНА ---
    const categoryButtons = document.querySelectorAll('.category-btn');

    if (categoryButtons.length > 0) {
        categoryButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                // Удаляем активный класс у всех кнопок
                categoryButtons.forEach(function(b) {
                    b.classList.remove('active');
                });

                // Добавляем активный класс нажатой кнопке
                this.classList.add('active');

                // Получаем категорию из data-атрибута
                const category = this.getAttribute('data-category');

                // Формируем новый URL
                const url = new URL(window.location.href);
                if (category) {
                    url.searchParams.set('category', category);
                } else {
                    url.searchParams.delete('category');
                }

                // Переходим по новому URL
                window.location.href = url.toString();
            });
        });
    }
<<<<<<< HEAD
});
=======
});
>>>>>>> 17aa9fe80430601b55ac05d1a95d326b8163eefa
