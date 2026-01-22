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
                // Удаляем битые ссылки напрямую из таблицы свойств
                $docsSuccess = true;
                $certSuccess = true;
                
                // Очищаем DOCS
                $query = "DELETE FROM b_iblock_element_property WHERE IBLOCK_ELEMENT_ID = " . intval($elementId) . " AND IBLOCK_PROPERTY_ID = (SELECT ID FROM b_iblock_property WHERE CODE = 'DOCS' AND IBLOCK_ID = " . intval($iblockId) . ")";
                $result = $GLOBALS['DB']->Query($query);
                if (!$result) {
                    $docsSuccess = false;
                }
                
                // Добавляем обратно только рабочие ссылки DOCS
                if ($docsSuccess && !empty($docsValues)) {
                    foreach ($docsValues as $docValue) {
                        $query = "INSERT INTO b_iblock_element_property (IBLOCK_ELEMENT_ID, IBLOCK_PROPERTY_ID, VALUE) VALUES (" . intval($elementId) . ", (SELECT ID FROM b_iblock_property WHERE CODE = 'DOCS' AND IBLOCK_ID = " . intval($iblockId) . "), " . intval($docValue) . ")";
                        $GLOBALS['DB']->Query($query);
                    }
                }
                
                // Очищаем SERT
                $query = "DELETE FROM b_iblock_element_property WHERE IBLOCK_ELEMENT_ID = " . intval($elementId) . " AND IBLOCK_PROPERTY_ID = (SELECT ID FROM b_iblock_property WHERE CODE = 'SERT' AND IBLOCK_ID = " . intval($iblockId) . ")";
                $result = $GLOBALS['DB']->Query($query);
                if (!$result) {
                    $certSuccess = false;
                }
                
                // Добавляем обратно только рабочие ссылки SERT
                if ($certSuccess && !empty($certValues)) {
                    foreach ($certValues as $certValue) {
                        $query = "INSERT INTO b_iblock_element_property (IBLOCK_ELEMENT_ID, IBLOCK_PROPERTY_ID, VALUE) VALUES (" . intval($elementId) . ", (SELECT ID FROM b_iblock_property WHERE CODE = 'SERT' AND IBLOCK_ID = " . intval($iblockId) . "), " . intval($certValue) . ")";
                        $GLOBALS['DB']->Query($query);
                    }
                }
                
                if ($docsSuccess && $certSuccess) {
                    echo "✓ Свойства товара очищены от битых ссылок<br>";
                    
                    // Очищаем кеш для этого товара
                    $GLOBALS['CACHE_MANAGER']->ClearByTag('iblock_id_' . $iblockId);
                } else {
                    echo "❌ Ошибка очистки свойств товара ID: {$elementId}<br>";
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

/**
 * Функция для поиска дублей файлов
 */
function findDuplicates($iblockId) {
    $files = [];
    $duplicates = [];
    $totalSize = 0;
    $duplicateSize = 0;
    
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
        
        // Проверяем DOCS
        $dbProps = CIBlockElement::GetProperty($iblockId, $elementId, [], ['CODE' => 'DOCS']);
        while ($prop = $dbProps->Fetch()) {
            if ($prop['VALUE']) {
                $fileInfo = CFile::GetByID($prop['VALUE'])->Fetch();
                if ($fileInfo) {
                    $key = $fileInfo['ORIGINAL_NAME'] . '_' . $fileInfo['FILE_SIZE'];
                    if (!isset($files[$key])) {
                        $files[$key] = [];
                    }
                    $files[$key][] = [
                        'ID' => $fileInfo['ID'],
                        'PATH' => $fileInfo['SRC'],
                        'SIZE' => $fileInfo['FILE_SIZE'],
                        'NAME' => $fileInfo['ORIGINAL_NAME'],
                        'ELEMENT_ID' => $elementId,
                        'ELEMENT_NAME' => $element['NAME'],
                        'PROPERTY' => 'DOCS'
                    ];
                    $totalSize += $fileInfo['FILE_SIZE'];
                }
            }
        }
        
        // Проверяем SERT
        $dbProps = CIBlockElement::GetProperty($iblockId, $elementId, [], ['CODE' => 'SERT']);
        while ($prop = $dbProps->Fetch()) {
            if ($prop['VALUE']) {
                $fileInfo = CFile::GetByID($prop['VALUE'])->Fetch();
                if ($fileInfo) {
                    $key = $fileInfo['ORIGINAL_NAME'] . '_' . $fileInfo['FILE_SIZE'];
                    if (!isset($files[$key])) {
                        $files[$key] = [];
                    }
                    $files[$key][] = [
                        'ID' => $fileInfo['ID'],
                        'PATH' => $fileInfo['SRC'],
                        'SIZE' => $fileInfo['FILE_SIZE'],
                        'NAME' => $fileInfo['ORIGINAL_NAME'],
                        'ELEMENT_ID' => $elementId,
                        'ELEMENT_NAME' => $element['NAME'],
                        'PROPERTY' => 'SERT'
                    ];
                    $totalSize += $fileInfo['FILE_SIZE'];
                }
            }
        }
    }
    
    // Находим дубли
    foreach ($files as $key => $fileGroup) {
        if (count($fileGroup) > 1) {
            $duplicates[$key] = $fileGroup;
            // Считаем размер дублей (исключая первый файл)
            for ($i = 1; $i < count($fileGroup); $i++) {
                $duplicateSize += $fileGroup[$i]['SIZE'];
            }
        }
    }
    
    return [
        'duplicates' => $duplicates,
        'totalSize' => $totalSize,
        'duplicateSize' => $duplicateSize
    ];
}

