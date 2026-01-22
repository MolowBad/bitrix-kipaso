<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Убираем лимит времени
ini_set('max_execution_time', 0);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$_SERVER["DOCUMENT_ROOT"] = __DIR__;
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/iblock/prolog.php');

if(!CModule::IncludeModule('iblock')) {
    die('Модуль iblock не подключен');
}

/**
 * Функция для получения всех файлов из базы данных Bitrix
 */
function getAllFilesFromDB() {
    $dbFiles = [];
    
    // Получаем все файлы из таблицы b_file
    $query = "SELECT ID, FILE_NAME, ORIGINAL_NAME, FILE_SIZE, SUBDIR, MODULE_ID 
              FROM b_file 
              WHERE MODULE_ID = 'iblock'";
    
    $result = $GLOBALS['DB']->Query($query);
    
    while ($file = $result->Fetch()) {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $file['SUBDIR'] . '/' . $file['FILE_NAME'];
        $dbFiles[$file['ID']] = [
            'ID' => $file['ID'],
            'FILE_NAME' => $file['FILE_NAME'],
            'ORIGINAL_NAME' => $file['ORIGINAL_NAME'],
            'FILE_SIZE' => $file['FILE_SIZE'],
            'SUBDIR' => $file['SUBDIR'],
            'FULL_PATH' => $filePath,
            'EXISTS' => file_exists($filePath)
        ];
    }
    
    return $dbFiles;
}

/**
 * Функция для сканирования физических файлов в папке upload
 */
function scanUploadDirectory($uploadDir) {
    $physicalFiles = [];
    
    if (!is_dir($uploadDir)) {
        return $physicalFiles;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relativePath = str_replace($uploadDir . '/', '', $file->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath);
            
            $physicalFiles[$file->getPathname()] = [
                'FULL_PATH' => $file->getPathname(),
                'RELATIVE_PATH' => $relativePath,
                'FILE_NAME' => $file->getFilename(),
                'FILE_SIZE' => $file->getSize(),
                'MODIFIED' => $file->getMTime()
            ];
        }
    }
    
    return $physicalFiles;
}

/**
 * Функция для поиска файлов-сирот
 */
function findOrphanedFiles($dbFiles, $physicalFiles) {
    $orphanedFiles = [];
    $dbFilePaths = [];
    
    // Создаем массив путей файлов из БД
    foreach ($dbFiles as $dbFile) {
        $dbFilePaths[] = $dbFile['FULL_PATH'];
    }
    
    // Ищем физические файлы, которых нет в БД
    foreach ($physicalFiles as $physicalFile) {
        if (!in_array($physicalFile['FULL_PATH'], $dbFilePaths)) {
            $orphanedFiles[] = $physicalFile;
        }
    }
    
    return $orphanedFiles;
}

/**
 * Функция для удаления файлов-сирот
 */
function removeOrphanedFiles($orphanedFiles, $dryRun = true) {
    $removedCount = 0;
    $savedSpace = 0;
    $errors = [];
    
    echo "<h3>" . ($dryRun ? "ТЕСТОВЫЙ РЕЖИМ" : "УДАЛЕНИЕ ФАЙЛОВ-СИРОТ") . "</h3>";
    
    foreach ($orphanedFiles as $file) {
        echo "Файл-сирота: {$file['RELATIVE_PATH']} (" . formatBytes($file['FILE_SIZE']) . ")<br>";
        
        if (!$dryRun) {
            if (unlink($file['FULL_PATH'])) {
                echo "✓ Удален: {$file['RELATIVE_PATH']}<br>";
                $removedCount++;
                $savedSpace += $file['FILE_SIZE'];
            } else {
                $error = "❌ Ошибка удаления: {$file['RELATIVE_PATH']}";
                echo "{$error}<br>";
                $errors[] = $error;
            }
        } else {
            $removedCount++;
            $savedSpace += $file['FILE_SIZE'];
        }
    }
    
    echo "<hr><strong>Итого:</strong><br>";
    echo "Файлов-сирот " . ($dryRun ? "найдено" : "удалено") . ": {$removedCount}<br>";
    echo "Места " . ($dryRun ? "можно освободить" : "освобождено") . ": " . formatBytes($savedSpace) . "<br>";
    
    if (!empty($errors)) {
        echo "<br><strong>Ошибки:</strong><br>";
        foreach ($errors as $error) {
            echo "- {$error}<br>";
        }
    }
    
    return [
        'removed' => $removedCount,
        'saved_space' => $savedSpace,
        'errors' => $errors
    ];
}

/**
 * Функция для поиска битых ссылок в БД
 */
function findBrokenFileLinks($dbFiles) {
    $brokenLinks = [];
    
    foreach ($dbFiles as $file) {
        if (!$file['EXISTS']) {
            $brokenLinks[] = $file;
        }
    }
    
    return $brokenLinks;
}

/**
 * Функция для поиска товаров, использующих битые файлы
 */
