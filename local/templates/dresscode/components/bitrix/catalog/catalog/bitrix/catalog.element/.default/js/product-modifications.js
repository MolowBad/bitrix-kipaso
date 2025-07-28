/**
 * Обработка выбора модификаций товара
 */
class ProductModifications {
    constructor(options) {
        this.options = options || {};
        this.productSku = this.options.productSku || '';
        this.modificationData = null;
        this.selectedValues = {};
        this.initialized = false;
        this.resultElement = document.querySelector(this.options.resultSelector || '.modification-result');
        this.modBlockElement = document.querySelector(this.options.modBlockSelector || '.product-modifications-main');
        this.templateElement = document.querySelector('.modification-template');
        this.groupsElement = document.querySelector('.modification-groups');
        
        this.init();
    }

    /**
     * Инициализация компонента
     */
    init() {
        // Защита от повторной инициализации
        if (this.initialized) {
            return;
        }
        this.initialized = true;
        
        // Загружаем данные о модификациях
        this.loadModificationData().then((data) => {
            // Нормализуем структуру данных
            if (this.modificationData && this.modificationData.modifications && !this.modificationData.mods) {
                this.modificationData.mods = this.modificationData.modifications;
            }
            
            if (this.modificationData && this.modificationData.template && this.modificationData.mods && this.modificationData.mods.length > 0) {
                this.renderModificationBlocks();
                this.modBlockElement.style.display = 'block';
                
                // Показываем блок с результатом
                const resultBlock = document.querySelector('.modification-result-block');
                if (resultBlock) {
                    resultBlock.style.display = 'block';
                }
            }
        }).catch(error => {
            // Ошибка загрузки данных
        });
    }

    /**
     * Загружает данные о модификациях из JSON файла
     */
    async loadModificationData() {
        try {
            // Пробуем разные пути к JSON файлу
            let response;
            let data;
            
            // Сначала пробуем all_products.json
            try {
                response = await fetch('/all_products.json');
                if (response.ok) {
                    data = await response.json();
                }
            } catch (e) {
                // Пробуем result.json
            }
            
            // Если не получилось, пробуем result.json
            if (!data) {
                try {
                    response = await fetch('/result.json');
                    if (response.ok) {
                        data = await response.json();
                    }
                } catch (e) {
                    // Не удалось загрузить
                }
            }
            
            if (!data) {
                throw new Error('Не удалось загрузить файл с данными модификаций');
            }
            
            // Определяем структуру данных и находим товар
            if (Array.isArray(data)) {
                // Если данные в виде массива
                this.modificationData = data.find(item => item.sku === this.productSku);
            } else if (typeof data === 'object') {
                // Если данные в виде объекта с ключами
                this.modificationData = data[this.productSku];
                
                // Если найден, добавляем sku для совместимости
                if (this.modificationData) {
                    this.modificationData.sku = this.productSku;
                }
            }
            
            return this.modificationData;
        } catch (error) {
            return null;
        }
    }

    /**
     * Отрисовывает блоки выбора модификаций
     */
    renderModificationBlocks() {
        console.log('ProductModifications: Начало отрисовки блоков');
        
        if (!this.modificationData || !this.modificationData.template || !this.modificationData.mods) {
            console.error('ProductModifications: Нет данных о модификациях');
            return;
        }
        
        // Показываем основной блок модификаций
        if (this.modBlockElement) {
            this.modBlockElement.style.display = 'block';
            console.log('ProductModifications: Основной блок модификаций показан');
        }

        // Отрисовываем шаблон модификации
        console.log('ProductModifications: Отрисовка шаблона...');
        this.renderTemplate();
        
        // Отрисовываем группы выбора
        console.log('ProductModifications: Отрисовка групп...');
        this.renderModificationGroups();
        
        // Инициализируем обработчики событий после отрисовки
        this.initEventListeners();
        
        // Не показываем результат до тех пор, пока не выбраны все модификации
        console.log('ProductModifications: Блок результата скрыт до полного выбора модификаций');
        
        console.log('ProductModifications: Отрисовка блоков завершена');
    }
    
    /**
     * Отрисовывает шаблон модификации
     */
    renderTemplate() {
        const templateContainer = this.modBlockElement.querySelector('.template-display');
        if (!templateContainer) {
            console.error('ProductModifications: Контейнер шаблона не найден');
            return;
        }
        
        console.log('ProductModifications: Отрисовка шаблона:', this.modificationData.template);
        
        let templateHtml = '';
        this.modificationData.template.forEach((item, index) => {
            if (item.modId === null) {
                // Статический текст
                templateHtml += `<span class="template-text">${item.text}</span>`;
            } else {
                // Кликабельный модификатор
                templateHtml += `<span class="template-modifier clickable" data-mod-id="${item.modId}" data-index="${index}">${item.text}</span>`;
            }
        });
        
        console.log('ProductModifications: HTML шаблона:', templateHtml);
        templateContainer.innerHTML = templateHtml;
        console.log('ProductModifications: Шаблон отрисован в контейнер');
    }
    
