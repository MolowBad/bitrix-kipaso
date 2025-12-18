<?php
ini_set('memory_limit', '512M');
set_time_limit(1800);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    die('Ошибка подключения модуля iblock');
}

$IBLOCK_ID = 16;
$SECTION_ID = 259;

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$xmlPath = $request->get('xml') ?: ($_SERVER['DOCUMENT_ROOT'] . '/meyertecAPI.xml');
$dryRun = strtoupper((string)($request->get('dry_run') ?? 'N')) === 'Y';
$limit = (int)($request->get('limit') ?? 0);
$includeSubsections = strtoupper((string)($request->get('include_subsections') ?? 'Y')) === 'Y' ? 'Y' : 'N';
$activeFilterParam = strtoupper((string)($request->get('active') ?? 'Y'));
$activeFilter = null;
if ($activeFilterParam === 'Y' || $activeFilterParam === 'N') {
    $activeFilter = $activeFilterParam;
}

$ABSOLUTE_URL_PREFIX = 'https://owen.ru';

function normalizeKey(string $s): string {
    $s = trim($s);
    $s = preg_replace('/\s+/u', ' ', $s);
    $s = str_replace(["\xC2\xA0"], ' ', $s);
    $s = trim($s);
    $s = mb_strtoupper($s);
    return $s;
}

function extractLeadingCode(string $name): string {
    $name = trim($name);
    if ($name === '') {
        return '';
    }
    if (preg_match('/^([A-Za-z0-9._\-]+)/u', $name, $m)) {
        return $m[1];
    }
    $parts = preg_split('/\s+/u', $name, 2);
    return $parts[0] ?? '';
}

function buildDetailText(array $parts): string {
    $out = '';
    foreach ($parts as $part) {
        $part = (string)$part;
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        if ($out !== '') {
            $out .= "<br><br>";
        }
        $out .= $part;
    }
    return $out;
}

function absolutizeRelativeUrls(string $html, string $prefix): string {
    $prefix = rtrim($prefix, '/');

    $html = preg_replace_callback(
        '/\b(href|src)\s*=\s*(["\'])(\/[^"\']*)\2/iu',
        static function ($m) use ($prefix) {
            $url = (string)$m[3];
            if (strpos($url, '//') === 0) {
                return $m[0];
            }
            return $m[1] . '=' . $m[2] . $prefix . $url . $m[2];
        },
        $html
    );

    $html = preg_replace_callback(
        '/url\(\s*(["\']?)(\/[^\)"\']*)\1\s*\)/iu',
        static function ($m) use ($prefix) {
            $url = (string)$m[2];
            if (strpos($url, '//') === 0) {
                return $m[0];
            }
            return 'url(' . $m[1] . $prefix . $url . $m[1] . ')';
        },
        $html
    );

    return $html;
}

