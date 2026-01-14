<?php

use Bitrix\Main\Loader;
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\SectionElementTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Catalog\PriceTable;
use Bitrix\Catalog\GroupTable;

@set_time_limit(0);

$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($docRoot === '') {
    $docRoot = realpath(__DIR__ . '/../../..');
    $_SERVER['DOCUMENT_ROOT'] = $docRoot;
}

$prologPath = $docRoot . '/bitrix/modules/main/include/prolog_before.php';
if (!file_exists($prologPath)) {
    exit("Не найден файл prolog_before.php по пути: {$prologPath}\n");
}
require $prologPath;

if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
    exit("Не удалось подключить модули iblock/catalog\n");
}

$targetIblockId = 16;
$rootSectionId = 259;
$targetPropertyCode = 'XML_ID';
$xmlPath = $docRoot . '/meyertecAPI.xml';

function getBasePriceTypeId(): int
{
    static $baseId = null;
    if ($baseId !== null) {
        return $baseId;
    }

    $row = GroupTable::getList([
        'filter' => [
            '=BASE' => 'Y'
        ],
        'select' => ['ID'],
        'limit' => 1,
    ])->fetch();

    $baseId = $row ? (int)$row['ID'] : 1;

    return $baseId;
}

function upsertBasePrice(int $productId, float $price, string $currency = 'RUB'): bool
{
    $basePriceTypeId = getBasePriceTypeId();

    $existingPrice = PriceTable::getList([
        'filter' => [
            '=PRODUCT_ID' => $productId,
            '=CATALOG_GROUP_ID' => $basePriceTypeId,
        ],
        'select' => ['ID'],
        'limit' => 1,
    ])->fetch();

    if ($existingPrice) {
        $res = PriceTable::update($existingPrice['ID'], [
            'PRICE' => $price,
            'CURRENCY' => $currency,
        ]);
    } else {
        $res = PriceTable::add([
            'PRODUCT_ID' => $productId,
            'CATALOG_GROUP_ID' => $basePriceTypeId,
            'PRICE' => $price,
            'CURRENCY' => $currency,
        ]);
    }

    return $res->isSuccess();
}

function getDescendantSectionIds(int $iblockId, int $rootSectionId): array
{
    $root = SectionTable::getList([
        'filter' => [
            '=IBLOCK_ID' => $iblockId,
            '=ID' => $rootSectionId,
        ],
        'select' => ['LEFT_MARGIN', 'RIGHT_MARGIN'],
        'limit' => 1,
    ])->fetch();

    if (!$root) {
        return [];
    }

    $ids = [];
    $res = SectionTable::getList([
        'filter' => [
            '=IBLOCK_ID' => $iblockId,
            '>LEFT_MARGIN' => (int)$root['LEFT_MARGIN'],
            '<RIGHT_MARGIN' => (int)$root['RIGHT_MARGIN'],
        ],
        'select' => ['ID'],
    ]);

    while ($row = $res->fetch()) {
        $ids[] = (int)$row['ID'];
    }

    return $ids;
}

function getElementIdsBySectionIds(array $sectionIds): array
{
    if (!$sectionIds) {
        return [];
    }

    $ids = [];

    $res = SectionElementTable::getList([
        'filter' => [
            '@IBLOCK_SECTION_ID' => $sectionIds,
        ],
        'select' => ['IBLOCK_ELEMENT_ID'],
    ]);

    while ($row = $res->fetch()) {
        $ids[(int)$row['IBLOCK_ELEMENT_ID']] = true;
    }

    $resMain = ElementTable::getList([
        'filter' => [
            '@IBLOCK_SECTION_ID' => $sectionIds,
        ],
        'select' => ['ID'],
    ]);

    while ($row = $resMain->fetch()) {
        $ids[(int)$row['ID']] = true;
    }

    return array_keys($ids);
}

function loadPricesByXmlId(string $xmlPath): array
{
    if (!file_exists($xmlPath)) {
        exit("Не найден файл XML по пути: {$xmlPath}\n");
    }

    $reader = new XMLReader();
    if (!$reader->open($xmlPath)) {
        exit("Не удалось открыть XML файл: {$xmlPath}\n");
    }

    $prices = [];

    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'product') {
            $xml = $reader->readOuterXML();
            if ($xml !== '') {
                try {
                    $node = new SimpleXMLElement($xml);
                    $xmlId = trim((string)$node->id);
                    $priceStr = trim((string)$node->price);

                    if ($xmlId !== '' && $priceStr !== '') {
                        $price = (float)str_replace([' ', ','], ['', '.'], $priceStr);
                        $prices[$xmlId] = $price;
                    }
                } catch (Throwable $e) {
                }
            }
        }
    }

    $reader->close();
    return $prices;
}

$pricesByXmlId = loadPricesByXmlId($xmlPath);
echo "Всего цен в XML: " . count($pricesByXmlId) . "\n";

$propRes = CIBlockProperty::GetList([], [
    'IBLOCK_ID' => $targetIblockId,
    '=CODE' => $targetPropertyCode,
]);
$prop = $propRes->Fetch();
if (!$prop) {
    exit("Не найдено свойство {$targetPropertyCode} в инфоблоке {$targetIblockId}\n");
}

$sectionIds = getDescendantSectionIds($targetIblockId, $rootSectionId);
$sectionIds[] = $rootSectionId;
$sectionIds = array_values(array_unique(array_map('intval', $sectionIds)));

$elementIds = getElementIdsBySectionIds($sectionIds);
if (!$elementIds) {
    exit("Не найдено товаров в подкатегориях раздела {$rootSectionId} (инфоблок {$targetIblockId})\n");
}

sort($elementIds);

echo "Товаров к обработке: " . count($elementIds) . "\n";

$updated = 0;
$notInXml = 0;
$noXmlId = 0;
$errors = 0;

$chunks = array_chunk($elementIds, 300);
foreach ($chunks as $chunk) {
    $res = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        [
            'IBLOCK_ID' => $targetIblockId,
            'ACTIVE' => 'Y',
            'ID' => $chunk,
        ],
        false,
        false,
        ['ID', 'NAME', 'CODE', 'PROPERTY_' . $targetPropertyCode]
    );

    while ($row = $res->GetNext()) {
        $elementId = (int)$row['ID'];
        $elementName = (string)($row['NAME'] ?? '');
        $elementCode = (string)($row['CODE'] ?? '');

        $xmlIdVal = (string)($row['PROPERTY_' . $targetPropertyCode . '_VALUE'] ?? $row['PROPERTY_' . $targetPropertyCode] ?? '');
        $xmlIdVal = trim($xmlIdVal);

        if ($xmlIdVal === '') {
            $noXmlId++;
            continue;
        }

        if (!array_key_exists($xmlIdVal, $pricesByXmlId)) {
            $notInXml++;
            continue;
        }

        $price = (float)$pricesByXmlId[$xmlIdVal];
        if (!upsertBasePrice($elementId, $price)) {
            $errors++;
            continue;
        }

        $updated++;
    }
}

echo "Итог: обновлено {$updated}, без XML_ID {$noXmlId}, не найдено в XML {$notInXml}, ошибок {$errors}\n";

echo "APPLY: изменения применены\n";
