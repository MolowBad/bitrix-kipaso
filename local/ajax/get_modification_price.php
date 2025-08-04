<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

header('Content-Type: application/json');

// Получаем параметры запроса
$productId = $_REQUEST['product_id'] ?? '';
$modificationName = $_REQUEST['modification_name'] ?? '';

// Проверяем наличие параметров
if (empty($productId) || empty($modificationName)) {
    echo json_encode([
        'success' => false,
        'error' => 'Не указан ID товара или название модификации'
    ]);
    exit;
}

// Путь к XML файлу
$xmlFilePath = $_SERVER["DOCUMENT_ROOT"] . "/catalogOven.xml";

// Проверяем существование файла
if (!file_exists($xmlFilePath)) {
    echo json_encode([
        'success' => false,
        'error' => 'XML файл не найден'
    ]);
    exit;
}

// Результат поиска
$result = [
    'success' => false,
    'price' => 0,
    'modification_name' => $modificationName,
    'product_id' => $productId
];

// Пытаемся найти товар и его цену (использование SimpleXML)
try {
    // Создаем объект для чтения XML по частям (для больших файлов)
    $reader = new XMLReader();
    $reader->open($xmlFilePath);

    // Флаг для определения, нашли ли мы нужный товар
    $foundProduct = false;
    $inPricesSection = false;

    // Перебираем XML
    while ($reader->read()) {
        // Если нашли начало элемента id, проверяем его значение
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'id' && !$foundProduct) {
            $id = $reader->readString();
            if ($id == $productId) {
                $foundProduct = true;
            }
        }

        // Если нашли товар и входим в секцию prices
        if ($foundProduct && $reader->nodeType == XMLReader::ELEMENT && $reader->name == 'prices') {
            $inPricesSection = true;
        }

        // Если нашли товар и мы в секции prices, ищем нужную модификацию
        if ($foundProduct && $inPricesSection && $reader->nodeType == XMLReader::ELEMENT && $reader->name == 'price') {
            // Извлекаем элемент price как SimpleXML для удобства работы
            $priceXml = simplexml_load_string($reader->readOuterXml());
            
            // Проверяем название модификации (поддержка тегов <name> и <n>)
            if ((string)$priceXml->name == $modificationName || (string)$priceXml->n == $modificationName) {
                $result['success'] = true;
                $result['price'] = (float)$priceXml->price;
                $result['izd_code'] = (string)$priceXml->izd_code;
                break; // Нашли нужную цену, выходим из цикла
            }
        }

        // Если вышли из секции prices, значит нужной модификации нет
        if ($foundProduct && $inPricesSection && $reader->nodeType == XMLReader::END_ELEMENT && $reader->name == 'prices') {
            break;
        }
    }

    $reader->close();
} catch (Exception $e) {
    $result['error'] = 'Ошибка при обработке XML: ' . $e->getMessage();
}

// Возвращаем результат
echo json_encode($result);