    /**
     * Отрисовывает группы выбора модификаций
     */
    renderModificationGroups() {
        const groupsContainer = this.modBlockElement.querySelector('.modification-groups');
        if (!groupsContainer) {
            console.error('ProductModifications: Контейнер групп не найден');
            return;
        }
        
        console.log('ProductModifications: Отрисовка групп модификаций:', this.modificationData.mods);
        
        let groupsHtml = '';
        
        this.modificationData.mods.forEach((mod, modIndex) => {
            groupsHtml += `
                <div class="modification-group" data-mod-id="${mod.id}" style="display: none;">
                    <div class="modification-group-title">${mod.title}</div>
                    <div class="modification-options">
            `;
            
            mod.options.forEach((option, optionIndex) => {
            // Проверяем, есть ли значения для отображения
            const hasValue = option.value && option.value.trim() !== '';
            const hasLabel = option.label && option.label.trim() !== '';
            
            groupsHtml += `
                <div class="modification-option">
                    <label>
                        <input type="radio" name="mod_${mod.id}" 
                            data-mod-id="${mod.id}" 
                            data-option-id="${option.id}" 
                            data-value="${option.value || ''}" 
                            data-label="${option.label || ''}">
                        ${hasValue ? `<span class="mod-value">${option.value}</span>` : '<span class="mod-value"></span>'}
                        ${hasLabel ? `<span class="mod-description">${option.label}</span>` : '<span class="mod-description"></span>'}
                    </label>
                </div>
            `;
        });
            
        groupsHtml += `
                </div>
            </div>
        `;
    });
        
        console.log('ProductModifications: HTML групп:', groupsHtml);
        groupsContainer.innerHTML = groupsHtml;
        console.log('ProductModifications: Группы отрисованы в контейнер');
    }

    /**
     * Инициализация обработчиков событий
     */
    initEventListeners() {
        // Защита от повторной инициализации
        if (this.eventListenersInitialized) {
            console.log('ProductModifications: Обработчики событий уже инициализированы');
            return;
        }
        this.eventListenersInitialized = true;
        console.log('ProductModifications: Инициализация обработчиков событий');
        
        // Обработчики для кликов по шаблону
        document.addEventListener('click', (e) => {
            console.log('ProductModifications: Клик по элементу:', e.target);
            console.log('ProductModifications: Классы элемента:', e.target.classList);
            console.log('ProductModifications: data-mod-id:', e.target.dataset.modId);
            
            if (e.target.dataset.modId && (e.target.classList.contains('clickable') || e.target.classList.contains('template-modifier'))) {
                const modId = parseInt(e.target.dataset.modId);
                
                // Проверяем, выбрана ли уже эта модификация
                if (this.selectedValues[modId] && e.target.classList.contains('selected')) {
                    // Если модификация уже выбрана, сбрасываем её
                    console.log('ProductModifications: Сбрасываем выбранную модификацию для modId:', modId);
                    this.resetModification(modId);
                } else {
                    // Иначе показываем группу для выбора
                    console.log('ProductModifications: Показываем группу модификаций для modId:', modId);
                    this.showModificationGroup(modId);
                }
            } else {
                console.log('ProductModifications: Элемент не кликабельный или нет modId');
            }
        });
        
        // Обработчики для выбора опций
        document.addEventListener('change', (e) => {
            if (e.target.type === 'radio' && e.target.dataset.modId) {
                const modId = parseInt(e.target.dataset.modId);
                const optionId = parseInt(e.target.dataset.optionId);
                const label = e.target.dataset.label || '';
                const value = e.target.dataset.value || '';
                
                console.log('ProductModifications: Выбрана опция:', {
                    modId: modId,
                    optionId: optionId,
                    value: value,
                    label: label
                });
                
                this.selectedValues[modId] = {
                    optionId: optionId,
                    label: label,
                    value: value
                };
                
                console.log('ProductModifications: Обновленные выбранные значения:', this.selectedValues);
                
                this.updateResult();
                this.updateTemplate();
            }
        });
    }
    
