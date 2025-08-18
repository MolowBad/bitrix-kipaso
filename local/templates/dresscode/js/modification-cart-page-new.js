/**
 * Скрипт для обновления информации о товарах с модификациями на странице корзины
 */
$(function() {
    // Создаём глобальный объект для хранения данных о модификациях
    window.productModifications = window.productModifications || {};
    
    /**
     * Загружает данные о модификациях из localStorage или глобальной переменной
     * @returns {Object} Данные о модификациях
     */
    function loadModificationsData() {
        try {
            // Сначала пробуем загрузить из localStorage
            let modificationsData = localStorage.getItem('productModifications');
            if (modificationsData) {
                modificationsData = JSON.parse(modificationsData);
                console.log('Загружены данные о модификациях из localStorage:', modificationsData);
                return modificationsData;
            }
            
            // Если нет в localStorage, то пробуем загрузить из сессии
            if (window.productModifications && Object.keys(window.productModifications).length > 0) {
                console.log('Загружены данные о модификациях из сессии:', window.productModifications);
                return window.productModifications;
            }
            
            return {};
        } catch (e) {
            console.error('Ошибка при загрузке данных о модификациях:', e);
            return {};
        }
    }
    
    /**
     * Обновляет информацию о модификации товара в корзине
     * @param {jQuery} $tabloid Элемент товара в корзине
     * @param {Object} modData Данные о модификации
     */
    function updateProductModification($tabloid, modData) {
        if (modData && modData.price) {
            const formattedPrice = formatPrice(modData.price);
            
            // Обновляем цену в priceContainer
            const $priceContainer = $tabloid.find('.priceContainer');
            if ($priceContainer.length > 0) {
                console.log('Обновляем цену с', $priceContainer.html(), 'на', formattedPrice);
                $priceContainer.html(formattedPrice);
                $priceContainer.attr('data-price', modData.price);
            }
            
            // Добавляем информацию о модификации
            addModificationInfo($tabloid, modData.modification, formattedPrice);
        }
    }
    
    /**
     * Форматирует цену для отображения
     * @param {number|string} price Цена для форматирования
     * @returns {string} Отформатированная цена
     */
    function formatPrice(price) {
        // Преобразуем в число
        price = parseFloat(price);
        
        // Форматируем с разделителями и знаком валюты
        let formattedPrice = price.toFixed(2);
        
        // Заменяем точку на запятую (для русской локали)
        formattedPrice = formattedPrice.replace('.', ',');
        
        // Добавляем пробелы между разрядами для тысяч
        let parts = formattedPrice.split(',');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, "&nbsp;");
        formattedPrice = parts.join(',');
        
        // Добавляем знак рубля
        return formattedPrice + " ₽";
    }
    
    /**
     * Добавляет информацию о модификации к товару в корзине
     * @param {jQuery} $tabloid jQuery объект таблоида товара
     * @param {string} modification Код модификации
     * @param {string} formattedPrice Отформатированная цена
     */
    function addModificationInfo($tabloid, modification, formattedPrice) {
        // Удаляем старую информацию о модификации, если есть
        $tabloid.find('.tabloidModification').remove();
        
        // Находим блок с названием товара
        const $nameBlock = $tabloid.find('.name');
        
        // Создаем блок с информацией о модификации
        if ($nameBlock.length > 0) {
            const $modificationInfo = $("<div>")
                .addClass("tabloidModification")
                .css({
                    "margin-top": "8px",
                    "font-size": "13px",
                    "color": "#555",
                    "font-weight": "bold"
                })
                .html("Модификация: " + modification);
            
            // Добавляем блок после названия товара
            $nameBlock.after($modificationInfo);
            console.log('Добавлена информация о модификации:', modification);
        } else {
            // Пробуем найти альтернативный блок для добавления информации
            const $productColText = $tabloid.find('.productColText');
            
            if ($productColText.length > 0) {
                const $modificationInfo = $("<div>")
                    .addClass("tabloidModification")
                    .css({
                        "margin-top": "8px",
                        "font-size": "13px",
                        "color": "#555",
                        "font-weight": "bold"
                    })
                    .html("Модификация: " + modification);
                
                // Добавляем блок в начало колонки с текстом
                $productColText.prepend($modificationInfo);
                console.log('Добавлена информация о модификации в колонку с текстом:', modification);
            }
        }
    }
    
    /**
     * Обновляет общую сумму корзины
     */
    function updateCartTotal() {
        let total = 0;
        
        // Суммируем все цены
        $('.priceContainer').each(function() {
            const price = parseFloat($(this).attr('data-price') || 0);
            const qty = parseInt($(this).closest('.tabloid').find('.qty').val() || 1);
            total += price * qty;
        });
        
        // Обновляем итоговую сумму, если она есть на странице
        const $orderSumPrice = $('#orderSumPrice, .orderSumPrice');
        if ($orderSumPrice.length > 0) {
            $orderSumPrice.html(formatPrice(total));
        }
        
        console.log("Итоговая сумма корзины обновлена:", total);
    }
    
    // Проверяем, что мы на странице корзины
    if (window.location.pathname.indexOf('/personal/cart/') !== -1) {
        console.log("Страница корзины загружена, проверяем модификации");
        
        // Загружаем данные о модификациях
        const modificationsData = loadModificationsData();
        
        // Сохраняем в глобальную переменную
        window.productModifications = modificationsData;
        
        // Обновляем информацию на странице
        if (Object.keys(modificationsData).length > 0) {
            console.log("Найдены данные о модификациях:", modificationsData);
            
            // Обновляем информацию о модификациях для всех товаров
            for (let productId in modificationsData) {
                if (modificationsData.hasOwnProperty(productId)) {
                    const modData = modificationsData[productId];
                    
                    // Ищем товар по ID
                    const $deleteBtn = $('.delete[data-id="' + productId + '"]');
                    if ($deleteBtn.length > 0) {
                        const $tabloid = $deleteBtn.closest('.tabloid');
                        updateProductModification($tabloid, modData);
                    } else {
                        // Альтернативный поиск, если кнопка удаления не найдена
                        $('.tabloid').each(function() {
                            const $tabloid = $(this);
                            const itemId = $tabloid.find('[data-id]').data('id');
                            
                            if (itemId && itemId.toString() === productId.toString()) {
                                updateProductModification($tabloid, modData);
                                return false; // Прерываем цикл
                            }
                        });
                    }
                }
            }
            
            // Обновляем общую сумму корзины
            setTimeout(updateCartTotal, 300);
        }
    }
    
    // Подписываемся на событие обновления корзины
    $(document).on('cart_reload', function() {
        if (window.location.pathname.indexOf('/personal/cart/') !== -1) {
            console.log('Сработало событие cart_reload, обновляем модификации');
            
            setTimeout(function() {
                // Загружаем данные о модификациях
                const modData = loadModificationsData();
                
                // Обновляем информацию на странице
                for (let productId in modData) {
                    if (modData.hasOwnProperty(productId)) {
                        const $deleteBtn = $('.delete[data-id="' + productId + '"]');
                        if ($deleteBtn.length > 0) {
                            updateProductModification($deleteBtn.closest('.tabloid'), modData[productId]);
                        }
                    }
                }
                
                // Обновляем общую сумму
                updateCartTotal();
            }, 500);
        }
    });
});
