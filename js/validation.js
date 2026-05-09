// Валидация форм на стороне клиента

document.addEventListener('DOMContentLoaded', function() {
    // Валидация формы регистрации
    const registerForm = document.querySelector('.register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const username = this.querySelector('[name="username"]');
            const email = this.querySelector('[name="email"]');
            const password = this.querySelector('[name="password"]');
            const confirmPassword = this.querySelector('[name="confirm_password"]');

            let errors = [];

            if (username && username.value.length < 3) {
                errors.push('Имя пользователя должно быть не менее 3 символов');
            }

            if (email && !isValidEmail(email.value)) {
                errors.push('Некорректный email адрес');
            }

            if (password && password.value.length < 6) {
                errors.push('Пароль должен быть не менее 6 символов');
            }

            if (password && confirmPassword && password.value !== confirmPassword.value) {
                errors.push('Пароли не совпадают');
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });
    }

    // Валидация формы заказа
    const orderForm = document.querySelector('.order-form');
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            const name = this.querySelector('[name="name"]');
            const phone = this.querySelector('[name="phone"]');

            let errors = [];

            if (name && name.value.length < 2) {
                errors.push('Введите корректное имя');
            }

            if (phone && !isValidPhone(phone.value)) {
                errors.push('Введите корректный номер телефона');
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });
    }

    // Валидация формы отзыва
    const reviewForm = document.querySelector('.review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            const rating = this.querySelector('[name="rating"]');
            const comment = this.querySelector('[name="comment"]');

            let errors = [];

            if (rating && !rating.value) {
                errors.push('Выберите оценку');
            }

            if (comment && comment.value.length < 10) {
                errors.push('Комментарий должен быть не менее 10 символов');
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });
    }
});

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function isValidPhone(phone) {
    const re = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/;
    return re.test(phone.replace(/\s/g, ''));
}
