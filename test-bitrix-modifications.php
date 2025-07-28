<?php
// Простая тестовая страница для проверки модификаций в контексте Bitrix
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

// Имитируем данные товара для тестирования
$arResult = array(
    "ID" => "123",
    "NAME" => "2ТРМ0 - Двухканальный измеритель-регулятор температуры",
    "PROPERTIES" => array(
        "CML2_ARTICLE" => array(
            "VALUE" => "2trm0"
        )
    )
);

$APPLICATION->SetTitle("Тест модификаций товара в Bitrix");
?>

<style>
    /* Подключаем стили модификаций */
    <?php include($_SERVER["DOCUMENT_ROOT"]."/local/components/dresscode/catalog.item/templates/detail/css/product-modifications.css"); ?>
    
    .test-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .product-info {
        background: #f5f5f5;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
    }
</style>

<div class="test-container">
    <h1>Тест системы модификаций товара в Bitrix</h1>
    
    <div class="product-info">
        <h2><?= $arResult["NAME"] ?></h2>
        <p><strong>ID товара:</strong> <?= $arResult["ID"] ?></p>
        <p><strong>Артикул:</strong> <?= $arResult["PROPERTIES"]["CML2_ARTICLE"]["VALUE"] ?></p>
    </div>
    
    <!-- Блок выбора модификаций товара -->
    <div class="product-modifications" style="display: none;">
        <h3>Выберите модификацию товара:</h3>
        
        <!-- Контейнер для шаблона модификации -->
        <div class="modification-template">
            <h4>Шаблон модификации:</h4>
            <div class="template-display" id="modification-template"></div>
        </div>
        
        <!-- Контейнер для групп модификаций -->
        <div class="modification-groups" id="modification-groups"></div>
    </div>
    
    <!-- Блок результата -->
    <div class="modification-result-block" style="display: none;" id="result-block">
        <div class="modification-result-title">Выбранная модификация:</div>
        <div class="modification-result" id="modification-result"></div>
    </div>
</div>

<script src="/local/templates/dresscode/components/bitrix/catalog/catalog/bitrix/catalog.element/.default/js/product-modifications.js"></script>
<script>
    // Инициализация модификаций товара
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM загружен, начинаем инициализацию модификаций товара');
        
        // Отладка для всех свойств товара
        console.log('Все свойства товара:', <?= json_encode(array_keys($arResult["PROPERTIES"] ?? [])) ?>);
        console.log('Название товара:', <?= json_encode($arResult["NAME"]) ?>);
        console.log('ID товара:', <?= json_encode($arResult["ID"]) ?>);
        
        // Получаем артикул товара из разных источников
        <?php 
        $productSku = '';
        
        // Пробуем получить из свойства CML2_ARTICLE
        if (!empty($arResult["PROPERTIES"]["CML2_ARTICLE"]["VALUE"])) {
            $productSku = $arResult["PROPERTIES"]["CML2_ARTICLE"]["VALUE"];
        }
        // Пробуем получить из свойства ARTNUMBER
        elseif (!empty($arResult["PROPERTIES"]["ARTNUMBER"]["VALUE"])) {
            $productSku = $arResult["PROPERTIES"]["ARTNUMBER"]["VALUE"];
        }
        // Пробуем получить из названия
        elseif (preg_match('/^([a-zA-Z0-9]+)/', $arResult["NAME"], $matches)) {
            $productSku = $matches[1];
        }
        // Для тестирования используем фиксированный артикул
        else {
            $productSku = '2trm0'; // Тестовый артикул из JSON
        }
        ?>
        var productSku = "<?= $productSku ?>";
        
        console.log('Полученный артикул товара:', productSku);
        
        // Если артикул найден, инициализируем модификации
        if (productSku) {
            // Приведем артикул к нижнему регистру для соответствия с JSON
            productSku = productSku.toLowerCase();
            console.log('Артикул в нижнем регистре:', productSku);
            
            // Проверяем наличие блоков для модификаций
            console.log('Блок модификаций:', document.querySelector('.product-modifications'));
            console.log('Блок результата:', document.querySelector('#modification-result'));
            
            var productMods = new ProductModifications({
                productSku: productSku,
                resultSelector: '#modification-result',
                modBlockSelector: '.product-modifications'
            });
            productMods.init();
        } else {
            console.error('Артикул товара не найден!');
        }
    });
</script>

<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>
