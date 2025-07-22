/**
 * Обработка выбора модификаций товара
 */
class ProductModifications {
    constructor(options) {
        console.log('ProductModifications: Инициализация с параметрами:', options);
        this.options = options || {};
        this.productSku = this.options.productSku || '';
        this.modificationData = null;
        this.selectedValues = {};
        this.resultElement = document.querySelector(this.options.resultSelector || '.modification-result');
        this.modBlockElement = document.querySelector(this.options.modBlockSelector || '.product-modifications');
        
        console.log('ProductModifications: Найден элемент для результата:', this.resultElement);
        console.log('ProductModifications: Найден элемент для модификаций:', this.modBlockElement);
        
        this.init();
    }

    /**
     * Инициализация компонента
     */
    init() {
        console.log('ProductModifications: Инициализация компонента с артикулом:', this.productSku);
        
        // Загружаем данные о модификациях
        this.loadModificationData().then((data) => {
            console.log('ProductModifications: Данные загружены:', data);
            if (this.modificationData && this.modificationData.mods && this.modificationData.mods.length > 0) {
                console.log('ProductModifications: Найдены модификации для артикула');
                this.renderModificationBlocks();
                this.initEventListeners();
                this.modBlockElement.style.display = 'block';
                
                // Показываем блок с результатом
                const resultBlock = document.querySelector('.modification-result-block');
                if (resultBlock) {
                    console.log('ProductModifications: Показываем блок результата');
                    resultBlock.style.display = 'block';
                } else {
                    console.error('ProductModifications: Блок результата не найден');
                }
            } else {
                console.warn('ProductModifications: Не найдено модификаций для артикула:', this.productSku);
            }
        }).catch(error => {
            console.error('ProductModifications: Ошибка при загрузке модификаций:', error);
        });
    }

    /**
     * Загружает данные о модификациях из JSON файла
     */
    async loadModificationData() {
        try {
            console.log('ProductModifications: Загрузка JSON файла с модификациями');
            const response = await fetch('/result.json');
            if (!response.ok) {
                throw new Error('Network response was not ok, status: ' + response.status);
            }
            
            const data = await response.json();
            console.log('ProductModifications: JSON данные получены:', data);
            
            // Находим нужный товар по SKU
            console.log('ProductModifications: Поиск товара с SKU:', this.productSku);
            this.modificationData = data.find(item => {
                console.log('ProductModifications: Проверяем совпадение:', item.sku, '===', this.productSku, item.sku === this.productSku);
                return item.sku === this.productSku;
            });
            
            console.log('ProductModifications: Результат поиска:', this.modificationData);
            return this.modificationData;
        } catch (error) {
            console.error('Ошибка загрузки данных модификаций:', error);
            return null;
        }
    }

    /**
     * Отрисовывает блоки выбора модификаций
     */
    renderModificationBlocks() {
        if (!this.modificationData || !this.modificationData.mods) return;
        
        let html = '<div class="modification-title">Выберите модификацию товара:</div>';
        
        this.modificationData.mods.forEach((mod, modIndex) => {
            html += `
                <div class="modification-group" data-group-index="${modIndex}">
                    <div class="modification-group-title">${mod.header}</div>
                    <div class="modification-options">
            `;
            
            mod.items.forEach((item, itemIndex) => {
                // Найти первый value (value1, value2, ...) в объекте
                const valueKey = Object.keys(item).find(key => key.startsWith('value'));
                const value = item[valueKey] || '';
                const description = item.description || '';
                
                html += `
                    <div class="modification-option">
                        <label>
                            <input type="radio" name="mod_${modIndex}" 
                                data-group="${modIndex}" 
                                data-value="${value}" 
                                data-description="${description}"
                                ${itemIndex === 0 ? 'checked' : ''}>
                            <span class="mod-value">${value}</span>
                            <span class="mod-description">${description}</span>
                        </label>
                    </div>
                `;
                
                // Сохраняем первый выбор по умолчанию
                if (itemIndex === 0) {
                    this.selectedValues[modIndex] = {
                        value: value,
                        description: description
                    };
                }
            });
            
            html += `
                    </div>
                </div>
            `;
        });
        
        html += `
            <div class="modification-result-block">
                <div class="modification-result-label">Выбранная модификация:</div>
                <div class="modification-result">${this.formatModificationCode()}</div>
            </div>
        `;
        
        this.modBlockElement.innerHTML = html;
        this.resultElement = document.querySelector('.modification-result');
    }

    /**
     * Инициализация обработчиков событий
     */
    initEventListeners() {
        const radioButtons = document.querySelectorAll('.modification-option input[type="radio"]');
        
        radioButtons.forEach(radio => {
            radio.addEventListener('change', (e) => {
                const group = e.target.dataset.group;
                const value = e.target.dataset.value;
                const description = e.target.dataset.description;
                
                this.selectedValues[group] = {
                    value: value,
                    description: description
                };
                
                this.updateResult();
            });
        });
    }

    /**
     * Обновляет результат выбора модификации
     */
    updateResult() {
        if (this.resultElement) {
            this.resultElement.textContent = this.formatModificationCode();
        }
    }

    /**
     * Форматирует код модификации на основе выбранных опций
     */
    formatModificationCode() {
        if (!this.modificationData) return '';
        
        let baseCode = this.modificationData.sku.toUpperCase();
        let modParts = [];
        
        // Собираем все выбранные значения
        Object.values(this.selectedValues).forEach(selected => {
            if (selected.value) {
                modParts.push(selected.value);
            }
        });
        
        // Форматируем финальный код
        // Примеры:
        // 2ТРМ0-Щ1.У2
        // 2ТРМ0-Щ2.3.RS
        
        // Первое значение идет через дефис, остальные через точку
        let result = baseCode;
        
        if (modParts.length > 0) {
            result += '-' + modParts[0];
            
            if (modParts.length > 1) {
                result += '.' + modParts.slice(1).join('.');
            }
        }
        
        return result;
    }
}
