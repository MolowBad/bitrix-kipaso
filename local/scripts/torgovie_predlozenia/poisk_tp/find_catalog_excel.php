<?php
// test_all_products_and_offers.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!\Bitrix\Main\Loader::includeModule('iblock') || !\Bitrix\Main\Loader::includeModule('catalog')) {
    die("Модули не подключены");
}

echo "<h2>АНАЛИЗ ВСЕХ ТОВАРОВ: ПРОСТЫЕ И С ТОРГОВЫМИ ПРЕДЛОЖЕНИЯМИ</h2>";

// Конфигурация
$PRODUCT_IBLOCK_ID = 16;

// Получаем информацию о SKU
$skuInfo = CCatalogSKU::getInfoByProductIBlock($PRODUCT_IBLOCK_ID);

echo "<h3>📊 ИНФОРМАЦИЯ О СТРУКТУРЕ:</h3>";
if ($skuInfo) {
    echo "✅ Найдены торговые предложения<br>";
    echo "IBLOCK_ID предложений: {$skuInfo['IBLOCK_ID']}<br>";
    echo "Свойство связи: {$skuInfo['SKU_PROPERTY_ID']}<br>";
} else {
    echo "❌ Торговые предложения не настроены<br>";
}

// Получаем все товары
$res = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    ['IBLOCK_ID' => $PRODUCT_IBLOCK_ID, 'ACTIVE' => 'Y'],
    false,
    ['nTopCount' => 50], // Ограничим для теста
    ['ID', 'NAME', 'CODE', 'XML_ID']
);

$simpleProducts = 0;
$productsWithOffers = 0;
$totalOffers = 0;

echo "<h3>🔍 АНАЛИЗ ТОВАРОВ:</h3>";

while ($product = $res->Fetch()) {
    $hasOffers = false;
    $offerCount = 0;
    
    // Проверяем есть ли торговые предложения
    if ($skuInfo) {
        $offerRes = CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $skuInfo['IBLOCK_ID'],
                'PROPERTY_' . $skuInfo['SKU_PROPERTY_ID'] => $product['ID'],
                'ACTIVE' => 'Y'
            ],
            false,
            false,
            ['ID']
        );
        $offerCount = $offerRes->SelectedRowsCount();
        $hasOffers = ($offerCount > 0);
    }
    
    if ($hasOffers) {
        $productsWithOffers++;
        $totalOffers += $offerCount;
        $borderColor = '#0066cc';
        $bgColor = '#e6f2ff';
        $typeBadge = '🛒 С ТП';
    } else {
        $simpleProducts++;
        $borderColor = '#00aa00';
        $bgColor = '#f0fff0';
        $typeBadge = '📦 ПРОСТОЙ';
    }
    
    echo "<div style='border: 2px solid {$borderColor}; padding: 15px; margin: 10px 0; background: {$bgColor};'>";
    echo "<h4>{$typeBadge} ТОВАР: {$product['NAME']} (ID: {$product['ID']})</h4>";
    echo "CODE: <strong>{$product['CODE']}</strong><br>";
    echo "XML_ID: {$product['XML_ID']}<br>";
    echo "Торговых предложений: <strong>{$offerCount}</strong><br>";
    
    // Свойства основного товара
    echo "<h5>Свойства основного товара:</h5>";
    $properties = CIBlockElement::GetProperty($PRODUCT_IBLOCK_ID, $product['ID']);
    
    $foundArticle = false;
    while ($prop = $properties->Fetch()) {
        $value = $prop['VALUE'];
        $isNumeric = is_numeric($value) && $value > 1000;
        $isArticle = in_array($prop['CODE'], ['ARTICLE', 'CML2_ARTICLE', 'ARTIKUL']);
        
        if ($isNumeric || $isArticle) {
            echo "<span style='color: green; font-weight: bold;'>";
            if ($isNumeric) $foundArticle = true;
        }
        
        echo "{$prop['CODE']} = {$value}";
        
        if ($isNumeric) echo " (числовой артикул)";
        if ($isArticle) echo " (свойство артикул)";
        
        if ($isNumeric || $isArticle) echo "</span>";
        echo "<br>";
    }
    
    if (!$foundArticle) {
        echo "<span style='color: #999;'>Числовых артикулов не найдено</span><br>";
    }
    
    // Если есть торговые предложения - покажем их IZD коды
    if ($hasOffers && $skuInfo) {
        echo "<h5>🔍 Торговые предложения (IZD коды):</h5>";
        $offerRes = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $skuInfo['IBLOCK_ID'],
                'PROPERTY_' . $skuInfo['SKU_PROPERTY_ID'] => $product['ID'],
                'ACTIVE' => 'Y'
            ],
            false,
            false,
            ['ID', 'NAME', 'CODE', 'XML_ID']
        );
        
        while ($offer = $offerRes->Fetch()) {
            echo "<div style='border: 1px solid #ccc; padding: 8px; margin: 5px 0; background: #fff;'>";
            echo "<strong>ТП ID: {$offer['ID']}</strong> - {$offer['NAME']}<br>";
            echo "CODE: {$offer['CODE']}, XML_ID: {$offer['XML_ID']}<br>";
            
            // Ищем IZD код
            $izdFound = false;
            $offerProperties = CIBlockElement::GetProperty($skuInfo['IBLOCK_ID'], $offer['ID']);
            
            while ($offerProp = $offerProperties->Fetch()) {
                if ($offerProp['CODE'] == 'IZD' && !empty($offerProp['VALUE'])) {
                    echo "<span style='color: red; font-weight: bold;'>IZD = {$offerProp['VALUE']} ⭐</span><br>";
                    $izdFound = true;
                }
            }
            
            if (!$izdFound) {
                echo "<span style='color: orange;'>IZD: не найден</span><br>";
            }
            
            echo "</div>";
        }
    }
    
    echo "</div>";
}

