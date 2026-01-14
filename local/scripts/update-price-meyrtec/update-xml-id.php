<?php

use Bitrix\Main\Loader;
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\SectionElementTable;
use Bitrix\Iblock\ElementTable;

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

if (!Loader::includeModule('iblock')) {
    exit("Не удалось подключить модуль iblock\n");
}

$targetIblockId = 16;
$rootSectionId = 259;
$targetPropertyCode = 'XML_ID';

$dryRun = (isset($_GET['dry-run']) && strtoupper((string)$_GET['dry-run']) === 'Y');
$limit = isset($_GET['limit']) ? max(0, (int)$_GET['limit']) : 0;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

if (php_sapi_name() === 'cli') {
    global $argv;
    $args = $argv ?? [];
    if (!empty($args)) {
        array_shift($args);
    }
    foreach ($args as $arg) {
        if ($arg === '--dry-run' || $arg === '--dry-run=Y' || $arg === '--dry-run=y') {
            $dryRun = true;
        } elseif (preg_match('/^--limit=(\d+)$/i', $arg, $m)) {
            $limit = max(0, (int)$m[1]);
        } elseif (preg_match('/^--offset=(\d+)$/i', $arg, $m)) {
            $offset = max(0, (int)$m[1]);
        }
    }
}

function normalizeCode(string $code): string
{
    $code = html_entity_decode($code, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $code = trim($code);
    $code = str_replace([' ', "\t", "\n", "\r"], '', $code);
    $code = str_replace(['_', '.', '/'], '-', $code);
    $code = preg_replace('/-+/u', '-', $code);
    $code = trim($code, '-');
    return mb_strtolower($code ?? '', 'UTF-8');
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

function loadXmlIdMap(string $xmlPath): array
{
    if (!file_exists($xmlPath)) {
        exit("Не найден файл XML по пути: {$xmlPath}\n");
    }

    $reader = new XMLReader();
    if (!$reader->open($xmlPath)) {
        exit("Не удалось открыть XML файл: {$xmlPath}\n");
    }

    $map = [];

    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'product') {
            $xml = $reader->readOuterXML();
            if ($xml !== '') {
                try {
                    $node = new SimpleXMLElement($xml);
                    $xmlId = trim((string)$node->id);
                    $name = trim((string)$node->name);
                    if ($xmlId !== '' && $name !== '') {
                        $map[normalizeCode($name)] = $xmlId;
                    }
                } catch (Throwable $e) {
                }
            }
        }
    }

    $reader->close();
    return $map;
}

$xmlPath = $docRoot . '/meyertecAPI.xml';
$xmlIdByCode = loadXmlIdMap($xmlPath);
echo "Всего кодов в XML: " . count($xmlIdByCode) . "\n";

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
if ($offset > 0 || $limit > 0) {
    if ($offset > 0) {
        $elementIds = array_slice($elementIds, $offset);
    }
    if ($limit > 0) {
        $elementIds = array_slice($elementIds, 0, $limit);
    }
}

echo "Товаров к обработке: " . count($elementIds) . "\n";

$updated = 0;
$unchanged = 0;
$notInXml = 0;
$errors = 0;
$notInXmlExamples = [];

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
        $codeNorm = normalizeCode((string)($row['CODE'] ?? ''));

        if ($codeNorm === '' || !array_key_exists($codeNorm, $xmlIdByCode)) {
            $notInXml++;
            if (count($notInXmlExamples) < 20) {
                $notInXmlExamples[] = (string)($row['CODE'] ?? '') . ' | ' . (string)($row['NAME'] ?? '');
            }
            continue;
        }

        $newValue = (string)$xmlIdByCode[$codeNorm];
        $current = (string)($row['PROPERTY_' . $targetPropertyCode . '_VALUE'] ?? $row['PROPERTY_' . $targetPropertyCode] ?? '');

        if (trim($current) === $newValue) {
            $unchanged++;
            continue;
        }

        if (!$dryRun) {
            try {
                CIBlockElement::SetPropertyValuesEx($elementId, $targetIblockId, [
                    $targetPropertyCode => $newValue,
                ]);
            } catch (Throwable $e) {
                $errors++;
                continue;
            }
        }

        $updated++;
    }
}

echo "Итог: обновлено {$updated}, без изменений {$unchanged}, не найдено в XML {$notInXml}, ошибок {$errors}\n";
if ($notInXmlExamples) {
    echo "Примеры CODE|NAME, которых нет в XML (первые 20):\n";
    foreach ($notInXmlExamples as $example) {
        echo "- {$example}\n";
    }
}

echo $dryRun ? "DRY-RUN: изменения не применялись\n" : "APPLY: изменения применены\n";
