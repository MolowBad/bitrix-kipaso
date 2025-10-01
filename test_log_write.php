<?php
/**
 * Проверка возможности записи в лог
 */

// Сначала загружаем Bitrix
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$logPath = $_SERVER['DOCUMENT_ROOT'].'/upload/cdek_parser.log';
$uploadDir = $_SERVER['DOCUMENT_ROOT'].'/upload';

echo "<h1>Проверка записи в лог</h1>";

// Проверка существования папки
echo "<h3>1. Проверка папки /upload/</h3>";
if (is_dir($uploadDir)) {
    echo "✓ Папка существует: $uploadDir<br>";
    echo "Права: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "<br>";
    echo "Можно записывать: " . (is_writable($uploadDir) ? "✓ ДА" : "✗ НЕТ") . "<br>";
} else {
    echo "✗ Папка НЕ существует: $uploadDir<br>";
    echo "Попытка создать...<br>";
    if (mkdir($uploadDir, 0775, true)) {
        echo "✓ Папка создана<br>";
    } else {
        echo "✗ Не удалось создать папку<br>";
    }
}

// Тест записи через file_put_contents
echo "<h3>2. Тест записи через file_put_contents</h3>";
$testContent = date('Y-m-d H:i:s') . " - Тестовая запись\n";
if (file_put_contents($logPath, $testContent, FILE_APPEND)) {
    echo "✓ Запись успешна (file_put_contents)<br>";
} else {
    echo "✗ Ошибка записи (file_put_contents)<br>";
}

// Тест записи через Bitrix Debug
echo "<h3>3. Тест записи через Bitrix Debug</h3>";

\Bitrix\Main\Diag\Debug::writeToFile([
    'TEST' => 'Direct write test',
    'TIMESTAMP' => date('Y-m-d H:i:s'),
], 'TEST_WRITE', $logPath);

if (file_exists($logPath)) {
    echo "✓ Файл создан/обновлён<br>";
    echo "Размер: " . filesize($logPath) . " байт<br>";
    echo "Последние 10 строк:<br>";
    echo "<pre style='background:#f5f5f5; padding:10px; max-height:300px; overflow:auto;'>";
    $lines = file($logPath);
    $lastLines = array_slice($lines, -10);
    echo htmlspecialchars(implode('', $lastLines));
    echo "</pre>";
} else {
    echo "✗ Файл не создан<br>";
}

// Тест вызова функции парсера напрямую
echo "<h3>4. Тест вызова обработчика напрямую</h3>";

if (function_exists('parseCdekAddress')) {
    echo "✓ Функция parseCdekAddress существует<br>";
    
    $testAddr = "Воронеж, ул. Ростовская, 58/20 #SVRN31";
    $result = parseCdekAddress($testAddr);
    
    echo "Тестовый адрес: $testAddr<br>";
    echo "Результат парсинга:<br>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
} else {
    echo "✗ Функция parseCdekAddress НЕ найдена<br>";
}

// Проверка регистрации событий
echo "<h3>5. Проверка зарегистрированных событий</h3>";

$eventManager = \Bitrix\Main\EventManager::getInstance();
$handlers = $eventManager->findEventHandlers('sale', 'OnSaleComponentOrderOneStepProcess');

echo "Обработчики OnSaleComponentOrderOneStepProcess:<br>";
if ($handlers) {
    echo "<pre>";
    foreach ($handlers as $handler) {
        print_r($handler);
    }
    echo "</pre>";
} else {
    echo "Не найдено<br>";
}

$handlers2 = $eventManager->findEventHandlers('sale', 'OnSaleOrderSaved');
echo "<br>Обработчики OnSaleOrderSaved:<br>";
if ($handlers2) {
    echo "<pre>";
    foreach ($handlers2 as $handler) {
        print_r($handler);
    }
    echo "</pre>";
} else {
    echo "Не найдено<br>";
}

echo "<hr>";
echo "<p><strong>После проверки удалите этот файл: /test_log_write.php</strong></p>";
?>
