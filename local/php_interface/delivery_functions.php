<?php

use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;

class ProductDeliveryManager
{
    const OWEN_DELIVERY_DAYS = 4;
    const EXCEL_FILE_PATH = '/1c-exchange/price_dealer.xlsx';

    private static $deliveryCache = [];

    public static function getProductDeliveryInfo($productId, $requestedQty = 1)
    {
        if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
            return self::getDefaultDeliveryInfo();
        }

        $article = self::getProductArticle($productId);
        if (!$article) {
            AddMessage2Log("Не найден артикул для товара ID: $productId", "delivery_manager");
            return self::getDefaultDeliveryInfo();
        }

        $stockQty = self::getStockQuantity($productId);
        $owenData = self::getOwenDeliveryData($article);

        // Детальное логирование
        AddMessage2Log("ДОСТАВКА - Товар ID: $productId, Артикул: $article, Остаток: $stockQty, Данные Owen: " . print_r($owenData, true), "delivery_detailed");

        return self::calculateDeliveryInfo($stockQty, $requestedQty, $owenData);
    }


    private static function getProductArticle($productId)
    {
        if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
            return null;
        }

        // Получаем информацию о SKU для этого товара
        $skuInfo = CCatalogSKU::getInfoByProductIBlock($productId);
        if (!$skuInfo) {
            AddMessage2Log("❌ Не найдена информация о SKU для товара ID: {$productId}", "delivery_manager");
            return null;
        }

        $offersIblockId = $skuInfo['IBLOCK_ID'];
        $linkPropertyId = $skuInfo['SKU_PROPERTY_ID'];

        // Ищем торговые предложения для этого товара
        $res = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $offersIblockId,
                'PROPERTY_' . $linkPropertyId => $productId,
                'ACTIVE' => 'Y'
            ],
            false,
            false,
            ['ID', 'NAME', 'CODE', 'XML_ID']
        );

        // Берем первое торговое предложение (или можно перебрать все)
        if ($offer = $res->Fetch()) {
            $offerId = $offer['ID'];

            AddMessage2Log("📦 Найдено торговое предложение ID: {$offerId} для товара ID: {$productId}", "delivery_manager");

            // 1. Ищем свойство IZD в торговом предложении
            $propertyRes = \CIBlockElement::GetProperty(
                $offersIblockId,
                $offerId,
                ["sort" => "asc"],
                ["CODE" => "IZD"]
            );

            while ($prop = $propertyRes->Fetch()) {
                $value = trim($prop['VALUE']);
                if (!empty($value) && is_numeric($value)) {
                    AddMessage2Log("📦 Найден IZD артикул: {$value} в торговом предложении ID: {$offerId}", "delivery_manager");
                    return $value;
                }
            }

            // 2. Ищем в других свойствах торгового предложения
            $otherProperties = ['ARTICLE', 'CML2_ARTICLE', 'ARTIKUL', 'ARTICUL', 'VENDOR_CODE'];

            foreach ($otherProperties as $code) {
                $propertyRes = \CIBlockElement::GetProperty(
                    $offersIblockId,
                    $offerId,
                    ["sort" => "asc"],
                    ["CODE" => $code]
                );

                if ($prop = $propertyRes->Fetch()) {
                    $value = trim($prop['VALUE']);
                    if (!empty($value) && is_numeric($value)) {
                        AddMessage2Log("📦 Найден артикул из свойства {$code}: {$value} в торговом предложении ID: {$offerId}", "delivery_manager");
                        return $value;
                    }
                }
            }

            // 3. Используем CODE или XML_ID торгового предложения
            if (!empty($offer['CODE']) && is_numeric($offer['CODE'])) {
                AddMessage2Log("📦 Используем CODE торгового предложения как артикул: {$offer['CODE']}", "delivery_manager");
                return $offer['CODE'];
            }

            if (!empty($offer['XML_ID']) && is_numeric($offer['XML_ID'])) {
                AddMessage2Log("📦 Используем XML_ID торгового предложения как артикул: {$offer['XML_ID']}", "delivery_manager");
                return $offer['XML_ID'];
            }
        }

        // 4. Запасной вариант - ищем в основном товаре
        $element = ElementTable::getRow([
            'select' => ['ID', 'CODE', 'XML_ID'],
            'filter' => ['=ID' => $productId],
            'cache' => ['ttl' => 3600]
        ]);

        if (!empty($element['XML_ID']) && is_numeric($element['XML_ID'])) {
            AddMessage2Log("📦 Используем XML_ID основного товара как артикул: {$element['XML_ID']}", "delivery_manager");
            return $element['XML_ID'];
        }

        if (!empty($element['CODE'])) {
            AddMessage2Log("📦 Используем CODE основного товара как артикул: {$element['CODE']}", "delivery_manager");
            return $element['CODE'];
        }

        AddMessage2Log("❌ Не удалось найти артикул для товара ID: {$productId}", "delivery_manager");
        return null;
    }


    private static function getProductIblockId($productId)
    {
        $element = ElementTable::getRow([
            'select' => ['IBLOCK_ID'],
            'filter' => ['=ID' => $productId],
            'cache' => ['ttl' => 3600]
        ]);

        return $element ? $element['IBLOCK_ID'] : null;
    }


    private static function getStockQuantity($productId)
    {
        $product = \Bitrix\Catalog\ProductTable::getRow([
            'select' => ['QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO'],
            'filter' => ['=ID' => $productId],
            'cache' => ['ttl' => 300]
        ]);

        // Логируем настройки остатков
        if ($product) {
            AddMessage2Log("ОСТАТКИ - Товар ID: {$productId}, Можно купить при нуле: " . ($product['CAN_BUY_ZERO'] ?? 'N') .
                ", Следить за количеством: " . ($product['QUANTITY_TRACE'] ?? 'N') .
                ", Количество: " . ($product['QUANTITY'] ?? 0), "stock_debug");
        }

        return $product ? (float)$product['QUANTITY'] : 0;
    }

    private static function getOwenDeliveryData($article)
    {
        if (empty(self::$deliveryCache)) {
            self::$deliveryCache = self::loadOwenExcelData();
        }

        $result = self::$deliveryCache[$article] ?? null;

        // Логируем поиск артикула
        if ($result) {
            AddMessage2Log("EXCEL - Найден артикул: $article, Данные: " . print_r($result, true), "excel_search");
        } else {
            AddMessage2Log("EXCEL - Артикул не найден: $article", "excel_search");
        }

        return $result;
    }

    private static function loadOwenExcelData()
    {
        $data = [];
        $excelFile = $_SERVER['DOCUMENT_ROOT'] . self::EXCEL_FILE_PATH;

        if (!file_exists($excelFile)) {
            AddMessage2Log("❌ Excel файл не найден: " . $excelFile, "delivery_manager");
            return $data;
        }

        $data = self::parseExcelProperly($excelFile);

        AddMessage2Log("📊 Загружено записей из Excel: " . count($data), "delivery_manager");

        // Логируем несколько примеров
        $examples = array_slice($data, 0, 3, true);
        foreach ($examples as $article => $info) {
            AddMessage2Log("📝 Пример - Артикул: $article, Данные: " . print_r($info, true), "excel_examples");
        }

        return $data;
    }

    private static function parseExcelProperly($excelFile)
    {
        $data = [];

        $zip = new ZipArchive();
        if ($zip->open($excelFile) !== TRUE) {
            AddMessage2Log("❌ Не удалось открыть Excel как ZIP", "excel_parse");
            return $data;
        }

        // Читаем shared strings
        $sharedStrings = [];
        if (($sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $xml = simplexml_load_string($sharedStringsXML);
            if ($xml) {
                foreach ($xml->si as $si) {
                    $sharedStrings[] = (string)$si->t;
                }
            }
            AddMessage2Log("📖 Загружено shared strings: " . count($sharedStrings), "excel_parse");
        }

        // Читаем данные листа
        if (($sheetXML = $zip->getFromName('xl/worksheets/sheet1.xml')) !== false) {
            $sheet = simplexml_load_string($sheetXML);
            if ($sheet && isset($sheet->sheetData)) {
                $rowNum = 0;

                foreach ($sheet->sheetData->row as $row) {
                    $rowNum++;
                    if ($rowNum <= 4) continue; // Пропускаем заголовки

                    $rowData = [];
                    $colIndex = 0;

                    foreach ($row->c as $cell) {
                        $cellAttributes = $cell->attributes();
                        $cellType = (string)$cellAttributes['t'];
                        $value = '';

                        if (isset($cell->v)) {
                            if ($cellType === 's') {
                                // Shared string
                                $index = (int)$cell->v;
                                $value = $sharedStrings[$index] ?? '';
                            } else {
                                $value = (string)$cell->v;
                            }
                        }

                        $rowData[$colIndex++] = $value;
                    }

                    // B-артикул(1), J-срок(9), H-по запросу(7), N-статус(13)
                    if (!empty($rowData[1]) && is_numeric($rowData[1])) {
                        $article = (string)$rowData[1];
                        $deliveryTime = $rowData[9] ?? '';
                        $onRequest = isset($rowData[7]) && trim($rowData[7]) === 'По запросу';

                        $data[$article] = [
                            'delivery_time' => is_numeric($deliveryTime) ? (int)$deliveryTime : null,
                            'on_request' => $onRequest,
                            'status' => $rowData[13] ?? ''
                        ];

                        // Логируем строку с "По запросу"
                        if ($onRequest) {
                            AddMessage2Log("🚨 Строка с 'По запросу' - Артикул: $article, Строка: " . print_r($rowData, true), "excel_on_request");
                        }
                    }
                }
            }
        } else {
            AddMessage2Log("❌ Не найден sheet1.xml в Excel", "excel_parse");
        }

        $zip->close();
        return $data;
    }

    private static function calculateDeliveryInfo($stockQty, $requestedQty, $owenData)
    {
        $availableQty = min($stockQty, $requestedQty);
        $deliveryQty = max(0, $requestedQty - $stockQty);

        // Логируем расчет
        AddMessage2Log("РАСЧЕТ - Запрошено: $requestedQty, В наличии: $stockQty, Доступно: $availableQty, Под заказ: $deliveryQty", "delivery_calc");

        // Случай 1: Весь товар в наличии
        if ($availableQty >= $requestedQty) {
            AddMessage2Log("✅ Результат: В НАЛИЧИИ", "delivery_result");
            return [
                'TYPE' => 'in_stock',
                'TEXT' => "В наличии: {$availableQty} шт.",
                'CSS_CLASS' => 'inStock',
                'STOCK_QTY' => $availableQty,
                'DELIVERY_QTY' => 0,
                'DATE' => '',
                'DELIVERY_DAYS' => 0
            ];
        }

        // Случай 2: Часть в наличии, часть под заказ
        if ($availableQty > 0 && $deliveryQty > 0) {
            $deliveryInfo = self::getDeliveryDetails($owenData);
            AddMessage2Log("🔄 Результат: ЧАСТИЧНО В НАЛИЧИИ", "delivery_result");
            return [
                'TYPE' => 'hybrid',
                'TEXT' => "Частично в наличии",
                'CSS_CLASS' => 'hybridStock',
                'STOCK_QTY' => $availableQty,
                'DELIVERY_QTY' => $deliveryQty,
                'DATE' => $deliveryInfo['date'],
                'DELIVERY_DAYS' => $deliveryInfo['days']
            ];
        }

        // Случай 3: Товара нет в наличии, проверяем Owen
        $deliveryInfo = self::getDeliveryDetails($owenData);

        if ($deliveryInfo['on_request']) {
            AddMessage2Log("📞 Результат: УТОЧНЯЙТЕ СРОКИ", "delivery_result");
            return [
                'TYPE' => 'on_request',
                'TEXT' => "Уточняйте сроки",
                'CSS_CLASS' => 'onRequest',
                'STOCK_QTY' => 0,
                'DELIVERY_QTY' => $requestedQty,
                'DATE' => '',
                'DELIVERY_DAYS' => 0
            ];
        }

        if ($deliveryInfo['days'] > 0) {
            AddMessage2Log("⏳ Результат: ПОД ЗАКАЗ {$deliveryInfo['days']} дн.", "delivery_result");
            $deliveryText = "Под заказ: {$deliveryInfo['days']} дн.";
            if (!empty($deliveryInfo['date'])) {
                $deliveryText .= " (≈ {$deliveryInfo['date']})";
            }

            return [
                'TYPE' => 'on_order',
                'TEXT' => $deliveryText,
                'CSS_CLASS' => 'onOrder',
                'STOCK_QTY' => 0,
                'DELIVERY_QTY' => $requestedQty,
                'DATE' => $deliveryInfo['date'],
                'DELIVERY_DAYS' => $deliveryInfo['days']
            ];
        }

        AddMessage2Log("❌ Результат: НЕТ В НАЛИЧИИ", "delivery_result");
        return [
            'TYPE' => 'out_of_stock',
            'TEXT' => "Нет в наличии",
            'CSS_CLASS' => 'outOfStock',
            'STOCK_QTY' => 0,
            'DELIVERY_QTY' => 0,
            'DATE' => '',
            'DELIVERY_DAYS' => 0
        ];
    }

    private static function getDeliveryDetails($owenData)
    {
        if (!$owenData) {
            return ['days' => 0, 'date' => '', 'on_request' => false];
        }

        $onRequest = $owenData['on_request'] ?? false;
        $owenDays = $owenData['delivery_time'] ?? 0;

        AddMessage2Log("📦 Owen данные - По запросу: " . ($onRequest ? 'ДА' : 'НЕТ') . ", Дней: $owenDays", "delivery_details");

        if ($onRequest) {
            return ['days' => 0, 'date' => '', 'on_request' => true];
        }

        $totalDays = $owenDays + self::OWEN_DELIVERY_DAYS;

        if ($totalDays > 0) {
            $deliveryDate = date('d.m.Y', strtotime("+{$totalDays} days"));
            return [
                'days' => $totalDays,
                'date' => $deliveryDate,
                'on_request' => false
            ];
        }

        return ['days' => 0, 'date' => '', 'on_request' => false];
    }

    private static function getDefaultDeliveryInfo()
    {
        return [
            'TYPE' => 'out_of_stock',
            'TEXT' => "Нет в наличии",
            'CSS_CLASS' => 'outOfStock',
            'STOCK_QTY' => 0,
            'DELIVERY_QTY' => 0,
            'DATE' => '',
            'DELIVERY_DAYS' => 0
        ];
    }

    public static function debugExcelData($article = null)
    {
        $excelFile = $_SERVER['DOCUMENT_ROOT'] . self::EXCEL_FILE_PATH;

        if (!file_exists($excelFile)) {
            return "❌ Файл не найден: " . $excelFile;
        }

        $data = self::loadOwenExcelData();
        $fileInfo = "✅ Файл существует: " . $excelFile . "\n";
        $fileInfo .= "📊 Размер: " . filesize($excelFile) . " байт\n";
        $fileInfo .= "📝 Записей загружено: " . count($data) . "\n\n";

        if ($article) {
            $fileInfo .= "🔍 Поиск артикула '$article':\n";
            if (isset($data[$article])) {
                $fileInfo .= "✅ Найден: " . print_r($data[$article], true);
            } else {
                $fileInfo .= "❌ Не найден в Excel";
                // Покажем какие артикулы есть
                $similar = array_keys($data);
                if (!empty($similar)) {
                    $fileInfo .= "\n📋 Ближайшие артикулы: " . implode(', ', array_slice($similar, 0, 5));
                }
            }
        } else {
            $fileInfo .= "📋 Первые 10 записей:\n";
            $counter = 0;
            foreach ($data as $art => $info) {
                if ($counter++ >= 10) break;
                $fileInfo .= "{$art} => " . print_r($info, true) . "\n";
            }
        }

        return $fileInfo;
    }
}

function getProductDeliveryInfo($productId, $quantity = 1)
{
    return ProductDeliveryManager::getProductDeliveryInfo($productId, $quantity);
}