    /**
     * Показывает группу модификаций
     */
    showModificationGroup(modId) {
        console.log('ProductModifications: Показываем группу для modId:', modId);
        
        // Скрываем все группы
        document.querySelectorAll('.modification-group').forEach(group => {
            group.style.display = 'none';
            console.log('ProductModifications: Скрываем группу:', group.dataset.modId);
        });
        
        // Показываем нужную группу (ищем только среди .modification-group)
        const targetGroup = document.querySelector(`.modification-group[data-mod-id="${modId}"]`);
        console.log('ProductModifications: Найденная группа:', targetGroup);
        
        if (targetGroup) {
            targetGroup.style.display = 'block';
            targetGroup.classList.add('active');
            console.log('ProductModifications: Группа показана:', modId);
        } else {
            console.error('ProductModifications: Группа не найдена для modId:', modId);
        }
    }

    /**
     * Показывает следующую группу модификаций после выбора в текущей
     */
    showNextModificationGroup(currentModId) {
        console.log('ProductModifications: Поиск следующей группы после modId:', currentModId);
        
        // Находим следующую группу в шаблоне
        const templateModIds = this.modificationData.template
            .filter(item => item.modId !== null)
            .map(item => item.modId);
        
        console.log('ProductModifications: modId в шаблоне:', templateModIds);
        
        const currentIndex = templateModIds.indexOf(currentModId);
        console.log('ProductModifications: Текущий индекс:', currentIndex);
        
        if (currentIndex !== -1 && currentIndex < templateModIds.length - 1) {
            const nextModId = templateModIds[currentIndex + 1];
            console.log('ProductModifications: Следующий modId:', nextModId);
            
            // Показываем следующую группу
            this.showModificationGroup(nextModId);
        } else {
            console.log('ProductModifications: Следующая группа не найдена или это последняя группа');
        }
    }

    /**
     * Сбрасывает выбор модификации (возвращает X)
     */
    resetModification(modId) {
        console.log('ProductModifications: Сброс модификации для modId:', modId);
        
        // Удаляем выбранное значение
        delete this.selectedValues[modId];
        
        // Снимаем выделение со всех опций в группе
        const groupInputs = document.querySelectorAll(`input[data-mod-id="${modId}"]`);
        groupInputs.forEach(input => {
            input.checked = false;
        });
        
        // Скрываем группу
        const targetGroup = document.querySelector(`.modification-group[data-mod-id="${modId}"]`);
        if (targetGroup) {
            targetGroup.style.display = 'none';
        }
        
        // Обновляем шаблон и результат
        this.updateTemplate();
        this.updateResult();
    }

    /**
     * Проверяет, выбраны ли все обязательные модификации
     */
    areAllModificationsSelected() {
        // Получаем все modId из шаблона
        const requiredModIds = this.modificationData.template
            .filter(item => item.modId !== null)
            .map(item => item.modId);
        
        console.log('ProductModifications: Обязательные modId:', requiredModIds);
        console.log('ProductModifications: Выбранные modId:', Object.keys(this.selectedValues));
        
        // Проверяем, что все обязательные modId выбраны
        return requiredModIds.every(modId => this.selectedValues.hasOwnProperty(modId));
    }

    /**
     * Обновляет шаблон с выбранными значениями
     */
    updateTemplate() {
        const templateDisplay = document.querySelector('.template-display');
        if (!templateDisplay) {
            console.error('ProductModifications: Контейнер шаблона не найден для обновления');
            return;
        }
        
        console.log('ProductModifications: Обновление шаблона с выбранными значениями:', this.selectedValues);
        
        let templateHtml = '';
        
        this.modificationData.template.forEach((item, index) => {
            if (item.modId && this.selectedValues[item.modId]) {
                const selectedValue = this.selectedValues[item.modId].value;
                // Если значение пустое, не показываем ничего, иначе показываем значение
                const displayValue = selectedValue && selectedValue.trim() !== '' ? selectedValue : '';
                if (displayValue) {
                    templateHtml += `<span class="template-modifier selected clickable" data-mod-id="${item.modId}" data-index="${index}">${displayValue}</span>`;
                } else {
                    // Для пустых значений не добавляем элемент в шаблон, но сохраняем кликабельность
                    templateHtml += `<span class="template-modifier selected clickable empty-value" data-mod-id="${item.modId}" data-index="${index}"></span>`;
                }
            } else if (item.modId) {
                templateHtml += `<span class="template-modifier clickable" data-mod-id="${item.modId}" data-index="${index}">${item.text}</span>`;
            } else {
                templateHtml += `<span class="template-text">${item.text}</span>`;
            }
        });
        
        console.log('ProductModifications: Обновленный HTML шаблона:', templateHtml);
        templateDisplay.innerHTML = templateHtml;
        
        // Обновляем результат
        this.updateResult();
    }
    
