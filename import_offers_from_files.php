<?php
/**
 * Скрипт импорта торговых предложений из файлов:
 *  - Цены: catalogOven.xml (корень сайта)
 *  - Описания: "Краткое описание для сайта 12,08.csv" (UTF-8, разделитель запятая или точка с запятой)
 *
 * Запуск:
 *  - Dry-run (только лог без изменений): /import_offers_from_files.php?dry-run=Y
 *  - Боевой запуск: /import_offers_from_files.php
 *
 * Предполагается, что:
 *  - Товарный ИБ: ID = 16
 *  - Поиск товара по свойству артикула CML2_ARTICLE
 *  - Связка SKU-ИБ уже существует. Если нет — скрипт завершится с подсказкой.
 *
 * ВАЖНО: В Битрикс нельзя назначить ID элемента при создании. В качестве идентификатора
 * предложения используем XML_ID и CODE = <izd_code>.
 */

use Bitrix\Main\Loader;

@set_time_limit(0);
@ini_set('display_errors', 1);

// Подготовка Bitrix окружения
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '/');
$prolog = $docRoot . '/bitrix/modules/main/include/prolog_before.php';
if (!file_exists($prolog)) {
    http_response_code(500);
    echo "Bitrix prolog not found: {$prolog}";
    exit;
}
require_once $prolog;

if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
    http_response_code(500);
    echo "Modules 'iblock' and 'catalog' are required";
    exit;
}

// ------------------------ Конфигурация ------------------------
$PRODUCT_IBLOCK_ID = 16;               // ИБ товаров
$ARTICLE_PROPERTY_CODE = 'CML2_ARTICLE';
$XLSX_BASENAME = 'Краткое описание для сайта 12,08';
$CSV_PATH = $docRoot . '/' . $XLSX_BASENAME . '.csv';
$XLSX_PATH = $docRoot . '/' . $XLSX_BASENAME . '.xlsx';
$XML_PATH = $docRoot . '/catalogOven.xml';
$DRY_RUN = (isset($_GET['dry-run']) && strtoupper($_GET['dry-run']) === 'Y');
$LOG = [];

function logm($msg) {
    global $LOG;
    $LOG[] = $msg;
}

function flushLogAndExit($code = 200) {
    global $LOG;
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    echo implode("\n", $LOG);
    exit;
}

// ------------------------ Проверка связки SKU ------------------------
$skuInfo = CCatalogSKU::GetInfoByProductIBlock($PRODUCT_IBLOCK_ID);
if (empty($skuInfo) || empty($skuInfo['IBLOCK_ID']) || empty($skuInfo['SKU_PROPERTY_ID'])) {
    logm("[ERR] Не найдена связка SKU для ИБ товаров {$PRODUCT_IBLOCK_ID}. " .
         "Создайте/свяжите ИБ торговых предложений через CCatalogSKU и повторите запуск.");
    flushLogAndExit(500);
}
$OFFERS_IBLOCK_ID = (int)$skuInfo['IBLOCK_ID'];
$LINK_PROP_ID = (int)$skuInfo['SKU_PROPERTY_ID'];
logm("[OK] Найден ИБ предложений: {$OFFERS_IBLOCK_ID}, свойство связи: {$LINK_PROP_ID}");

// ------------------------ Чтение XML цен ------------------------
if (!file_exists($XML_PATH)) {
    logm("[ERR] Не найден файл XML цен: {$XML_PATH}");
    flushLogAndExit(500);
}
$xml = @simplexml_load_file($XML_PATH);
if (!$xml) {
    logm("[ERR] Ошибка чтения XML: {$XML_PATH}");
    flushLogAndExit(500);
}

// Ожидаем структуру ... <price><name>...</name><price>...</price><izd_code>30289</izd_code></price>
$xmlPriceByIzd = [];
$xmlArticleByIzd = [];
foreach ($xml->xpath('//price') as $priceNode) {
    $izd = trim((string)$priceNode->izd_code);
    $price = trim((string)$priceNode->price);
    // артикул товара находится в родительском узле <product><id>
    $idVal = '';
    $parentIds = $priceNode->xpath('ancestor::product/id');
    if (is_array($parentIds) && isset($parentIds[0])) {
        $idVal = trim((string)$parentIds[0]);
    }
    if ($izd !== '') {
        if ($price !== '') {
            $xmlPriceByIzd[$izd] = (float)str_replace([' ', ','], ['', '.'], $price);
        }
        if ($idVal !== '') {
            $xmlArticleByIzd[$izd] = $idVal;
        }
    }
}
logm('[OK] Загружено цен из XML: ' . count($xmlPriceByIzd));
logm('[OK] Загружено соответствий izd_code→id из XML: ' . count($xmlArticleByIzd));

