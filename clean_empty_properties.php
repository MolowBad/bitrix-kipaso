<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$_SERVER["DOCUMENT_ROOT"] = __DIR__;
require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/iblock/prolog.php');

if(!CModule::IncludeModule('iblock')) {
    die('Модуль iblock не подключен');
}

$iblockId = 16;

/**
 * Функция для поиска и очистки пустых значений в свойствах товаров
 */
function cleanEmptyProperties($iblockId, $dryRun = true) {
    $cleanedProducts = 0;
    $cleanedProperties = 0;
    
    echo "<h3>" . ($dryRun ? "ТЕСТОВЫЙ РЕЖИМ" : "ОЧИСТКА ПУСТЫХ СВОЙСТВ") . "</h3>";
    
    // Получаем все товары
    $dbElements = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId],
        false,
        false,
        ['ID', 'NAME', 'XML_ID']
    );
    
    while ($element = $dbElements->Fetch()) {
        $elementId = $element['ID'];
        $hasEmptyProperties = false;
        
        // Проверяем DOCS
        $docsValues = [];
        $dbProps = CIBlockElement::GetProperty($iblockId, $elementId, [], ['CODE' => 'DOCS']);
        while ($prop = $dbProps->Fetch()) {
            if ($prop['VALUE']) {
                // Проверяем, существует ли файл
                $fileInfo = CFile::GetByID($prop['VALUE'])->Fetch();
                if ($fileInfo) {
                    $docsValues[] = $prop['VALUE'];
                } else {
                    echo "Товар ID {$elementId}: Найдена пустая ссылка DOCS на файл ID {$prop['VALUE']}<br>";
                    $hasEmptyProperties = true;
                    $cleanedProperties++;
                }
            }
        }
        
        // Проверяем SERT
        $certValues = [];
        $dbProps = CIBlockElement::GetProperty($iblockId, $elementId, [], ['CODE' => 'SERT']);
        while ($prop = $dbProps->Fetch()) {
            if ($prop['VALUE']) {
                // Проверяем, существует ли файл
                $fileInfo = CFile::GetByID($prop['VALUE'])->Fetch();
                if ($fileInfo) {
                    $certValues[] = $prop['VALUE'];
                } else {
                    echo "Товар ID {$elementId}: Найдена пустая ссылка SERT на файл ID {$prop['VALUE']}<br>";
                    $hasEmptyProperties = true;
                    $cleanedProperties++;
                }
            }
        }
        
        if ($hasEmptyProperties) {
            $cleanedProducts++;
            echo "Очищаем товар: ID {$elementId}, Название: {$element['NAME']}<br>";
            
            if (!$dryRun) {
                // Обновляем свойства товара
                $updateResult = CIBlockElement::SetPropertyValuesEx(
                    $elementId, 
                    $iblockId, 
                    [
                        'DOCS' => $docsValues,
                        'SERT' => $certValues
                    ]
                );
                
                if ($updateResult) {
                    echo "✓ Свойства товара обновлены<br>";
                } else {
                    echo "❌ Ошибка обновления свойств товара ID: {$elementId}<br>";
                }
            }
        }
    }
    
    echo "<hr><strong>Итого:</strong><br>";
    echo "Товаров с пустыми свойствами " . ($dryRun ? "найдено" : "очищено") . ": {$cleanedProducts}<br>";
    echo "Пустых ссылок " . ($dryRun ? "найдено" : "удалено") . ": {$cleanedProperties}<br>";
    
    return [
        'products' => $cleanedProducts,
        'properties' => $cleanedProperties
    ];
}

// Основная логика
echo "<h1>Очистка пустых свойств товаров</h1>";

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'analyze';
$dryRun = ($mode !== 'clean');

if ($dryRun) {
    echo "<p style='color: blue;'><strong>РЕЖИМ АНАЛИЗА</strong> - свойства не будут изменены</p>";
    echo "<p>Для реальной очистки добавьте параметр: <a href='?mode=clean'>?mode=clean</a></p>";
} else {
    echo "<p style='color: red;'><strong>РЕЖИМ ОЧИСТКИ</strong> - пустые свойства будут удалены!</p>";
}

echo "<hr>";

$result = cleanEmptyProperties($iblockId, $dryRun);

if ($dryRun && $result['properties'] > 0) {
    echo "<br><p style='color: red;'><strong>Внимание!</strong> Для реальной очистки запустите: <a href='?mode=clean' onclick='return confirm(\"Очистить пустые свойства?\")'><strong>?mode=clean</strong></a></p>";
}

echo "<hr>";
echo "<p>Анализ завершен: " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='cleanup_orphaned_files.php'>← Вернуться к основному скрипту</a></p>";
?>
