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
            console.log('Найден товар для обновления:', $tabloid);
            console.log('Структура HTML таблоида:', $tabloid.html());
            const formattedPrice = formatPrice(modData.price);
            
            // Обновляем цену в priceContainer
            const $priceContainer = $tabloid.find('.priceContainer');
            
            // Если priceContainer найден, обновляем в нём
            if ($priceContainer.length > 0) {
                console.log('Обновляем цену в .priceContainer с', $priceContainer.html(), 'на', formattedPrice);
                $priceContainer.html(formattedPrice);
                $priceContainer.attr('data-price', modData.price);
                
                // Обновляем родительский элемент, если он есть
                const $priceParent = $priceContainer.parent();
                if ($priceParent.length > 0) {
                    console.log('Обновляем родительский элемент price:', $priceParent);
                    $priceParent.attr('data-price', modData.price);
                }
            } else {
                // Пробуем найти альтернативные контейнеры для цены
                const $priceBlock = $tabloid.find('.price, .sum, .sumContainer, .basketSum');
                if ($priceBlock.length > 0) {
                    console.log('Обновляем цену в альтернативном блоке с', $priceBlock.html(), 'на', formattedPrice);
                    $priceBlock.html(formattedPrice);
                    $priceBlock.attr('data-price', modData.price);
                }
            }
            
            // Обновляем все формы цен
            $tabloid.find('input[name*="price"], input[data-price], [data-price]').each(function() {
                console.log('Обновляем data-price элемент:', $(this));
                $(this).attr('data-price', modData.price);
                $(this).val(modData.price);
            });
            
            // Также обновляем общую сумму корзины
            const $basketSum = $('.basketSum');
            if ($basketSum.length > 0) {
                console.log('Обновляем общую сумму корзины:', $basketSum);
                const qty = parseInt($tabloid.find('.qty').val() || 1);
                $basketSum.html(formatPrice(modData.price * qty));
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
 * @param {jQuery} $tabloid Элемент товара в корзине
 * @param {string} modification Код модификации
 * @param {string} price Цена модификации
 */
function addModificationInfo($tabloid, modification, price) {
    console.log('Добавляем информацию о модификации:', modification, price);
    
    if (modification) {
        // Проверяем, есть ли уже блок информации о модификации
        const $existingInfo = $tabloid.find('.itemModification');
        
        // Если блок уже есть, обновляем его
        if ($existingInfo.length > 0) {
            console.log('Обновляем существующий блок модификации');
            $existingInfo.find('.modificationName').text('Модификация: ' + modification);
            $existingInfo.find('.modificationPrice').text('Цена модификации: ' + price);
        } else {
            // Ищем разные возможные места для добавления информации о модификации
            
            // Создаем блок модификации
            const $modificationBlock = $('<div class="itemModification" style="margin-top: 8px; font-size: 13px; color: #555; font-weight: bold;"><div class="modificationName">Модификация: ' + modification + '</div><div class="modificationPrice">Цена модификации: ' + price + '</div></div>');
            
            // Список возможных мест для добавления
            let inserted = false;
            
            // 1. Добавляем после ссылки на название в разных форматах
            const nameSelectors = [
                '.productColText .name',               // Для squares шаблона
                '.name a', 
                'a.name',
                '.name span',
                '.name'
            ];
            
            // Перебираем селекторы, пока не найдем подходящий
            for (let i = 0; i < nameSelectors.length; i++) {
                const $nameBlock = $tabloid.find(nameSelectors[i]);
                if ($nameBlock.length > 0) {
                    console.log('Добавляем модификацию после:', nameSelectors[i]);
                    $nameBlock.after($modificationBlock);
                    inserted = true;
                    break;
                }
            }
            
            // 2. Если не нашли название, пробуем добавить перед ценой
            if (!inserted) {
                const priceSelectors = ['.price', '.priceContainer', '.basketQty'];
                
                for (let i = 0; i < priceSelectors.length; i++) {
                    const $priceBlock = $tabloid.find(priceSelectors[i]);
                    if ($priceBlock.length > 0) {
                        console.log('Добавляем модификацию перед:', priceSelectors[i]);
                        $priceBlock.before($modificationBlock);
                        inserted = true;
                        break;
                    }
                }
            }
            
            // 3. В крайнем случае добавляем в начало блока товара
            if (!inserted) {
                console.log('Добавляем модификацию в начало блока');
                $tabloid.prepend($modificationBlock);
            }
            
            // Сохраняем данные о модификации в data-атрибутах
            $tabloid.attr('data-modification', modification);
            $tabloid.attr('data-modification-price', price.replace(/[^0-9,.]/g, ''));
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
        
        console.log("Итоговая сумма корзины (рассчитанная):", total);
        
        // Обновляем итоговую сумму в разных местах страницы
        
        // 1. Стандартный блок суммы
        const $orderSumPrice = $('#orderSumPrice, .orderSumPrice');
        if ($orderSumPrice.length > 0) {
            $orderSumPrice.html(formatPrice(total));
            console.log('Обновлена сумма в orderSumPrice');
        }
        
        // 2. Блок basketSum (из предоставленного HTML)
        const $basketSum = $('.basketSum');
        if ($basketSum.length > 0) {
            $basketSum.html(formatPrice(total));
            console.log('Обновлена сумма в basketSum:', $basketSum.html());
        }
        
        // 3. Блок в #sum .price span
        const $sumPrice = $('#sum .price span');
        if ($sumPrice.length > 0) {
            $sumPrice.html(formatPrice(total));
            console.log('Обновлена сумма в #sum .price span');
        }
        
        // 4. Все элементы с классом .allSum
        $('.allSum, .sumPrice').each(function() {
            $(this).html(formatPrice(total));
        });
        
        console.log("Итоговая сумма корзины обновлена во всех блоках");
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
            
            // Добавляем расширенный поиск товаров с модификациями
            console.log('Начинаем поиск товаров для обновления модификаций');
            
            // Сначала просто добавим модификации ко всем товарам в корзине
            // Это поможет в случае, если ID товаров в localStorage и на странице не совпадают
            
            // Получаем список всех товаров в корзине
            const $allProducts = $('.tabloid');
            console.log('Найдено товаров в корзине:', $allProducts.length);
            
            // Перебираем все модификации
            for (let productId in modificationsData) {
                if (modificationsData.hasOwnProperty(productId)) {
                    const modData = modificationsData[productId];
                    console.log('Обрабатываем модификацию:', productId, modData);
                    
                    // Ищем товар по ID несколькими способами
                    let found = false;
                    
                    // 1. Сначала ищем по кнопке удаления
                    const $deleteBtn = $('.delete[data-id="' + productId + '"]');
                    if ($deleteBtn.length > 0) {
                        const $tabloid = $deleteBtn.closest('.tabloid');
                        console.log('Найден товар по кнопке удаления:', productId);
                        updateProductModification($tabloid, modData);
                        found = true;
                    }
                    
                    // 2. Если не нашли, ищем по любому элементу с data-id
                    if (!found) {
                        $('[data-id="' + productId + '"]').each(function() {
                            const $tabloid = $(this).closest('.tabloid');
                            if ($tabloid.length > 0) {
                                console.log('Найден товар по атрибуту data-id:', productId);
                                updateProductModification($tabloid, modData);
                                found = true;
                                return false; // Прерываем цикл
                            }
                        });
                    }
                    
                    // 3. Если все еще не нашли, просто ищем модификацию в тексте
                    if (!found && modData.modification) {
                        const modificationCode = modData.modification;
                        
                        $allProducts.each(function() {
                            const $tabloid = $(this);
                            const text = $tabloid.text();
                            
                            // Если текст товара содержит код модификации
                            if (text.indexOf(modificationCode) !== -1) {
                                console.log('Найден товар по тексту модификации:', modificationCode);
                                updateProductModification($tabloid, modData);
                                found = true;
                                return false; // Прерываем цикл
                            }
                        });
                    }
                    
                    // 4. В крайнем случае, если в корзине только 1 товар, обновляем его
                    if (!found && $allProducts.length === 1) {
                        console.log('Найден единственный товар в корзине, обновляем его');
                        updateProductModification($allProducts, modData);
                    }
                }
            }
            
            // Обновляем общую сумму корзины
            setTimeout(updateCartTotal, 300);
        }
    }
    
    // Подписываемся на событие обновления корзины и на DOMContentLoaded
    $(document).on('cart_reload', handleCartUpdate);
    
    // Также обновляем после полной загрузки страницы
    $(window).on('load', function() {
        if (window.location.pathname.indexOf('/personal/cart/') !== -1) {
            setTimeout(handleCartUpdate, 1000);
        }
    });
    
    /**
     * Обработчик обновления корзины
     */
    function handleCartUpdate() {
        if (window.location.pathname.indexOf('/personal/cart/') !== -1) {
            console.log('Сработало событие обновления корзины, обновляем модификации');
            
            setTimeout(function() {
                // Загружаем данные о модификациях
                const modData = loadModificationsData();
                console.log('Загружены данные о модификациях:', modData);
                
                // Если есть данные о модификациях
                if (Object.keys(modData).length > 0) {
                    // Получаем список всех товаров в корзине
                    const $allProducts = $('.tabloid');
                    console.log('Найдено товаров в корзине при обновлении:', $allProducts.length);
                    
                    // Обновляем информацию на странице
                    for (let productId in modData) {
                        if (modData.hasOwnProperty(productId)) {
                            const modificationInfo = modData[productId];
                            
                            // Ищем товар по ID несколькими способами
                            let found = false;
                            
                            // 1. Сначала ищем по кнопке удаления
                            const $deleteBtn = $('.delete[data-id="' + productId + '"]');
                            if ($deleteBtn.length > 0) {
                                const $tabloid = $deleteBtn.closest('.tabloid');
                                updateProductModification($tabloid, modificationInfo);
                                found = true;
                            }
                            
                            // 2. Если не нашли, ищем по любому элементу с data-id
                            if (!found) {
                                $('[data-id="' + productId + '"]').each(function() {
                                    const $tabloid = $(this).closest('.tabloid');
                                    if ($tabloid.length > 0) {
                                        updateProductModification($tabloid, modificationInfo);
                                        found = true;
                                        return false; // Прерываем цикл
                                    }
                                });
                            }
                            
                            // 3. Если все еще не нашли, ищем по модификации в тексте
                            if (!found && modificationInfo.modification) {
                                const modificationCode = modificationInfo.modification;
                                
                                $allProducts.each(function() {
                                    const $tabloid = $(this);
                                    const text = $tabloid.text();
                                    
                                    if (text.indexOf(modificationCode) !== -1) {
                                        updateProductModification($tabloid, modificationInfo);
                                        found = true;
                                        return false; // Прерываем цикл
                                    }
                                });
                            }
                            
                            // 4. В крайнем случае, если в корзине только 1 товар
                            if (!found && $allProducts.length === 1) {
                                updateProductModification($allProducts, modificationInfo);
                            }
                        }
                    }
                    
                    // Обновляем общую сумму с небольшой задержкой
                    setTimeout(updateCartTotal, 300);
                }
            }, 500);
        }
    }
});