// ------------------------ Чтение данных из CSV/XLSX ------------------------
$rows = [];
if (file_exists($CSV_PATH)) {
    $h = fopen($CSV_PATH, 'r');
    if (!$h) {
        logm("[ERR] Не удалось открыть CSV: {$CSV_PATH}");
        flushLogAndExit(500);
    }
    // Попробуем автодетектировать разделитель
    $firstLine = fgets($h);
    rewind($h);
    $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

    $lineNo = 0;
    while (($data = fgetcsv($h, 0, $delimiter)) !== false) {
        $lineNo++;
        // Индексация столбцов: 1..N по заданию
        // 2: izd_code, 3: article, 18: name
        $izd = isset($data[1]) ? trim($data[1]) : '';
        $article = isset($data[2]) ? trim($data[2]) : '';
        $name = isset($data[17]) ? trim($data[17]) : '';
        if ($lineNo === 1) {
            // Возможная шапка — пропускаем, если данные нечисловые в столбце 2
            if ($izd === 'izd_code' || $izd === 'ИД' || !preg_match('/^\d+$/', $izd)) {
                logm('[INFO] Пропущена строка-шапка CSV');
                continue;
            }
        }
        if ($izd === '' || $article === '' || $name === '') {
            continue;
        }
        $rows[] = [
            'izd_code' => $izd,
            'article'  => $article,
            'name'     => $name,
        ];
    }
    fclose($h);
    logm('[OK] Загружено строк из CSV: ' . count($rows));
} elseif (file_exists($XLSX_PATH)) {
    $xlsxRows = readXlsxRows($XLSX_PATH);
    $lineNo = 0;
    foreach ($xlsxRows as $row) {
        $lineNo++;
        // 1-based индексация
        $izd = isset($row[2]) ? trim($row[2]) : '';
        $article = isset($row[3]) ? trim($row[3]) : '';
        $name = isset($row[18]) ? trim($row[18]) : '';
        if ($lineNo === 1) {
            if ($izd === 'izd_code' || $izd === 'ИД' || !preg_match('/^\d+$/', (string)$izd)) {
                logm('[INFO] Пропущена строка-шапка XLSX');
                continue;
            }
        }
        if ($izd === '' || $article === '' || $name === '') { continue; }
        $rows[] = [
            'izd_code' => (string)$izd,
            'article'  => (string)$article,
            'name'     => (string)$name,
        ];
    }
    logm('[OK] Загружено строк из XLSX: ' . count($rows));
} else {
    logm("[ERR] Не найдено ни CSV, ни XLSX: {$CSV_PATH} / {$XLSX_PATH}");
    flushLogAndExit(500);
}

if (empty($rows)) {
    logm('[ERR] В исходных данных нет валидных строк для обработки.');
    flushLogAndExit(500);
}

// ------------------------ Индексация и группировка ------------------------
// Разрешим артикул по izd_code через XML (<id>), если доступен. Иначе берём из Excel.
$resolved = [];
foreach ($rows as $r) {
    $resolvedArticle = $xmlArticleByIzd[$r['izd_code']] ?? $r['article'];
    if ($resolvedArticle === '' || $resolvedArticle === null) { continue; }
    // фикс для возможных пробелов/регистра
    $resolvedArticle = trim((string)$resolvedArticle);
    $r['article_resolved'] = $resolvedArticle;
    $resolved[] = $r;
}

if (empty($resolved)) {
    logm('[ERR] Не удалось определить артикулы по данным XML/Excel.');
    flushLogAndExit(500);
}

// Группа: артикул → набор предложений
$byArticle = [];
foreach ($resolved as $r) {
    $byArticle[$r['article_resolved']][] = $r;
}
logm('[OK] Найдено товаров по артикулам (с учётом XML): ' . count($byArticle));