function findProductsUsingBrokenFiles($brokenFileIds, $iblockId = 16) {
    $productsWithBrokenFiles = [];
    
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
        $brokenFiles = [];
        
        // Проверяем DOCS
        $dbProps = CIBlockElement::GetProperty($iblockId, $elementId, [], ['CODE' => 'DOCS']);
        while ($prop = $dbProps->Fetch()) {
            if ($prop['VALUE'] && in_array($prop['VALUE'], $brokenFileIds)) {
                $brokenFiles[] = [
                    'PROPERTY' => 'DOCS',
                    'FILE_ID' => $prop['VALUE']
                ];
            }
        }
        
        // Проверяем SERT
        $dbProps = CIBlockElement::GetProperty($iblockId, $elementId, [], ['CODE' => 'SERT']);
        while ($prop = $dbProps->Fetch()) {
            if ($prop['VALUE'] && in_array($prop['VALUE'], $brokenFileIds)) {
                $brokenFiles[] = [
                    'PROPERTY' => 'SERT',
                    'FILE_ID' => $prop['VALUE']
                ];
            }
        }
        
        if (!empty($brokenFiles)) {
            $productsWithBrokenFiles[$elementId] = [
                'ELEMENT_INFO' => $element,
                'BROKEN_FILES' => $brokenFiles
            ];
        }
    }
    
    return $productsWithBrokenFiles;
}

/**
 * Функция для удаления битых ссылок из БД
 */
function removeBrokenFileLinks($brokenLinks, $dryRun = true) {
    $removedCount = 0;
    $errors = [];
    $iblockId = 16;
    
    echo "<h3>" . ($dryRun ? "ТЕСТОВЫЙ РЕЖИМ" : "УДАЛЕНИЕ БИТЫХ ССЫЛОК") . " из базы данных</h3>";
    
    // Собираем ID битых файлов
    $brokenFileIds = [];
    foreach ($brokenLinks as $file) {
        $brokenFileIds[] = $file['ID'];
    }
    
    // Находим товары, использующие битые файлы
    echo "Поиск товаров с битыми ссылками...<br>";
    $productsWithBrokenFiles = findProductsUsingBrokenFiles($brokenFileIds, $iblockId);
    echo "Найдено товаров с битыми ссылками: " . count($productsWithBrokenFiles) . "<br><br>";
    
    // Очищаем свойства товаров от битых ссылок
    foreach ($productsWithBrokenFiles as $elementId => $productData) {
        echo "Товар ID: {$elementId}, Название: {$productData['ELEMENT_INFO']['NAME']}<br>";
        
        foreach ($productData['BROKEN_FILES'] as $brokenFile) {
            echo "  - Удаляем битую ссылку на файл ID {$brokenFile['FILE_ID']} из свойства {$brokenFile['PROPERTY']}<br>";
            
            if (!$dryRun) {
                // Получаем текущие значения свойства
                $currentValues = [];
                $dbProps = CIBlockElement::GetProperty(
                    $iblockId, 
                    $elementId, 
                    [], 
                    ['CODE' => $brokenFile['PROPERTY']]
                );
                
                while ($prop = $dbProps->Fetch()) {
                    if ($prop['VALUE'] && $prop['VALUE'] != $brokenFile['FILE_ID']) {
                        $currentValues[] = $prop['VALUE'];
                    }
                }
                
                // Обновляем свойство товара (убираем битую ссылку)
                $updateResult = CIBlockElement::SetPropertyValuesEx(
                    $elementId, 
                    $iblockId, 
                    [$brokenFile['PROPERTY'] => $currentValues]
                );
                
                if ($updateResult) {
                    echo "    ✓ Свойство {$brokenFile['PROPERTY']} товара обновлено<br>";
                } else {
                    $error = "❌ Ошибка обновления свойства {$brokenFile['PROPERTY']} товара ID: {$elementId}";
                    echo "    {$error}<br>";
                    $errors[] = $error;
                }
            }
        }
    }
    
    echo "<br>";
    
    // Теперь удаляем записи файлов из БД
    foreach ($brokenLinks as $file) {
        echo "Удаляем запись файла: ID {$file['ID']}, {$file['ORIGINAL_NAME']}<br>";
        
        if (!$dryRun) {
            // Прямое удаление из таблицы b_file
            $query = "DELETE FROM b_file WHERE ID = " . intval($file['ID']);
            $result = $GLOBALS['DB']->Query($query);
            
            if ($result) {
                echo "✓ Удалена запись ID: {$file['ID']}<br>";
                $removedCount++;
            } else {
                $error = "❌ Ошибка удаления записи ID: {$file['ID']}";
                echo "{$error}<br>";
                $errors[] = $error;
            }
        } else {
            $removedCount++;
        }
    }
    
    echo "<hr><strong>Итого:</strong><br>";
    echo "Битых ссылок " . ($dryRun ? "найдено" : "удалено") . ": {$removedCount}<br>";
    echo "Товаров очищено: " . count($productsWithBrokenFiles) . "<br>";
    
    if (!empty($errors)) {
        echo "<br><strong>Ошибки:</strong><br>";
        foreach ($errors as $error) {
            echo "- {$error}<br>";
        }
    }
    
    return [
        'removed' => $removedCount,
        'products_cleaned' => count($productsWithBrokenFiles),
        'errors' => $errors
    ];
}

