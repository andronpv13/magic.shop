document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. Утилита для получения CSRF токена ---
    function getCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (!metaTag) {
            console.error("CRITICAL: Мета-тег csrf-token не найден в <head>!");
            return '';
        }
        const token = metaTag.getAttribute('content');
        return token;
    }

    // --- 2. Утилита для обновления счетчика в блоке "Итого" ---
    function updateSummaryCount(count) {
        const summaryCountElement = document.getElementById('cart-summary-count');
        if (summaryCountElement) {
            summaryCountElement.textContent = count + ' шт.';
        }
    }

    // --- 3. Утилита для показа уведомлений (Toast) ---
    function showToast(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.position = 'fixed';
            container.style.top = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.textContent = message;

        // ИЗМЕНЕНО: Прозрачность и размеры
        toast.style.minWidth = '200px'; // Было 250px
        toast.style.padding = '10px 15px'; // Было 15px 20px
        toast.style.backgroundColor = type === 'success' ? 'rgba(40, 167, 69, 0.15)' : 'rgba(220, 53, 69, 0.15)';
        toast.style.color = '#fff';
        toast.style.marginBottom = '10px';
        toast.style.borderRadius = '4px';
        toast.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        toast.style.transform = 'translateY(-20px)';
        toast.style.fontSize = '14px'; // Уменьшен шрифт

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.opacity = '0.15'; // Финальная прозрачность
            toast.style.transform = 'translateY(0)';
        });

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                if (container.contains(toast)) {
                    container.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }

    // --- 4. Обработка кнопок "Добавить в корзину" ---
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');

    addToCartForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnContent = submitBtn.innerHTML;

            const token = getCsrfToken();
            if (!token) {
                showToast('Ошибка безопасности: токен не найден', 'error');
                return;
            }
            formData.append('csrf_token', token);

            submitBtn.disabled = true;
            submitBtn.innerHTML = '...';
            
            fetch('/cart/add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCountElement = document.getElementById('cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cart_count;
                        cartCountElement.style.transform = 'scale(1.5)';
                        setTimeout(() => cartCountElement.style.transform = 'scale(1)', 200);
                    }

                    // ОБНОВЛЯЕМ СЧЕТЧИК В БЛОКЕ "ИТОГО"
                    updateSummaryCount(data.cart_count);

                    showToast(data.message, 'success');
                } else {
                    showToast(data.message || 'Ошибка при добавлении товара', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Произошла ошибка соединения.', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
            });
        });
    });

    // --- 5. Обработка кнопок + и - (Количество) ---
    // Ищем кнопки по НОВЫМ классам
    document.querySelectorAll('.btn-quantity-plus, .btn-quantity-minus').forEach(function(button) {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.cart-quantity-input');
            if (!input) return;
            
            const action = this.getAttribute('data-action');
            let currentVal = parseInt(input.value);
            const maxVal = parseInt(input.getAttribute('max')) || 999;

            if (action === 'increase') {
                if (currentVal < maxVal) {
                    input.value = currentVal + 1;
                    input.dispatchEvent(new Event('change'));
                } else {
                    showToast('Достигнут лимит товара на складе', 'error');
                }
            } else if (action === 'decrease') {
                if (currentVal > 1) {
                    input.value = currentVal - 1;
                    input.dispatchEvent(new Event('change'));
                }
            }
        });
    });

    // --- 6. Обработка изменения количества в инпуте ---
    const quantityInputs = document.querySelectorAll('.cart-quantity-input');

    quantityInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const productId = this.getAttribute('data-product-id');
            const newQuantity = parseInt(this.value);
            const row = this.closest('.cart-item');
            const totalCell = row.querySelector('.cart-item-total');
            const cartTotalCell = document.querySelector('.cart-total-amount');

            if (newQuantity < 1) {
                showToast('Минимальное количество - 1', 'error');
                this.value = 1;
                return;
            }

            input.disabled = true;

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', newQuantity);
            formData.append('csrf_token', getCsrfToken());

            fetch('/cart/update_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (totalCell && data.item_total !== undefined) {
                        totalCell.textContent = data.item_total + ' ₽';
                    }
                    
                    if (cartTotalCell && data.cart_total !== undefined) {
                        cartTotalCell.textContent = data.cart_total + ' ₽';
                    }

                    const cartCountElement = document.getElementById('cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cart_count;
                    }

                    // !!! ВАЖНО: ОБНОВЛЯЕМ СЧЕТЧИК В БЛОКЕ "ИТОГО" !!!
                    updateSummaryCount(data.cart_count);

                    showToast(data.message, 'success');
                } else {
                    showToast(data.message || 'Ошибка обновления', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ошибка соединения', 'error');
            })
            .finally(() => {
                input.disabled = false;
            });
        });
    });

    // --- 7. Обработка удаления товара ---
    const deleteButtons = document.querySelectorAll('.btn-delete-item');

    deleteButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Вы уверены, что хотите удалить этот товар?')) {
                return;
            }

            const productId = this.getAttribute('data-product-id');
            const row = this.closest('.cart-item');

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('csrf_token', getCsrfToken());

            fetch('/cart/remove_from_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        if (document.querySelectorAll('.cart-item').length === 0) {
                            location.reload(); 
                        }
                    }, 300);

                    const cartCountElement = document.getElementById('cart-count');
                    if (cartCountElement) cartCountElement.textContent = data.cart_count;

                    const cartTotalCell = document.querySelector('.cart-total-amount');
                    if (cartTotalCell) cartTotalCell.textContent = data.cart_total + ' ₽';

                    // ОБНОВЛЯЕМ СЧЕТЧИК В БЛОКЕ "ИТОГО"
                    updateSummaryCount(data.cart_count);

                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Ошибка удаления', 'error');
            });
        });
    });
});
