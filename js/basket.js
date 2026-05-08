document.addEventListener('DOMContentLoaded', function() {

    // Единый обработчик событий для всех кнопок корзины
    document.addEventListener('click', function(e) {

        // === УДАЛЕНИЕ ТОВАРА ===
        const removeBtn = e.target.closest('.remove-from-basket');
        if (removeBtn) {
            e.preventDefault();
            const productId = removeBtn.dataset.productId;
            const basketItem = removeBtn.closest('.basket-item');

            fetch('/basket/remove_basket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}&csrf_token=${getCsrf()}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.csrf_token) document.querySelector('meta[name="csrf-token"]').content = data.csrf_token;
                if (data.success) {
                    if (basketItem) basketItem.remove();
                    // ✅ Обновляем счетчики и шапку, и внутри корзины
                    updateBasketCounts(data.basket_count);
                    if (data.basket_total !== undefined) updateBasketTotal(data.basket_total);
                    showNotification(data.message, 'success');

                    // Если корзина опустела, перезагружаем страницу
                    if (document.querySelectorAll('.basket-item').length === 0) {
                        setTimeout(() => location.reload(), 500);
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(() => showNotification('Ошибка сети', 'error'));
        }

        // === ДОБАВЛЕНИЕ В КОРЗИНУ ===
        const addBtn = e.target.closest('.add-to-basket');
        if (addBtn) {
            e.preventDefault();
            const productId = addBtn.dataset.productId;
            const quantity = parseInt(addBtn.dataset.quantity) || 1;

            fetch('/basket/add_basket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}&quantity=${quantity}&csrf_token=${getCsrf()}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.csrf_token) document.querySelector('meta[name="csrf-token"]').content = data.csrf_token;
                if (data.success) {
                    // ✅ Обновляем счетчики и шапку, и внутри корзины
                    updateBasketCounts(data.basket_count);
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(() => showNotification('Ошибка сети', 'error'));
        }

        // === КНОПКИ ПЛЮС / МИНУС ===
        const increaseBtn = e.target.closest('.increase-quantity');
        if (increaseBtn) {
            e.preventDefault();
            const productId = increaseBtn.dataset.productId;
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            if (input) {
                const newQuantity = parseInt(input.value) + 1;
                input.value = newQuantity;
                updateQuantity(productId, newQuantity);
            }
        }

        const decreaseBtn = e.target.closest('.decrease-quantity');
        if (decreaseBtn) {
            e.preventDefault();
            const productId = decreaseBtn.dataset.productId;
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            if (input) {
                const val = parseInt(input.value);
                if (val > 1) {
                    const newQuantity = val - 1;
                    input.value = newQuantity;
                    updateQuantity(productId, newQuantity);
                }
            }
        }
    });

    // === ОБРАБОТЧИК ИЗМЕНЕНИЯ КОЛИЧЕСТВА ===
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('quantity-input')) {
            const input = e.target;
            const productId = input.dataset.productId;
            const quantity = Math.max(1, parseInt(input.value) || 1);
            input.value = quantity;
            updateQuantity(productId, quantity);
        }
    });

    // === ФУНКЦИЯ ОБНОВЛЕНИЯ КОЛИЧЕСТВА ===
    function updateQuantity(productId, quantity) {
        fetch('/basket/update_basket.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}&quantity=${quantity}&csrf_token=${getCsrf()}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.csrf_token) document.querySelector('meta[name="csrf-token"]').content = data.csrf_token;
            if (data.success) {
                // ✅ Обновляем счетчики и шапку, и внутри корзины
                updateBasketCounts(data.basket_count);
                if (data.basket_total !== undefined) updateBasketTotal(data.basket_total);
                if (data.item_total !== undefined) {
                    const itemTotalEl = document.querySelector(`.item-total[data-product-id="${productId}"]`);
                    if (itemTotalEl) itemTotalEl.textContent = formatPrice(data.item_total);
                }
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(() => showNotification('Ошибка сети', 'error'));
    }
});

// === ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ===

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// ✅ ЕДИНАЯ ФУНКЦИЯ ОБНОВЛЕНИЯ СЧЕТЧИКОВ
function updateBasketCounts(count) {
    if (count === undefined) return;

    // 1. Обновляем счетчик в шапке (на всех страницах)
    const headerEl = document.getElementById('basket-count');
    if (headerEl) {
        headerEl.textContent = count;
        // Анимация пульсации
        headerEl.style.transition = 'transform 0.2s';
        headerEl.style.transform = 'scale(1.3)';
        setTimeout(() => headerEl.style.transform = 'scale(1)', 200);
    }

    // 2. Обновляем счетчик внутри страницы корзины (если мы на ней)
    const cartPageEl = document.getElementById('cart-page-count');
    if (cartPageEl) {
        cartPageEl.textContent = count;
    }
}

function updateBasketTotal(total) {
    const el = document.querySelector('.basket-total');
    if (el) {
        el.textContent = 'Итого к оплате: ' + formatPrice(total);
    }
}

function formatPrice(price) {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(price);
}

function showNotification(msg, type) {
    const old = document.querySelector('.ajax-notification');
    if (old) old.remove();

    const el = document.createElement('div');
    el.className = `ajax-notification notification ${type}`;
    el.textContent = msg;
    el.style.cssText = `
        position: fixed; top: 90px; right: 20px; z-index: 9999;
        padding: 12px 20px; border-radius: 8px; color: white; font-weight: 600;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease forwards;
    `;
    document.body.appendChild(el);
    setTimeout(() => {
        el.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => el.remove(), 300);
    }, 3000);
}

// Стили для анимации уведомлений
if (!document.getElementById('notify-styles')) {
    const style = document.createElement('style');
    style.id = 'notify-styles';
    style.textContent = `
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
    `;
    document.head.appendChild(style);
}
