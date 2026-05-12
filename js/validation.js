document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    if (!form) return;

    // Элементы формы
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');

    let validationState = {
        username: false,
        email: false,
        password: false,
        confirm: false
    };

    // --- Утилиты ---

    // Debounce для AJAX запросов
    function debounce(func, delay) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // Обновление состояния кнопки отправки
    function updateSubmitButton() {
        const isValid = Object.values(validationState).every(Boolean);
        if (submitBtn) {
            submitBtn.disabled = !isValid;
            submitBtn.style.opacity = isValid ? '1' : '0.6';
            submitBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
        }
    }

    // Установка статуса валидации для поля
    function setFieldStatus(inputElement, isValid, errorClass = 'error', successClass = 'success') {
        if (!inputElement) return;

        // Удаляем предыдущие классы
        inputElement.classList.remove(errorClass, successClass);

        // Добавляем новый статус
        if (isValid) {
            inputElement.classList.add(successClass);
        } else {
            inputElement.classList.add(errorClass);
        }
    }

    // Переключение видимости пароля
    function initPasswordToggle(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        const wrapper = input.parentElement;
        // Ищем или создаем кнопку глаза
        let toggleBtn = wrapper.querySelector('.password-toggle');

        if (!toggleBtn) {
            toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'password-toggle';
            toggleBtn.setAttribute('aria-label', 'Показать/скрыть пароль');
            wrapper.appendChild(toggleBtn);
        }

        toggleBtn.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            // Меняем иконку (через класс для CSS)
            toggleBtn.classList.toggle('active', !isPassword);
        });
    }

    // Получаем CSRF токен из meta тега
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // --- Логика валидации полей ---

    // 1. Валидация Логина
    const validateUsername = debounce(async () => {
        const value = usernameInput.value.trim();
        const regex = /^[a-zA-Zа-яА-ЯёЁ]{4,10}$/;

        if (!regex.test(value)) {
            validationState.username = false;
            setFieldStatus(usernameInput, false);
            updateSubmitButton();
            return;
        }

        // AJAX проверка уникальности с CSRF токеном
        try {
            const response = await fetch('/users/check_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: new URLSearchParams({ type: 'username', value: value })
            });
            const data = await response.json();

            // Если available: true, значит логин свободен
            validationState.username = data.available === true;
            setFieldStatus(usernameInput, validationState.username);
        } catch (e) {
            validationState.username = false;
            setFieldStatus(usernameInput, false);
        }
        updateSubmitButton();
    }, 500);

    // 2. Валидация Email
    const validateEmail = debounce(async () => {
        const value = emailInput.value.trim();
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!regex.test(value)) {
            validationState.email = false;
            setFieldStatus(emailInput, false);
            updateSubmitButton();
            return;
        }

        // AJAX проверка уникальности с CSRF токеном
        try {
            const response = await fetch('/users/check_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: new URLSearchParams({ type: 'email', value: value })
            });
            const data = await response.json();

            validationState.email = data.available === true;
            setFieldStatus(emailInput, validationState.email);
        } catch (e) {
            validationState.email = false;
            setFieldStatus(emailInput, false);
        }
        updateSubmitButton();
    }, 500);

    // 3. Валидация Пароля
    const validatePassword = () => {
        const value = passwordInput.value;
        const isValid = value.length >= 6;

        validationState.password = isValid;
        setFieldStatus(passwordInput, isValid);

        // Если поле подтверждения уже заполнено, перепроверяем его
        if (confirmPasswordInput.value) {
            validateConfirmPassword();
        }

        updateSubmitButton();
    };

    // 4. Валидация Подтверждения пароля
    const validateConfirmPassword = () => {
        const pass = passwordInput.value;
        const confirm = confirmPasswordInput.value;
        const isValid = confirm.length > 0 && pass === confirm;

        validationState.confirm = isValid;
        setFieldStatus(confirmPasswordInput, isValid);
        updateSubmitButton();
    };

    // --- Инициализация слушателей ---

    if (usernameInput) {
        usernameInput.addEventListener('input', validateUsername);
    }

    if (emailInput) {
        emailInput.addEventListener('input', validateEmail);
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', validatePassword);
        initPasswordToggle('password');
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', validateConfirmPassword);
        initPasswordToggle('confirm_password');
    }

    // Блокируем кнопку изначально
    updateSubmitButton();
});