// Статистика
echo "<h3>📊 СТАТИСТИКА:</h3>";
echo "Всего товаров: " . ($simpleProducts + $productsWithOffers) . "<br>";
echo "Простых товаров: <strong>{$simpleProducts}</strong><br>";
echo "Товаров с торговыми предложениями: <strong>{$productsWithOffers}</strong><br>";
echo "Всего торговых предложений: <strong>{$totalOffers}</strong><br>";

// Анализ IZD кодов в торговых предложениях
if ($skuInfo && $totalOffers > 0) {
    echo "<h3>🔎 АНАЛИЗ IZD КОДОВ В ТОРГОВЫХ ПРЕДЛОЖЕНИЯХ:</h3>";
    
    $izdRes = CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $skuInfo['IBLOCK_ID'],
            'ACTIVE' => 'Y'
        ],
        false,
        false,
        ['ID']
    );
    
    $offersWithIzd = 0;
    $totalOffersChecked = $izdRes->SelectedRowsCount();
    
    $izdRes = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        [
            'IBLOCK_ID' => $skuInfo['IBLOCK_ID'],
            'ACTIVE' => 'Y'
        ],
        false,
        ['nTopCount' => 100],
        ['ID']
    );
    
    while ($offer = $izdRes->Fetch()) {
        $propertyRes = CIBlockElement::GetProperty(
            $skuInfo['IBLOCK_ID'],
            $offer['ID'],
            ["sort" => "asc"],
            ["CODE" => "IZD"]
        );
        
        if ($prop = $propertyRes->Fetch()) {
            if (!empty($prop['VALUE'])) {
                $offersWithIzd++;
            }
        }
    }
    
    echo "Всего торговых предложений: {$totalOffersChecked}<br>";
    echo "С IZD кодом: <strong>{$offersWithIzd}</strong><br>";
    echo "Без IZD кода: " . ($totalOffersChecked - $offersWithIzd) . "<br>";
}

echo "<h3>🎯 РЕКОМЕНДАЦИИ ДЛЯ СИСТЕМЫ ДОСТАВКИ:</h3>";

if ($productsWithOffers > 0) {
    echo "✅ <strong>Для товаров с торговыми предложениями:</strong> искать IZD коды в ТП<br>";
}

if ($simpleProducts > 0) {
    echo "✅ <strong>Для простых товаров:</strong> искать артикулы в основном товаре<br>";
    echo "&nbsp;&nbsp;&nbsp;Нужна таблица соответствия CODE → числовые артикулы<br>";
}

echo "</div>";
?>