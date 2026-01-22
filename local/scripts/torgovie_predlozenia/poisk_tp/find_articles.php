<?
// test_article_properties.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$testProductId = 140; // ID товара из вашего теста

$res = CIBlockElement::GetList(
    [],
    ['ID' => $testProductId],
    false,
    false,
    ['ID', 'NAME', 'CODE', 'IBLOCK_ID']
);

if ($element = $res->Fetch()) {
    echo "Товар: {$element['NAME']} (ID: {$element['ID']})<br>";
    echo "CODE: {$element['CODE']}<br>";
    echo "IBLOCK_ID: {$element['IBLOCK_ID']}<br><br>";
    
    // Получаем все свойства
    $properties = CIBlockElement::GetProperty(
        $element['IBLOCK_ID'],
        $testProductId
    );
    
    echo "Все свойства товара:<br>";
    while ($prop = $properties->Fetch()) {
        echo "{$prop['CODE']} = {$prop['VALUE']}<br>";
    }
}