/**
 * Функция для удаления дублей
 */
function removeDuplicates($iblockId, $duplicates) {
    $removedFiles = 0;
    $freedSpace = 0;
    $updatedProducts = 0;
    $filesToDelete = []; // Список файлов для удаления
    
    echo "<h3>Этап 1: Обновление ссылок в товарах</h3>";
    
    // Сначала обновляем ВСЕ ссылки во всех товарах
    foreach ($duplicates as $key => $fileGroup) {
        $originalFile = $fileGroup[0]; // Оставляем первый файл
        echo "<h4>Обработка группы дублей: {$originalFile['NAME']}</h4>";
        echo "Оригинал: ID {$originalFile['ID']} (товар: {$originalFile['ELEMENT_NAME']})<br>";
        
        // Обрабатываем дубли (начиная со второго файла)
        for ($i = 1; $i < count($fileGroup); $i++) {
            $duplicateFile = $fileGroup[$i];
            echo "Обновляем ссылки для дубля: ID {$duplicateFile['ID']} (товар: {$duplicateFile['ELEMENT_NAME']})<br>";
            
            // Находим ВСЕ товары, которые используют этот дубль
            $elementsToUpdate = [];
            
            // Ищем в DOCS
            $dbElements = CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => $iblockId, 'PROPERTY_DOCS' => $duplicateFile['ID']],
                false,
                false,
                ['ID', 'NAME']
            );
            while ($element = $dbElements->Fetch()) {
                $elementsToUpdate[] = ['ID' => $element['ID'], 'NAME' => $element['NAME'], 'PROPERTY' => 'DOCS'];
            }
            
            // Ищем в SERT
            $dbElements = CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => $iblockId, 'PROPERTY_SERT' => $duplicateFile['ID']],
                false,
                false,
                ['ID', 'NAME']
            );
            while ($element = $dbElements->Fetch()) {
                $elementsToUpdate[] = ['ID' => $element['ID'], 'NAME' => $element['NAME'], 'PROPERTY' => 'SERT'];
            }
            
            // Обновляем все найденные товары
            foreach ($elementsToUpdate as $elementInfo) {
                $elementId = $elementInfo['ID'];
                $propertyCode = $elementInfo['PROPERTY'];
                
                echo "&nbsp;&nbsp;- Обновляем товар: {$elementInfo['NAME']} (свойство {$propertyCode})<br>";
                
                // Используем прямые SQL-запросы для надежного обновления
                $success = true;
                
                // Заменяем ссылку на дубль на ссылку на оригинал
                $query = "UPDATE b_iblock_element_property SET VALUE = " . intval($originalFile['ID']) . " WHERE IBLOCK_ELEMENT_ID = " . intval($elementId) . " AND IBLOCK_PROPERTY_ID = (SELECT ID FROM b_iblock_property WHERE CODE = '" . $propertyCode . "' AND IBLOCK_ID = " . intval($iblockId) . ") AND VALUE = " . intval($duplicateFile['ID']);
                
                $result = $GLOBALS['DB']->Query($query);
                if (!$result) {
                    $success = false;
                }
                
                if ($success) {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;✓ Ссылки обновлены через SQL<br>";
                    $updatedProducts++;
                } else {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;❌ Ошибка SQL-обновления ссылок<br>";
                }
            }
            
            // Добавляем файл в список для удаления
            $filesToDelete[] = [
                'ID' => $duplicateFile['ID'],
                'SIZE' => $duplicateFile['SIZE'],
                'NAME' => $duplicateFile['NAME']
            ];
        }
        
        echo "<br>";
    }
    
    echo "<hr><h3>Этап 2: Удаление файлов-дублей</h3>";
    
    // Теперь удаляем все файлы-дубли
    foreach ($filesToDelete as $fileInfo) {
        echo "Удаляем файл: ID {$fileInfo['ID']} ({$fileInfo['NAME']})<br>";
        
        if (CFile::Delete($fileInfo['ID'])) {
            $removedFiles++;
            $freedSpace += $fileInfo['SIZE'];
            echo "✓ Файл удален успешно<br>";
        } else {
            echo "❌ Ошибка удаления файла<br>";
        }
    }
    
    return [
        'removedFiles' => $removedFiles,
        'freedSpace' => $freedSpace,
        'updatedProducts' => $updatedProducts
    ];
}

