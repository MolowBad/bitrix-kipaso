<?php
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

/**
 * Поиск существующих ТП по izd_code, с защитой от дублей.
 * Возвращает: [первый_ID_или_null, массив_ID_дубликатов]
 */
function findExistingOffer(int $offersIblockId, int $linkPropId, int $productId, string $izd): array {
    $ids = [];
    // По XML_ID
    $res1 = CIBlockElement::GetList([], [
        'IBLOCK_ID' => $offersIblockId,
        'XML_ID' => $izd,
    ], false, false, ['ID']);
    while ($r = $res1->GetNext()) { $ids[] = (int)$r['ID']; }

    // По CODE (строгое сравнение)
    $res2 = CIBlockElement::GetList([], [
        'IBLOCK_ID' => $offersIblockId,
        '=CODE' => $izd,
    ], false, false, ['ID']);
    while ($r = $res2->GetNext()) { $ids[] = (int)$r['ID']; }

    // По связке с товаром + CODE (на случай разных XML_ID)
    $res3 = CIBlockElement::GetList([], [
        'IBLOCK_ID' => $offersIblockId,
        'PROPERTY_' . $linkPropId => $productId,
        '=CODE' => $izd,
    ], false, false, ['ID']);
    while ($r = $res3->GetNext()) { $ids[] = (int)$r['ID']; }

    $ids = array_values(array_unique($ids));
    if (empty($ids)) { return [null, []]; }
    $first = array_shift($ids);
    return [$first, $ids];
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
$XLSX_BASENAME = '1c-stocks';
$CSV_PATH = $docRoot . '/' . $XLSX_BASENAME . '.csv';
$XLSX_PATH = $docRoot . '/' . $XLSX_BASENAME . '.xlsx';
$XML_PATH = $docRoot . '/catalogOven.xml';
$DRY_RUN = (isset($_GET['dry-run']) && strtoupper($_GET['dry-run']) === 'Y');
$LOG = [];

// ------------------------ Поддержка запуска из CLI ------------------------
if (php_sapi_name() === 'cli') {
    global $argv;
    $cliArgs = $argv ?? [];
    // Удалим имя скрипта
    if (!empty($cliArgs)) { array_shift($cliArgs); }
    foreach ($cliArgs as $arg) {
        if (preg_match('/^--file=(.+)$/i', $arg, $m)) { $_GET['file'] = $m[1]; }
        elseif (preg_match('/^--basename=(.+)$/i', $arg, $m)) { $_GET['basename'] = $m[1]; }
        elseif ($arg === '--dry-run' || $arg === '--dry-run=Y' || $arg === '--dry-run=y') { $_GET['dry-run'] = 'Y'; }
        elseif (preg_match('/^--limit=(\d+)$/i', $arg, $m)) { $_GET['limit'] = $m[1]; }
        elseif (preg_match('/^--offset=(\d+)$/i', $arg, $m)) { $_GET['offset'] = $m[1]; }
    }
    if (!empty($cliArgs)) { logm('[INFO] CLI режим: параметры получены из аргументов командной строки'); }
    // Обновим DRY_RUN после возможной подстановки из CLI
    $DRY_RUN = (isset($_GET['dry-run']) && strtoupper($_GET['dry-run']) === 'Y');
}

// Переопределение пути к исходному файлу через параметры запроса
$USER_FILE = isset($_GET['file']) ? trim((string)$_GET['file']) : '';
$USER_BASENAME = isset($_GET['basename']) ? trim((string)$_GET['basename']) : '';
if ($USER_FILE !== '') {
    // Если путь начинается с '/', считаем относительным к DOCUMENT_ROOT
    $abs = ($USER_FILE[0] === '/') ? ($docRoot . $USER_FILE) : ($docRoot . '/' . $USER_FILE);
    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    if ($ext === 'csv') {
        $CSV_PATH = $abs;
        $XLSX_PATH = '';
    } elseif ($ext === 'xlsx') {
        $XLSX_PATH = $abs;
        $CSV_PATH = '';
    } else {
        logm('[ERR] Неверное расширение файла в параметре file. Ожидается .csv или .xlsx: ' . $USER_FILE);
        flushLogAndExit(400);
    }
    logm('[INFO] Используется файл из параметра file: ' . $USER_FILE);
} elseif ($USER_BASENAME !== '') {
    $XLSX_BASENAME = $USER_BASENAME;
    $CSV_PATH = $docRoot . '/' . $XLSX_BASENAME . '.csv';
    $XLSX_PATH = $docRoot . '/' . $XLSX_BASENAME . '.xlsx';
    logm('[INFO] Используется basename из параметра: ' . $XLSX_BASENAME);
}

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
        // Новая структура:
        // 2: символьный код ТП (используем как izd_code/CODE/XML_ID)
        // 3: краткое наименование модификации
        // 18: полное название модификации
        $izd = isset($data[1]) ? trim($data[1]) : '';
        $shortName = isset($data[2]) ? trim($data[2]) : '';
        $fullName = isset($data[17]) ? trim($data[17]) : '';
        $name = trim($shortName . ($shortName !== '' && $fullName !== '' ? ' ' : '') . $fullName);
        if ($lineNo === 1) {
            // Попытка идентифицировать шапку по ключевым словам
            $headerSample = $izd . ' ' . $shortName . ' ' . $fullName;
            if (preg_match('/символь|код|кратк|полное|назван/iu', $headerSample)) {
                logm('[INFO] Пропущена строка-шапка CSV');
                continue;
            }
        }
        // Пропускаем строку, если пуст любой из требуемых столбцов (2,3,18)
        if ($izd === '' || $shortName === '' || $fullName === '') {
            continue;
        }
        $rows[] = [
            'izd_code' => $izd,
            // Артикул в новом файле отсутствует — будет резолвиться из XML по izd_code
            'article'  => '',
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
        // Новая структура колонок: 2=код ТП, 3=краткое имя, 18=полное имя
        $izd = isset($row[2]) ? trim($row[2]) : '';
        $shortName = isset($row[3]) ? trim($row[3]) : '';
        $fullName = isset($row[18]) ? trim($row[18]) : '';
        $name = trim($shortName . ($shortName !== '' && $fullName !== '' ? ' ' : '') . $fullName);
        if ($lineNo === 1) {
            $headerSample = $izd . ' ' . $shortName . ' ' . $fullName;
            if (preg_match('/символь|код|кратк|полное|назван/iu', $headerSample)) {
                logm('[INFO] Пропущена строка-шапка XLSX');
                continue;
            }
        }
        // Пропускаем строку, если пуст любой из требуемых столбцов (2,3,18)
        if ($izd === '' || $shortName === '' || $fullName === '') { continue; }
        $rows[] = [
            'izd_code' => (string)$izd,
            // Артикул отсутствует в новом файле — резолвится из XML
            'article'  => '',
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
// ------------------------ Ограничение объёма обработки ------------------------
// Пагинация: offset и limit
$OFFSET = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$LIMIT  = isset($_GET['limit'])  ? max(0, (int)$_GET['limit'])  : 0;
if ($OFFSET > 0 || $LIMIT > 0) {
    $before = count($rows);
    if ($OFFSET > 0) { $rows = array_slice($rows, $OFFSET); }
    if ($LIMIT > 0)  { $rows = array_slice($rows, 0, $LIMIT); }
    logm('[INFO] Применена пагинация offset=' . $OFFSET . ', limit=' . $LIMIT . ', строк: ' . $before . ' → ' . count($rows));
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

        // Ищем существующие ТП по XML_ID/CODE и по связке с товаром
        [$existingOfferId, $duplicateIds] = findExistingOffer($OFFERS_IBLOCK_ID, $LINK_PROP_ID, $productId, $izd);
        if (!empty($duplicateIds)) {
            logm('[DUP] Найдено дублирующих ТП для izd=' . $izd . ': IDs=' . implode(',', $duplicateIds) . '. Обновляю первый из них.');
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