// Базовый тип цены
$basePriceGroup = CCatalogGroup::GetBaseGroup();
$basePriceTypeId = $basePriceGroup ? (int)$basePriceGroup['ID'] : 1;
logm('[OK] Используется базовый тип цены ID=' . $basePriceTypeId);

// ------------------------ Обработка по товарам ------------------------
$created = 0; $updated = 0; $skipped = 0; $noPrice = 0; $noProduct = 0;

foreach ($byArticle as $article => $offers) {
    // Ищем товар по свойству артикула
    $productId = null;
    $res = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        [
            'IBLOCK_ID' => $PRODUCT_IBLOCK_ID,
            'ACTIVE' => 'Y',
            'PROPERTY_' . $ARTICLE_PROPERTY_CODE => $article,
        ],
        false,
        ['nTopCount' => 1],
        ['ID', 'NAME']
    );
    if ($item = $res->GetNext()) {
        $productId = (int)$item['ID'];
    }

    if (!$productId) {
        $noProduct += count($offers);
        logm("[WARN] Не найден товар по артикулу '{$article}', пропущено предложений: " . count($offers));
        continue;
    }

    // Обработаем каждое предложение из CSV для данного артикула
    foreach ($offers as $offerRow) {
        $izd = $offerRow['izd_code'];
        $name = $offerRow['name'];
        $priceVal = $xmlPriceByIzd[$izd] ?? null;
        if ($priceVal === null) {
            $noPrice++;
            logm("[WARN] Не найдена цена в XML для izd_code={$izd}, артикул={$article}. Пропуск.");
            continue;
        }

        // Проверяем существование ТП по XML_ID или CODE = izd_code
        $existingOfferId = null;
        $resOffer = CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $OFFERS_IBLOCK_ID,
                'XML_ID' => $izd,
            ],
            false,
            ['nTopCount' => 1],
            ['ID']
        );
        if ($o = $resOffer->GetNext()) {
            $existingOfferId = (int)$o['ID'];
        } else {
            $resOffer2 = CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => $OFFERS_IBLOCK_ID,
                    '=CODE' => $izd,
                ],
                false,
                ['nTopCount' => 1],
                ['ID']
            );
            if ($o2 = $resOffer2->GetNext()) {
                $existingOfferId = (int)$o2['ID'];
            }
        }

        if ($existingOfferId) {
            // Обновим имя и связь (на всякий), цену
            $el = new CIBlockElement();
            $updateFields = [
                'NAME' => $name,
                'CODE' => $izd,
                'XML_ID' => $izd,
            ];
            if (!$DRY_RUN) {
                $el->Update($existingOfferId, $updateFields, false, false, true);
                CIBlockElement::SetPropertyValuesEx($existingOfferId, $OFFERS_IBLOCK_ID, [
                    $LINK_PROP_ID => $productId,
                ]);
                upsertPrice($existingOfferId, $priceVal, $basePriceTypeId);
            }
            $updated++;
            logm("[UPD] ТП {$existingOfferId} (izd={$izd}) обновлено. Товар ID={$productId}, цена={$priceVal}");
        } else {
            // Создаём новое ТП
            $el = new CIBlockElement();
            $fields = [
                'IBLOCK_ID' => $OFFERS_IBLOCK_ID,
                'ACTIVE' => 'Y',
                'NAME' => $name,
                'CODE' => $izd,      // не уникально глобально, но обычно достаточно
                'XML_ID' => $izd,    // используем как внешний ID
                'PROPERTY_VALUES' => [
                    $LINK_PROP_ID => $productId,
                ],
            ];
            $newId = 0;
            if (!$DRY_RUN) {
                $newId = (int)$el->Add($fields);
                if ($newId <= 0) {
                    logm('[ERR] Не удалось создать ТП для izd=' . $izd . ': ' . $el->LAST_ERROR);
                    $skipped++;
                    continue;
                }
                // Обеспечим каталожную запись
                ensureCatalogProduct($newId);
                upsertPrice($newId, $priceVal, $basePriceTypeId);
            }
            $created++;
            logm("[ADD] Создано ТП ID={$newId} (izd={$izd}). Товар ID={$productId}, цена={$priceVal}");
        }
    }
}

logm('--- Итоги ---');
logm("Создано: {$created}");
logm("Обновлено: {$updated}");
logm("Пропущено без цены: {$noPrice}");
logm("Не найден товар по артикулу: {$noProduct}");
logm("Прочитано строк CSV: " . count($rows) . ", цен XML: " . count($xmlPriceByIzd));
logm($DRY_RUN ? '[DRY-RUN] Изменения не сохранялись' : '[APPLY] Изменения применены');