    /**
     * Получает сокращенное значение для отображения в шаблоне
     */
    getShortValue(modId, optionId) {
        const mod = this.modificationData.mods.find(m => m.id === modId);
        if (!mod) return 'X';
        
        const option = mod.options.find(o => o.id === optionId);
        if (!option) return 'X';
        
        // Универсальная логика на основе названий групп
        const groupTitle = (mod.title || '').toLowerCase();
        const optionLabel = (option.label || '').toLowerCase();
        
        // Тип корпуса - всегда Щ + номер по порядку
        if (groupTitle.includes('тип корпуса') || groupTitle.includes('корпус')) {
            const optionIndex = mod.options.findIndex(o => o.id === optionId);
            return 'Щ' + (optionIndex + 1);
        }
        
        // Тип индикации - 1 для красных, 2 для зеленых
        if (groupTitle.includes('индикация') || groupTitle.includes('цвет')) {
            if (optionLabel.includes('красн')) return '1';
            if (optionLabel.includes('зелен')) return '2';
            // По умолчанию первая опция = 1, остальные = 2
            const optionIndex = mod.options.findIndex(o => o.id === optionId);
            return optionIndex === 0 ? '1' : '2';
        }
        
        // Тип выхода - P для реле, T для транзистора
        if (groupTitle.includes('выход') || groupTitle.includes('тип выхода')) {
            if (optionLabel.includes('реле')) return 'P';
            if (optionLabel.includes('транзистор')) return 'T';
            // По умолчанию первая опция = P, вторая = T
            const optionIndex = mod.options.findIndex(o => o.id === optionId);
            return optionIndex === 0 ? 'P' : 'T';
        }
        
        // Питание - извлекаем числа из описания
        if (groupTitle.includes('питание') || groupTitle.includes('напряжение')) {
            const voltageMatch = optionLabel.match(/(\d+)/);
            if (voltageMatch) {
                return voltageMatch[1];
            }
            // По умолчанию стандартные значения
            const optionIndex = mod.options.findIndex(o => o.id === optionId);
            const defaultVoltages = ['85', '24', '12'];
            return defaultVoltages[optionIndex] || '85';
        }
        
        // Интерфейс - сокращения на основе названий
        if (groupTitle.includes('интерфейс') || groupTitle.includes('связь')) {
            if (optionLabel.includes('rs-485') || optionLabel.includes('rs485')) return 'RS';
            if (optionLabel.includes('ethernet')) return 'E';
            if (optionLabel.includes('wi-fi') || optionLabel.includes('wifi')) return 'W';
            if (optionLabel.includes('без') || optionLabel.includes('нет')) return '';
            // Если не распознали, возвращаем первые буквы
            const words = optionLabel.split(/[\s-]+/);
            return words.map(w => w.charAt(0).toUpperCase()).join('').substring(0, 2);
        }
        
        // Для неизвестных типов - пытаемся извлечь первые символы или цифры
        const numberMatch = optionLabel.match(/(\d+)/);
        if (numberMatch) {
            return numberMatch[1];
        }
        
        // Возвращаем номер опции по порядку
        const optionIndex = mod.options.findIndex(o => o.id === optionId);
        return (optionIndex + 1).toString();
    }

    /**
     * Обновляет результат выбора модификации
     */
    updateResult() {
        const resultBlock = document.querySelector('.modification-result-block');
        
        if (this.areAllModificationsSelected()) {
            // Показываем блок результата и обновляем текст
            if (resultBlock) {
                resultBlock.style.display = 'block';
                console.log('ProductModifications: Показываем блок результата - все модификации выбраны');
            }
            
            if (this.resultElement) {
                this.resultElement.textContent = this.formatModificationCode();
            }
        } else {
            // Скрываем блок результата, если не все модификации выбраны
            if (resultBlock) {
                resultBlock.style.display = 'none';
                console.log('ProductModifications: Скрываем блок результата - не все модификации выбраны');
            }
        }
    }

    /**
     * Форматирует код модификации на основе выбранных опций
     */
    formatModificationCode() {
        if (!this.modificationData || !this.modificationData.template) return '';
        
        console.log('ProductModifications: Формирование кода модификации с выбранными значениями:', this.selectedValues);
        
        let result = '';
        
        // Проходим по шаблону и формируем код
        this.modificationData.template.forEach((item, index) => {
            if (item.modId && this.selectedValues[item.modId]) {
                // Используем значение value из выбранной опции
                const selectedValue = this.selectedValues[item.modId].value || '';
                result += selectedValue;
            } else if (!item.modId) {
                // Добавляем статический текст
                result += item.text;
            } else {
                // Если модификация не выбрана, добавляем X
                result += 'X';
            }
        });
        
        console.log('ProductModifications: Сформированный код:', result);
        return result;
    }
}