if (!is_file($xmlPath)) {
    die('XML файл не найден: ' . htmlspecialcharsbx($xmlPath));
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($xmlPath, 'SimpleXMLElement', LIBXML_NOCDATA);
if (!$xml) {
    $errors = array_map(static fn($e) => trim($e->message), libxml_get_errors());
    die('Ошибка парсинга XML: ' . htmlspecialcharsbx(implode('; ', $errors)));
}

if (!isset($xml->products) || !isset($xml->products->product)) {
    die('В XML не найден блок products/product');
}

$map = [];
foreach ($xml->products->product as $p) {
    $xmlName = trim((string)($p->name ?? ''));
    if ($xmlName === '') {
        continue;
    }

    $fullName = trim((string)($p->fullName ?? ''));
    $detailText = absolutizeRelativeUrls(buildDetailText([
        (string)($p->text ?? ''),
        (string)($p->text2 ?? ''),
        (string)($p->text3 ?? ''),
        (string)($p->packing ?? ''),
    ]), $ABSOLUTE_URL_PREFIX);

    $key = normalizeKey($xmlName);
    $map[$key] = [
        'xmlName' => $xmlName,
        'fullName' => $fullName,
        'detailText' => $detailText,
    ];
}

$sectionRow = \Bitrix\Iblock\SectionTable::getList([
    'filter' => [
        '=IBLOCK_ID' => $IBLOCK_ID,
        '=ID' => $SECTION_ID,
    ],
    'select' => ['ID', 'NAME', 'IBLOCK_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN'],
])->fetch();

$sectionIds = [];
if ($sectionRow) {
    if ($includeSubsections === 'Y') {
        $secListRes = \Bitrix\Iblock\SectionTable::getList([
            'filter' => [
                '=IBLOCK_ID' => $IBLOCK_ID,
                '>=LEFT_MARGIN' => (int)$sectionRow['LEFT_MARGIN'],
                '<=RIGHT_MARGIN' => (int)$sectionRow['RIGHT_MARGIN'],
            ],
            'select' => ['ID'],
            'order' => ['LEFT_MARGIN' => 'ASC'],
        ]);
        while ($s = $secListRes->fetch()) {
            $sectionIds[] = (int)$s['ID'];
        }
    } else {
        $sectionIds = [(int)$SECTION_ID];
    }
}

$total = 0;
$matched = 0;
$updated = 0;
$skipped = 0;
$notFoundInXml = 0;
$errors = [];
$errorCount = 0;

$el = new CIBlockElement();

$elementIds = [];
if ($sectionIds) {
    $seRes = \Bitrix\Iblock\SectionElementTable::getList([
        'filter' => [
            '@IBLOCK_SECTION_ID' => $sectionIds,
        ],
        'select' => ['IBLOCK_ELEMENT_ID'],
    ]);
    while ($se = $seRes->fetch()) {
        $eid = (int)$se['IBLOCK_ELEMENT_ID'];
        if ($eid > 0) {
            $elementIds[$eid] = true;
        }
    }
}

$elementIdList = array_keys($elementIds);
sort($elementIdList, SORT_NUMERIC);

$elementFilter = [
    '=IBLOCK_ID' => $IBLOCK_ID,
    '@ID' => $elementIdList,
];
if ($activeFilter !== null) {
    $elementFilter['=ACTIVE'] = $activeFilter;
}

$res = \Bitrix\Iblock\ElementTable::getList([
    'filter' => $elementFilter,
    'select' => ['ID', 'NAME'],
    'order' => ['ID' => 'ASC'],
]);

while ($row = $res->fetch()) {
    $total++;
    if ($limit > 0 && $total > $limit) {
        break;
    }

    $currentName = (string)$row['NAME'];
    $code = extractLeadingCode($currentName);
    $key = normalizeKey($code !== '' ? $code : $currentName);

    if (!isset($map[$key])) {
        $notFoundInXml++;
        continue;
    }

    $matched++;
    $xmlData = $map[$key];

    $newName = $currentName;
    $fullName = (string)$xmlData['fullName'];
    $xmlName = (string)$xmlData['xmlName'];

    if ($fullName !== '') {
        $fullNameNorm = normalizeKey($fullName);
        $currentNameNorm = normalizeKey($currentName);

        if (mb_strpos($currentNameNorm, $fullNameNorm) === false) {
            $newName = $xmlName . ' ' . $fullName;
        }
    }

    $newDetail = (string)$xmlData['detailText'];

    $fieldsToUpdate = [];
    if ($newName !== $currentName) {
        $fieldsToUpdate['NAME'] = $newName;
    }

    $fieldsToUpdate['DETAIL_TEXT'] = $newDetail;
    $fieldsToUpdate['DETAIL_TEXT_TYPE'] = 'html';

    if (!$fieldsToUpdate) {
        $skipped++;
        continue;
    }

    if ($dryRun) {
        $updated++;
        continue;
    }

    $ok = $el->Update((int)$row['ID'], $fieldsToUpdate);
    if ($ok) {
        $updated++;
    } else {
        $errorCount++;
        if (count($errors) < 20) {
            $errors[] = 'ID=' . (int)$row['ID'] . ': ' . $el->LAST_ERROR;
        }
    }
}

echo htmlspecialcharsbx(
    'Готово. total=' . $total .
    ', matched=' . $matched .
    ', updated=' . $updated .
    ', skipped=' . $skipped .
    ', miss=' . $notFoundInXml .
    ', errors=' . $errorCount
) . "<br>\n";

if ($errors) {
    echo 'Ошибки (первые ' . count($errors) . '):<br>\n';
    foreach ($errors as $e) {
        echo htmlspecialcharsbx($e) . "<br>\n";
    }
}
