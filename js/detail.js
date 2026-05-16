// Обработчики для кнопок изменения количества товара на странице детального просмотра и в каталоге
document.addEventListener('DOMContentLoaded', function() {
    // Делегирование событий для кнопок +/-
    document.addEventListener('click', function(e) {
        // Уменьшение количества (для страницы детального просмотра)
        if (e.target.classList.contains('decrease')) {
            const productId = e.target.dataset.productId;
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            if (input) {
                let value = parseInt(input.value) || 1;
                if (value > 1) {
                    input.value = value - 1;
                    updateAddToCartButton(productId, input.value);
                }
            }
        }

        // Увеличение количества (для страницы детального просмотра)
        if (e.target.classList.contains('increase')) {
            const productId = e.target.dataset.productId;
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            if (input) {
                let value = parseInt(input.value) || 1;
                const max = parseInt(input.max) || 99;
                if (value < max) {
                    input.value = value + 1;
                    updateAddToCartButton(productId, input.value);
                }
            }
        }

        // Уменьшение количества (для карточек товаров в каталоге)
        if (e.target.classList.contains('decrease-compact')) {
            const productId = e.target.dataset.productId;
            const input = document.querySelector(`.quantity-input-compact[data-product-id="${productId}"]`);
            if (input) {
                let value = parseInt(input.value) || 1;
                if (value > 1) {
                    input.value = value - 1;
                    updateAddToCartButton(productId, input.value);
                }
            }
        }

        // Увеличение количества (для карточек товаров в каталоге)
        if (e.target.classList.contains('increase-compact')) {
            const productId = e.target.dataset.productId;
            const input = document.querySelector(`.quantity-input-compact[data-product-id="${productId}"]`);
            if (input) {
                let value = parseInt(input.value) || 1;
                const max = parseInt(input.max) || 99;
                if (value < max) {
                    input.value = value + 1;
                    updateAddToCartButton(productId, input.value);
                }
            }
        }
    });

    // Функция обновления data-quantity у кнопки "В корзину"
    function updateAddToCartButton(productId, quantity) {
        const addToCartBtn = document.querySelector(`.add-to-basket[data-product-id="${productId}"]`);
        if (addToCartBtn) {
            addToCartBtn.dataset.quantity = quantity;
        }
    }

    // Обработка ручного ввода в поле количества (для страницы детального просмотра)
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('quantity-input')) {
            const productId = e.target.dataset.productId;
            let value = parseInt(e.target.value) || 1;
            const min = parseInt(e.target.min) || 1;
            const max = parseInt(e.target.max) || 99;

            if (value < min) value = min;
            if (value > max) value = max;

            e.target.value = value;
            updateAddToCartButton(productId, value);
        }
    });

    // Обработка ручного ввода в поле количества (для карточек товаров в каталоге)
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('quantity-input-compact')) {
            const productId = e.target.dataset.productId;
            let value = parseInt(e.target.value) || 1;
            const min = parseInt(e.target.min) || 1;
            const max = parseInt(e.target.max) || 99;

            if (value < min) value = min;
            if (value > max) value = max;

            e.target.value = value;
            updateAddToCartButton(productId, value);
        }
    });
});