function formatBytes($size, $precision = 2) {
    $units = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

// Основная логика скрипта
echo "<h1>Очистка файлов-сирот и битых ссылок</h1>";

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'analyze';
$action = isset($_GET['action']) ? $_GET['action'] : 'all';
$dryRun = ($mode !== 'remove');

if ($dryRun) {
    echo "<p style='color: blue;'><strong>РЕЖИМ АНАЛИЗА</strong> - файлы не будут удалены</p>";
    echo "<p>Для реального удаления добавьте параметр: <a href='?mode=remove&action={$action}'>?mode=remove</a></p>";
} else {
    echo "<p style='color: red;'><strong>РЕЖИМ УДАЛЕНИЯ</strong> - файлы будут удалены!</p>";
}

echo "<hr>";

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/upload';

if ($action === 'orphaned' || $action === 'all') {
    echo "<h2>1. Поиск файлов-сирот (есть на диске, нет в БД)</h2>";
    
    echo "Сканирование базы данных...<br>";
    $dbFiles = getAllFilesFromDB();
    echo "Найдено файлов в БД: " . count($dbFiles) . "<br>";
    
    echo "Сканирование папки /upload/...<br>";
    $physicalFiles = scanUploadDirectory($uploadDir);
    echo "Найдено физических файлов: " . count($physicalFiles) . "<br>";
    
    echo "Поиск файлов-сирот...<br>";
    $orphanedFiles = findOrphanedFiles($dbFiles, $physicalFiles);
    
    if (!empty($orphanedFiles)) {
        echo "<strong>Найдено файлов-сирот: " . count($orphanedFiles) . "</strong><br><br>";
        $orphanResult = removeOrphanedFiles($orphanedFiles, $dryRun);
    } else {
        echo "<p style='color: green;'>Файлы-сироты не найдены!</p>";
    }
    
    echo "<hr>";
}

if ($action === 'broken' || $action === 'all') {
    echo "<h2>2. Поиск битых ссылок (есть в БД, нет на диске)</h2>";
    
    if (!isset($dbFiles)) {
        $dbFiles = getAllFilesFromDB();
    }
    
    $brokenLinks = findBrokenFileLinks($dbFiles);
    
    if (!empty($brokenLinks)) {
        echo "<strong>Найдено битых ссылок: " . count($brokenLinks) . "</strong><br><br>";
        $brokenResult = removeBrokenFileLinks($brokenLinks, $dryRun);
    } else {
        echo "<p style='color: green;'>Битые ссылки не найдены!</p>";
    }
    
    echo "<hr>";
}

// Общая статистика
echo "<h2>Общая статистика</h2>";
$totalOrphanedRemoved = isset($orphanResult) ? $orphanResult['removed'] : 0;
$totalBrokenRemoved = isset($brokenResult) ? $brokenResult['removed'] : 0;
$totalSpaceSaved = isset($orphanResult) ? $orphanResult['saved_space'] : 0;

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Тип проблемы</th><th>" . ($dryRun ? "Найдено" : "Удалено") . "</th><th>Места " . ($dryRun ? "можно освободить" : "освобождено") . "</th></tr>";
echo "<tr><td>Файлы-сироты (на диске, нет в БД)</td><td>{$totalOrphanedRemoved}</td><td>" . formatBytes($totalSpaceSaved) . "</td></tr>";
echo "<tr><td>Битые ссылки (в БД, нет на диске)</td><td>{$totalBrokenRemoved}</td><td>-</td></tr>";
echo "<tr><td><strong>ИТОГО</strong></td><td><strong>" . ($totalOrphanedRemoved + $totalBrokenRemoved) . "</strong></td><td><strong>" . formatBytes($totalSpaceSaved) . "</strong></td></tr>";
echo "</table>";

if ($dryRun && ($totalOrphanedRemoved > 0 || $totalBrokenRemoved > 0)) {
    echo "<br><p style='color: red;'><strong>Внимание!</strong> Для реального удаления запустите:</p>";
    echo "<ul>";
    echo "<li><a href='?mode=remove&action=orphaned' onclick='return confirm(\"Удалить файлы-сироты?\")'>Удалить только файлы-сироты</a></li>";
    echo "<li><a href='?mode=remove&action=broken' onclick='return confirm(\"Удалить битые ссылки?\")'>Удалить только битые ссылки</a></li>";
    echo "<li><a href='?mode=remove&action=all' onclick='return confirm(\"Удалить всё?\")'>Удалить всё</a></li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p>Анализ завершен: " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Полезные ссылки:</strong></p>";
echo "<ul>";
echo "<li><a href='?action=all'>Полный анализ</a></li>";
echo "<li><a href='?action=orphaned'>Только файлы-сироты</a></li>";
echo "<li><a href='?action=broken'>Только битые ссылки</a></li>";
echo "<li><a href='dubli.php'>Вернуться к анализу дублей</a></li>";
echo "</ul>";
?>
