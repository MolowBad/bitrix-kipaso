/**
 * Обработчики для кнопки покупки модификаций товара
 */

$(function() {
    // Функция для нормализации названия модификации,был баг из за точки в конце названия
    function normalizeModificationName(str) {
        if (typeof str !== 'string') return '';
        return str
        .trim() 
       
        .replace(/[\u2010\u2011\u2012\u2013\u2014\u2015\u2212\u00AD]/g, '-')
       
        .replace(/\s*-\s*/g, '-')
        
        .replace(/[.\[,;:]+$/g, '');
    }
  
    // Добавляем обработчик на клик по кнопке покупки модификации
    $(document).on("click", ".modificationAddCart", function(event) {
        event.preventDefault();
        
        const $this = $(this);
        // Получаем ID товара из разных источников
        let productID = $this.data("id");
        
        // Если ID не указан или неверный формат, берем ID из главного элемента страницы
        if (!productID || productID === "" || isNaN(parseInt(productID))) {
            productID = $("#catalogElement").data("product-id");
        }
        
        // Берём актуальные значения модификации и цены из DOM рядом с кнопкой,
        // чтобы не зависеть от устаревших data-* атрибутов
        let modification = $this.data("modification");
        let price = $this.data("price");
        const quantity = $this.data("quantity") || 1;

        // Поиск внутри ближайшего блока модификации
        const $modBlock = $this.closest('.modification-result-block');
        if ($modBlock.length) {
            const $modResult = $modBlock.find('#modification-result');
            if ($modResult.length) {
                const txt = $modResult.text().trim();
                const normalizedTxt = normalizeModificationName(txt);
                if (normalizedTxt) {
                    modification = normalizedTxt;
                    if (txt !== normalizedTxt) {
                        $modResult.text(normalizedTxt);
                    }
                }
            }
            const $priceEl = $modBlock.find('.modification-price');
            if ($priceEl.length) {
                const raw = ($priceEl.text() || '').trim();
                // Нормализуем цену: оставляем цифры и разделители, заменяем запятую на точку
                const normalized = raw.replace(/[^\d.,]/g, '').replace(/\s/g, '');
                const num = parseFloat(normalized.replace(',', '.'));
                if (!isNaN(num)) price = num;
            }
        }
            
        // Отправляем AJAX-запрос на добавление товара с модификацией
        if (!$this.hasClass("loading") && !$this.hasClass("added")) {
            $this.addClass("loading").html("Загружается...");
            
            // Сохраняем информацию о модификации в localStorage для использования на странице корзины
            modification = normalizeModificationName(modification);
            setModificationData(productID, modification, price);

            
            const ajaxData = {
                act: "addCart",
                id: productID,
                modification: modification,
                modification_price: price,
                q: quantity,
                site_id: SITE_ID
            };

            if (typeof BX !== "undefined") {
                if (typeof BX.bitrix_sessid === "function") {
                    ajaxData.sessid = BX.bitrix_sessid();
                } else if (BX.message && BX.message("bitrix_sessid")) {
                    ajaxData.sessid = BX.message("bitrix_sessid");
                }
            }
            
            // Определяем целевой AJAX-URL
            var targetAjax = (typeof ajaxPath !== 'undefined' && ajaxPath) ? ajaxPath : '/ajax.php';
            
            // Отправляем запрос на добавление товара в корзину
            $.ajax({
                url: targetAjax,
                type: "POST",
                dataType: "json",
                data: ajaxData,
                success: function(response) {
                    
                    // Перезагружаем корзину
                    if (typeof window.cartReload === 'function') {
                        window.cartReload();
                    }
                    
                    // Изменяем внешний вид кнопки
                    const $imageAfterLoad = $this.find("img");
                    const isModificationBtn = $this.hasClass("modificationAddCart");
                    $this.removeClass("loading");

                    if (isModificationBtn) {
                        // Для кнопки модификации: не блокируем повторное добавление и не уводим в корзину
                        if ($imageAfterLoad.length) {
                            $this.prepend($imageAfterLoad.attr("src", (typeof TEMPLATE_PATH !== 'undefined' ? TEMPLATE_PATH : '') + "/images/added.svg"));
                        }
                        var addedText = (typeof LANG !== 'undefined' && LANG["BASKET_ADDED"]) ? LANG["BASKET_ADDED"] : "Добавлено в корзину";
                        var defaultText = (typeof LANG !== 'undefined' && (LANG["BASKET_BUY_MODIFICATION"] || LANG["BASKET_ADD"])) ? (LANG["BASKET_BUY_MODIFICATION"] || LANG["BASKET_ADD"]) : "Купить модификацию";
                        $this.html(addedText);
                        // Оставляем href без изменений (или сбрасываем на #), чтобы повторный клик снова шёл в AJAX
                        // $this.attr('href', '#'); // раскомментируйте при необходимости
                        setTimeout(function() {
                            $this.removeClass("added loading");
                            $this.html(defaultText);
                        }, 1200);
                    } else {
                        // Обычная кнопка товара: оставить состояние "в корзине" и ссылку на корзину
                        if ($imageAfterLoad.length) {
                            $this.prepend($imageAfterLoad.attr("src", (typeof TEMPLATE_PATH !== 'undefined' ? TEMPLATE_PATH : '') + "/images/added.svg"));
                        }
                        var addedTextCommon = (typeof LANG !== 'undefined' && LANG["BASKET_ADDED"]) ? LANG["BASKET_ADDED"] : "Добавлено в корзину";
                        $this.addClass("added").html(addedTextCommon);
                        if (typeof SITE_DIR !== 'undefined') {
                            $this.attr("href", SITE_DIR + "personal/cart/");
                        }
                    }
                    
                    // Показываем всплывающее окно корзины, если оно есть в ответе
                    if (response && response.status === true && response.window_component) {
                        // Добавляем компонент окна корзины в DOM
                        $('body').append(response.window_component);
                        
                        // Добавляем информацию о модификации в окно корзины
                        setTimeout(function() {
                            updateModificationInfoInBasket(productID, modification, price);
                        }, 100);
                    } else {
                        
                        // Отправляем запрос за информацией о товаре
                        $.ajax({
                            url: targetAjax,
                            type: "GET",
                            dataType: "html",
                            data: {
                                act: "getProductWindow",
                                id: productID
                            },
                            success: function(windowHTML) {
                                // Удаляем старое окно, если есть
                                $("#appBasket").remove();
                                
                                // Добавляем новое окно
                                $('body').append(windowHTML);
                                
                                // Добавляем информацию о модификации
                                setTimeout(function() {
                                    updateModificationInfoInBasket(productID, modification, price);
                                }, 100);
                            }
                        });
                    }
                },
                error: function(jqxhr, textStatus, error) {
                    console.error("Ошибка при добавлении в корзину:", textStatus, error);
                    $this.removeClass("loading").addClass("error");
                }
            });
        }
    });
    
    // Функция для обновления информации о модификации в корзине
    function updateModificationInfoInBasket(productID, modification, price) {
        
        // Находим открытую корзину
        const $basket = $("#appBasket");
        
        if ($basket.length > 0) {
            
            // Находим название товара в корзине
            const $nameBlock = $basket.find(".name.moreLink");
            
            // Удаляем старый блок информации о модификации, если он существует
            $basket.find(".itemModification").remove();
            
            // Инициализация и отображение окна корзины
            showBasketWindow($basket);
            
            if ($nameBlock.length > 0) {
                
                // Создаем блок с информацией о модификации
                const $modificationInfo = $("<div>")
                    .addClass("itemModification")
                    .css({
                        "margin-top": "8px",
                        "font-size": "13px",
                        "color": "#555",
                        "font-weight": "bold"
                    });
                
                // Добавляем текст модификации
                $modificationInfo.append(
                    $("<div>")
                        .addClass("modificationName")
                        .text("Модификация: " + modification)
                );
                
                // Если есть цена, добавляем и обновляем все блоки с ценой
                if (price) {
                    // Форматируем цену для отображения
                    const formattedPrice = formatPrice(price);
                    
                    // Добавляем цену в блок информации о модификации
                    $modificationInfo.append(
                        $("<div>")
                            .addClass("modificationPrice")
                            .text("Цена модификации: " + formattedPrice)
                    );
                    
                    // Обновляем цену в блоке с ценой товара
                    const $priceContainer = $basket.find(".priceContainer");
                    if ($priceContainer.length > 0) {
                        $priceContainer.html(formattedPrice);
                    }
                    
                    // Обновляем итоговую сумму
                    const $allSum = $basket.find(".allSum");
                    if ($allSum.length > 0) {
                        $allSum.html(formattedPrice);
                    }
                }
                
                // Добавляем блок после названия товара
                $nameBlock.after($modificationInfo);
                
                // Добавляем обработчик события для кнопки закрытия
                $basket.find('.closeWindow').off('click.modificationBasket').on('click.modificationBasket', function() {
                    // Закрытие окна корзины и удаление затемнения
                    $("#appBasketOverlay").remove();
                    $basket.remove();
                });
            } else {
                
                // Если нет блока названия, добавляем блок с информацией в .information .wrapper
                const $wrapper = $basket.find(".information .wrapper");
                
                if ($wrapper.length > 0) {
                    // Форматируем цену, если она указана
                    let formattedPrice = '';
                    if (price) {
                        formattedPrice = formatPrice(price);
                    }
                    
                    // Создаем блок с информацией о модификации
                    const $modificationInfo = $("<div>")
                        .addClass("itemModification")
                        .css({
                            "margin-top": "8px",
                            "margin-bottom": "8px",
                            "font-size": "13px",
                            "color": "#555",
                            "font-weight": "bold"
                        })
                        .text("Модификация: " + modification + (formattedPrice ? " (цена: " + formattedPrice + ")" : ""));
                    
                    // Добавляем блок в обёртку
                    $wrapper.prepend($modificationInfo);
                    
                    // Обновляем цену в блоке с ценой товара
                    if (price) {
                        // Обновляем цену в блоке с ценой товара
                        const $priceContainer = $basket.find(".priceContainer");
                        if ($priceContainer.length > 0) {
                            $priceContainer.html(formattedPrice);
                        }
                        
                        // Обновляем итоговую сумму
                        const $allSum = $basket.find(".allSum");
                        if ($allSum.length > 0) {
                            $allSum.html(formattedPrice);
                        }
                    }
                }
            }
        }
    }
    
    // Функция для отображения окна корзины
    function showBasketWindow($basket) {
        
        // Добавляем класс для стилизации
        $basket.addClass("opened");
        
        // Динамически позиционируем окно в центре
        const windowWidth = Math.min($basket.outerWidth(), $(window).width());
        const windowHeight = Math.min($basket.outerHeight(), $(window).height());
        
        $basket.css({
            "position": "fixed",
            "display": "block",
            "left": (($(window).width() - windowWidth) / 2) + "px",
            "top": (($(window).height() - windowHeight) / 2) + "px",
            "z-index": 999
        });
        
        // Удаляем старый оверлей, если он есть
        $("#appBasketOverlay").remove();
        
        // Добавляем затемнение фона
        $("<div id='appBasketOverlay'></div>").appendTo("body").css({
            "position": "fixed",
            "top": 0,
            "bottom": 0,
            "left": 0,
            "right": 0,
            "background-color": "rgba(0,0,0,0.5)",
            "z-index": 998
        }).on("click", function() {
            $("#appBasket").remove();
            $(this).remove();
        });
    }
    
    // Функция форматирования цены для отображения
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
    
    // Функция сохранения данных о модификации в localStorage
    function setModificationData(productId, modification, price) {
        try {
            // Получаем текущие данные или создаем новый объект
            let modificationsData = localStorage.getItem('productModifications');
            if (!modificationsData) {
                modificationsData = {};
            } else {
                modificationsData = JSON.parse(modificationsData);
            }
            
            // Сохраняем информацию о модификации
            modificationsData[productId] = {
                modification: modification,
                price: price,
                timestamp: Date.now()
            };
            
            // Записываем в localStorage
            localStorage.setItem('productModifications', JSON.stringify(modificationsData));           
            
            // Вызываем событие для обновления цен на странице, если это страница корзины
            $(document).trigger('modificationDataUpdated', [productId, modification, price]);
        } catch (e) {
            console.error("Ошибка при сохранении данных о модификации:", e);
        }
    }
});

