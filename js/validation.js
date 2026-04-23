// js/validation.js
console.log("Validation.js загружен");

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, находимся ли мы на странице регистрации
    // Используем window.location.pathname для точности
    const currentPath = window.location.pathname;

    // Если путь не содержит /users/register.php, выходим
    if (!currentPath.includes('/users/register.php')) {
        console.log("Мы не на странице регистрации. Скрипт завершает работу.");
        return; 
    }

    console.log("Мы на странице регистрации. Инициализация...");

    const form = document.getElementById('registerForm');
    if (!form) {
        console.error("Форма регистрации registerForm не найдена!");
        return;
    }

    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passInput = document.getElementById('password');
    const confirmPassInput = document.getElementById('confirm_password');

    if (!usernameInput || !emailInput || !passInput || !confirmPassInput) {
        console.error("Один из полей ввода не найден!");
        return;
    }

    // SVG иконки
    const iconCheck = `<svg viewBox="0 0 24 24" style="width:100%;height:100%;fill:#28a745;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`;
    const iconCross = `<svg viewBox="0 0 24 24" style="width:100%;height:100%;fill:#dc3545;"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`;

    // Регулярное выражение для Email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // Функция установки статуса (с принудительными стилями)
    function setStatus(input, isValid, message) {
        const wrapper = input.closest('.input-with-feedback');
        if (!wrapper) return;

        const feedbackIcon = wrapper.querySelector('.input-status-icon');
        const feedbackMsg = wrapper.nextElementSibling;

        // Сброс классов input
        input.classList.remove('is-valid', 'is-invalid');
        
        // Сброс иконки
        if (feedbackIcon) {
            feedbackIcon.className = 'input-status-icon';
            feedbackIcon.innerHTML = '';
            // Принудительный сброс стилей
            feedbackIcon.style.visibility = 'hidden';
            feedbackIcon.style.opacity = '0';
        }
        
        // Сброс сообщения
        if (feedbackMsg) {
            feedbackMsg.textContent = '';
            feedbackMsg.classList.remove('error');
        }

        if (isValid === null) return;

        if (isValid) {
            input.classList.add('is-valid');
            if (feedbackIcon) {
                feedbackIcon.innerHTML = iconCheck;
                // Принудительная установка
                feedbackIcon.style.setProperty('visibility', 'visible', 'important');
                feedbackIcon.style.setProperty('opacity', '1', 'important');
            }
        } else {
            input.classList.add('is-invalid');
            if (feedbackIcon) {
                feedbackIcon.innerHTML = iconCross;
                feedbackIcon.style.setProperty('visibility', 'visible', 'important');
                feedbackIcon.style.setProperty('opacity', '1', 'important');
            }
            if (feedbackMsg) {
                feedbackMsg.textContent = message;
                feedbackMsg.classList.add('error');
            }
        }
    }

    // Функция проверки полей (Server)
    function checkField(input, type) {
        const value = input.value.trim();
        
        // Сброс
        setStatus(input, null);

        if (value.length < 3) return;

        // Проверка Email (клиентская)
        if (type === 'email') {
            if (!emailRegex.test(value)) {
                setStatus(input, false, 'Некорректный формат email');
                return;
            }
        }

        // AJAX запрос
        fetch(`/users/check_user.php?type=${type}&value=${encodeURIComponent(value)}`)
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    setStatus(input, true);
                } else {
                    setStatus(input, false, data.message);
                }
            })
            .catch(error => {
                console.error("Ошибка сети:", error);
                setStatus(input, false, "Ошибка проверки. Попробуйте позже.");
            });
    }

    // Функция проверки паролей
    function validatePasswords() {
        const p1 = passInput.value;
        const p2 = confirmPassInput.value;

        // Проверка длины
        if (p1.length >= 6) {
            setStatus(passInput, true);
        } else if (p1.length > 0) {
            setStatus(passInput, false, 'Слишком короткий пароль');
        } else {
            setStatus(passInput, null);
        }

        // Проверка совпадения
        if (p2.length > 0) {
            if (p1 === p2) {
                setStatus(confirmPassInput, true);
            } else {
                setStatus(confirmPassInput, false, 'Пароли не совпадают');
            }
        } else {
            setStatus(confirmPassInput, null);
        }
    }

    // Навешиваем обработчики
    let timeout;

    // Username
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => checkField(this, 'username'), 500);
        });
        usernameInput.addEventListener('change', function() {
            clearTimeout(timeout);
            checkField(this, 'username');
        });
    }

    // Email
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => checkField(this, 'email'), 500);
        });
        emailInput.addEventListener('change', function() {
            clearTimeout(timeout);
            checkField(this, 'email');
        });
    }

    // Password
    if (passInput) {
        passInput.addEventListener('input', function() {
            validatePasswords();
            if (confirmPassInput.value.length > 0) {
                validatePasswords();
            }
        });
    }

    // Confirm Password
    if (confirmPassInput) {
        confirmPassInput.addEventListener('input', function() {
            validatePasswords();
        });
    }
});