/**
 * Форматирование размера файла
 */
function formatBytes($size, $precision = 2) {
    if ($size == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}

// Основная логика
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'analyze';

echo "<h1>Скрипт управления дублями и пустыми свойствами</h1>";

if ($mode === 'clean') {
    // Режим очистки пустых свойств
    echo "<h2>Очистка пустых свойств товаров</h2>";
    echo "<p style='color: red;'><strong>РЕЖИМ ОЧИСТКИ</strong> - пустые свойства будут удалены!</p>";
    echo "<hr>";
    
    $result = cleanEmptyProperties($iblockId, false);
    
    echo "<hr>";
    echo "<p>Очистка завершена: " . date('Y-m-d H:i:s') . "</p>";
    
} elseif ($mode === 'remove') {
    // Режим удаления дублей
    echo "<h2>Удаление дублей файлов</h2>";
    echo "<p style='color: red;'><strong>РЕЖИМ УДАЛЕНИЯ</strong> - дубли будут удалены!</p>";
    echo "<hr>";
    
    $analysis = findDuplicates($iblockId);
    
    if (count($analysis['duplicates']) > 0) {
        echo "<p>Найдено групп дублей: " . count($analysis['duplicates']) . "</p>";
        echo "<p>Размер дублей для удаления: " . formatBytes($analysis['duplicateSize']) . "</p>";
        echo "<hr>";
        
        $result = removeDuplicates($iblockId, $analysis['duplicates']);
        
        echo "<hr><h3>Результат удаления:</h3>";
        echo "<p>✓ Удалено файлов: {$result['removedFiles']}</p>";
        echo "<p>✓ Освобождено места: " . formatBytes($result['freedSpace']) . "</p>";
        echo "<p>✓ Обновлено товаров: {$result['updatedProducts']}</p>";
    } else {
        echo "<p style='color: green;'>Дубли файлов не найдены!</p>";
    }
    
    echo "<hr>";
    echo "<p>Удаление завершено: " . date('Y-m-d H:i:s') . "</p>";
    
} else {
    // Режим анализа (по умолчанию)
    echo "<h2>Анализ дублей файлов</h2>";
    echo "<p style='color: blue;'><strong>РЕЖИМ АНАЛИЗА</strong> - файлы не будут изменены</p>";
    echo "<hr>";
    
    $analysis = findDuplicates($iblockId);
    
    echo "<h3>Статистика:</h3>";
    echo "<p>Общий размер всех файлов: " . formatBytes($analysis['totalSize']) . "</p>";
    echo "<p>Найдено групп дублей: " . count($analysis['duplicates']) . "</p>";
    echo "<p>Размер дублей: " . formatBytes($analysis['duplicateSize']) . "</p>";
    
    if (count($analysis['duplicates']) > 0) {
        echo "<hr><h3>Детали дублей:</h3>";
        
        foreach ($analysis['duplicates'] as $key => $fileGroup) {
            echo "<h4>Группа: {$fileGroup[0]['NAME']} (" . formatBytes($fileGroup[0]['SIZE']) . ")</h4>";
            foreach ($fileGroup as $index => $file) {
                $label = $index === 0 ? "<strong>ОРИГИНАЛ</strong>" : "дубль";
                echo "- {$label}: ID {$file['ID']} в товаре '{$file['ELEMENT_NAME']}' (свойство {$file['PROPERTY']})<br>";
            }
            echo "<br>";
        }
        
        echo "<hr>";
        echo "<p style='color: red;'><strong>Для удаления дублей:</strong> <a href='?mode=remove' onclick='return confirm(\"Удалить все дубли?\")'>?mode=remove</a></p>";
    }
    
    // Анализ пустых свойств
    echo "<hr><h2>Анализ пустых свойств</h2>";
    $emptyResult = cleanEmptyProperties($iblockId, true);
    
    if ($emptyResult['properties'] > 0) {
        echo "<p style='color: red;'><strong>Для очистки пустых свойств:</strong> <a href='?mode=clean' onclick='return confirm(\"Очистить пустые свойства?\")'>?mode=clean</a></p>";
    }
    
    echo "<hr>";
    echo "<p>Анализ завершен: " . date('Y-m-d H:i:s') . "</p>";
}

echo "<hr>";
echo "<p><strong>Доступные режимы:</strong></p>";
echo "<ul>";
echo "<li><a href='dubli.php'>Анализ (по умолчанию)</a></li>";
echo "<li><a href='dubli.php?mode=remove'>Удаление дублей</a></li>";
echo "<li><a href='dubli.php?mode=clean'>Очистка пустых свойств</a></li>";
echo "</ul>";
?>