flushLogAndExit(200);

// ------------------------ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ------------------------
function readXlsxRows(string $path): array {
    $rows = [];
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        logm('[ERR] Не удалось открыть XLSX: ' . $path);
        return $rows;
    }

    // Прочитаем sharedStrings если есть
    $sharedStrings = [];
    $ssIndex = $zip->locateName('xl/sharedStrings.xml');
    if ($ssIndex !== false) {
        $xml = simplexml_load_string($zip->getFromIndex($ssIndex));
        if ($xml) {
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string)$si->t;
                } else {
                    // si может содержать несколько t внутри r
                    $acc = '';
                    foreach ($si->r as $r) {
                        $acc .= (string)$r->t;
                    }
                    $sharedStrings[] = $acc;
                }
            }
        }
    }

    // Определим первый лист
    $sheetPath = 'xl/worksheets/sheet1.xml';
    if ($zip->locateName($sheetPath) === false) {
        // Попробуем найти любой sheetX.xml
        for ($i = 1; $i <= 10; $i++) {
            $try = 'xl/worksheets/sheet' . $i . '.xml';
            if ($zip->locateName($try) !== false) { $sheetPath = $try; break; }
        }
    }
    $sheetXmlStr = $zip->getFromName($sheetPath);
    if ($sheetXmlStr === false) {
        logm('[ERR] Не найден лист в XLSX (sheet1.xml)');
        $zip->close();
        return $rows;
    }
    $sheet = simplexml_load_string($sheetXmlStr);
    if (!$sheet) {
        logm('[ERR] Ошибка парсинга sheet XML');
        $zip->close();
        return $rows;
    }

    // Разберём строки/ячейки
    $maxCol = 0;
    foreach ($sheet->sheetData->row as $rowNode) {
        $row = [];
        foreach ($rowNode->c as $c) {
            $r = (string)$c['r']; // адрес, например A1
            $colLetters = preg_replace('/\d+/', '', $r);
            $colIndex = excelColToIndex($colLetters); // 1-based
            if ($colIndex > $maxCol) $maxCol = $colIndex;

            $t = (string)$c['t'];
            $val = '';
            if ($t === 's') { // shared string
                $idx = (int)$c->v;
                $val = $sharedStrings[$idx] ?? '';
            } elseif ($t === 'inlineStr') {
                $val = isset($c->is->t) ? (string)$c->is->t : '';
            } else {
                $val = isset($c->v) ? (string)$c->v : '';
            }
            $row[$colIndex] = $val;
        }
        if (!empty($row)) {
            // Заполним пропуски до максимального столбца (чтобы индексы были 1..N)
            for ($i = 1; $i <= $maxCol; $i++) {
                if (!array_key_exists($i, $row)) $row[$i] = '';
            }
            ksort($row);
            $rows[] = $row;
        }
    }
    $zip->close();
    return $rows;
}

function excelColToIndex(string $letters): int {
    $letters = strtoupper($letters);
    $num = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $num = $num * 26 + (ord($letters[$i]) - ord('A') + 1);
    }
    return $num;
}

function ensureCatalogProduct(int $offerId): void {
    // Регистрируем предложение как товар каталога (не SKU-связь, а запись в b_catalog_product)
    $exist = CCatalogProduct::GetByID($offerId);
    if (!$exist) {
        CCatalogProduct::Add([
            'ID' => $offerId,
        ]);
    }
}

function upsertPrice(int $productId, float $price, int $priceTypeId): void {
    $currency = 'RUB';
    // Ищем существующую цену
    $res = CPrice::GetList(
        [],
        [
            'PRODUCT_ID' => $productId,
            'CATALOG_GROUP_ID' => $priceTypeId,
        ]
    );
    if ($ar = $res->Fetch()) {
        CPrice::Update($ar['ID'], [
            'PRICE' => $price,
            'CURRENCY' => $currency,
        ]);
    } else {
        CPrice::Add([
            'PRODUCT_ID' => $productId,
            'CATALOG_GROUP_ID' => $priceTypeId,
            'PRICE' => $price,
            'CURRENCY' => $currency,
        ]);
    }
}
