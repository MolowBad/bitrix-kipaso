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

// Нормализация имени модификации: трим пробелов, унификация дефисов, срезание хвостовых точек/знаков
function normalizeName(string $s): string {
    // Трим пробелов (включая неразрывные) по краям
    $s = preg_replace('/^[\p{Z}\s\x{00A0}\x{202F}]+|[\p{Z}\s\x{00A0}\x{202F}]+$/u', '', $s);
    // Заменяем различные типы тире/дефисов на обычный '-'
    $s = strtr($s, [
        "\xE2\x80\x90" => '-', // hyphen
        "\xE2\x80\x91" => '-', // non-breaking hyphen
        "\xE2\x80\x92" => '-', // figure dash
        "\xE2\x80\x93" => '-', // en dash
        "\xE2\x80\x94" => '-', // em dash
        "\xE2\x80\x95" => '-', // horizontal bar
    ]);
    // Сжимаем множественные пробелы до одного
    $s = preg_replace('/[\x{00A0}\x{202F}\s]+/u', ' ', $s);
    // Удаляем хвостовые пробелы и знаки препинания (точки, запятые, двоеточия, точка с запятой)
    $s = preg_replace('/[\s\x{00A0}\x{202F}\.,;:]+$/u', '', $s);
    return $s;
}

// Применяем нормализацию к входящему названию
$modificationNameNormalized = normalizeName((string)$modificationName);

// Результат поиска
$result = [
    'success' => false,
    'price' => 0,
    'modification_name' => $modificationNameNormalized,
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
            
            // Проверяем название модификации (поддержка тегов <name> и <n>) с учётом нормализации
            $xmlName1 = normalizeName((string)$priceXml->name);
            $xmlName2 = normalizeName((string)$priceXml->n);
            if ($xmlName1 === $modificationNameNormalized || $xmlName2 === $modificationNameNormalized) {
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
