<?php
use Bitrix\Main\Loader;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\IO\File;
use Bitrix\Iblock\ElementTable;
use Bitrix\Catalog\StoreProductTable;
use Bitrix\Catalog\ProductTable;

@set_time_limit(0);
@ini_set('memory_limit', '1024M');

// Configuration
const XML_PATH = '/1c-exchange/date-stock.xml';
const SKU_IBLOCK_ID = 17;          // TODO: set real SKU iblock ID
const PRODUCT_IBLOCK_ID = 16;      // TODO: set real Product iblock ID
const UPDATE_PARENT_SUM = true;   // Sum SKU quantities into parent product

// Bootstrap Bitrix
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($docRoot === '') {
    // Fallback for CLI
    $docRoot = realpath(__DIR__ . '/../../..');
    $_SERVER['DOCUMENT_ROOT'] = $docRoot;
}
require $docRoot . '/bitrix/modules/main/include/prolog_before.php';

if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
    http_response_code(500);
    echo 'Error: cannot load required modules (iblock, catalog)';
    exit;
}

// Controls
$run  = isset($_GET['run']) ? (int)$_GET['run'] : 0;
$dry  = isset($_GET['dry']) ? (int)$_GET['dry'] : 0;
$log  = isset($_GET['log']) ? (int)$_GET['log'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;

if (!$run) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Stock import is ready. Call with ?run=1 to execute.\n";
    echo "Params: dry=1 (no updates), log=1 (verbose), limit=N.\n";
    echo "XML: " . XML_PATH . "\n";
    echo "SKU_IBLOCK_ID: " . SKU_IBLOCK_ID . ", PRODUCT_IBLOCK_ID: " . PRODUCT_IBLOCK_ID . "\n";
    exit;
}

if (SKU_IBLOCK_ID <= 0) {
    http_response_code(500);
    echo 'Error: please set SKU_IBLOCK_ID in script.';
    exit;
}

// Resolve XML path with fallbacks
$xmlRel = isset($_GET['xml']) && $_GET['xml'] !== '' ? $_GET['xml'] : XML_PATH; // allow override via ?xml=/1c-exchange/file.xml
if ($xmlRel === '' || $xmlRel[0] !== '/') { $xmlRel = '/' . ltrim($xmlRel, '/'); }

// Helper: swap Cyrillic 'с' (U+0441) and Latin 'c' ONLY in directory names, not filename
function swapCyrLatCDir(string $dir): array {
    $cyr = 'с'; // U+0441
    $lat = 'c';
    $v1 = str_replace($cyr, $lat, $dir); // cyr->lat
    $v2 = str_replace($lat, $cyr, $dir); // lat->cyr
    return array_values(array_unique([$dir, $v1, $v2]));
}

// Split into dir + filename to avoid mutating filename
$pi = pathinfo($xmlRel);
$dir = isset($pi['dirname']) ? ($pi['dirname'] === '.' ? '/' : $pi['dirname']) : '/';
$base = $pi['basename'] ?? 'ДанныеПоОстаткам.xml';

$candidates = [];
// 1) original full path and url-decoded
$candidates[] = $xmlRel;
$candidates[] = urldecode($xmlRel);
// 2) variants with swapped c/с in dir only
foreach (swapCyrLatCDir($dir) as $d) {
    $rel = rtrim($d, '/') . '/' . $base;
    $candidates[] = $rel;
    $candidates[] = urldecode($rel);
}

// Deduplicate candidates
$candidates = array_values(array_unique($candidates));

$xmlFileAbs = '';
foreach ($candidates as $rel) {
    $abs = $docRoot . $rel;
    if (File::isFileExists($abs)) {
        $xmlFileAbs = $abs;
        break;
    }
}

if ($xmlFileAbs === '') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'XML file not found. Tried paths:\n';
    foreach ($candidates as $rel) {
        echo ' - ' . ($docRoot . $rel) . "\n";
    }
    // small diagnostic directory listing to help identify actual folder name
    if (!empty($log)) {
        echo "\nDocumentRoot: {$docRoot}\n";
        $rootList = @scandir($docRoot) ?: [];
        echo "Root entries (first 50):\n";
        $i = 0;
        foreach ($rootList as $entry) {
            if ($entry === '.' || $entry === '..') { continue; }
            echo ' * ' . $entry . "\n";
            if (++$i >= 50) break;
        }
    }
    exit;
}

// Parse XML efficiently
$reader = new XMLReader();
if (!$reader->open($xmlFileAbs)) {
    http_response_code(500);
    echo 'Failed to open XML via XMLReader';
    exit;
}

$updated = 0;
$processed = 0;
$errors = 0;
$parentsToSum = []; // parentId => ['sum' => int, 'onOrder' => bool]

function logm($msg, $log) {
    if ($log) { echo $msg . "\n"; }
}

// Helper: find SKU ID by CODE
function findSkuIdByCode(string $code): ?int {
    if ($code === '') { return null; }
    $row = ElementTable::getRow([
        'select' => ['ID', 'IBLOCK_ID'],
        'filter' => ['=IBLOCK_ID' => SKU_IBLOCK_ID, '=CODE' => $code, '=ACTIVE' => 'Y'],
        'cache'  => ['ttl' => 300],
    ]);
    return $row ? (int)$row['ID'] : null;
}

// Helper: get parent product ID for SKU
function getParentProductId(int $skuId): ?int {
    // Try via property CML2_LINK
    $res = CIBlockElement::GetByID($skuId);
    if ($el = $res->GetNextElement()) {
        $props = $el->GetProperties(['SORT' => 'ASC'], ['CODE' => 'CML2_LINK']);
        if (!empty($props['CML2_LINK']['VALUE'])) {
            return (int)$props['CML2_LINK']['VALUE'];
        }
    }
    return null;
}

// Iterate XML
while ($reader->read()) {
    if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'NOMENKLATUREWITHREMAINS') {
        $articule = $reader->getAttribute('articule') ?: '';
        $remains  = (int)($reader->getAttribute('remains') ?: 0);
        $processed++;
        if ($limit > 0 && $processed > $limit) { break; }

        if ($articule === '') { $errors++; continue; }

        $skuId = findSkuIdByCode($articule);
        if (!$skuId) {
            logm("WARN: SKU not found by CODE/articule='{$articule}'", $log);
            continue;
        }

        // Map remains to availability/status
        $qty = ($remains > 0) ? $remains : 0;                 // only positive numbers become stock
        $canBuyZero = ($remains <= 0) ? 'Y' : 'N';            // 0 or negative means on order
        $status = ($remains > 0) ? 'in_stock' : 'on_order';

        // Update SKU availability
        if (!$dry) {
            $r = ProductTable::update($skuId, [
                'QUANTITY' => $qty,
                'QUANTITY_TRACE' => 'Y',
                'CAN_BUY_ZERO' => $canBuyZero,
            ]);
            if (!$r->isSuccess()) {
                $errors++;
                logm('ERR: update SKU '.$skuId.' failed: '.implode('; ', $r->getErrorMessages()), $log);
                continue;
            }

            // Backend sort: in stock first (lower SORT), then on order
            $sortVal = ($qty > 0) ? 100 : 200;
            try {
                $es = ElementTable::update($skuId, ['SORT' => $sortVal]);
                if (!$es->isSuccess()) {
                    logm('WARN: update SORT for SKU '.$skuId.' failed: '.implode('; ', $es->getErrorMessages()), $log);
                }
            } catch (\Throwable $e) {
                logm('WARN: exception while updating SORT for SKU '.$skuId.': '.$e->getMessage(), $log);
            }
        }
        $updated++;
        logm("OK: SKU {$skuId} CODE={$articule} remains={$remains} => status={$status}, QUANTITY={$qty}, CAN_BUY_ZERO={$canBuyZero}", $log);

        if (UPDATE_PARENT_SUM) {
            $parentId = getParentProductId($skuId);
            if ($parentId) {
                if (!isset($parentsToSum[$parentId])) { $parentsToSum[$parentId] = ['sum' => 0, 'onOrder' => false]; }
                // Sum only positive remains to parent's QUANTITY
                if ($remains > 0) { $parentsToSum[$parentId]['sum'] += $remains; }
                // Mark parent as on-order if any child is on-order (remains <= 0)
                if ($remains <= 0) { $parentsToSum[$parentId]['onOrder'] = true; }
            }
        }
    }
}
$reader->close();

// Update parents
if (UPDATE_PARENT_SUM && !empty($parentsToSum)) {
    foreach ($parentsToSum as $parentId => $info) {
        if ($parentId <= 0) { continue; }
        $sumQty = (int)($info['sum'] ?? 0);
        $parentCanBuyZero = !empty($info['onOrder']) ? 'Y' : 'N';
        if (!$dry) {
            $r = ProductTable::update($parentId, [
                'QUANTITY' => $sumQty,
                'QUANTITY_TRACE' => 'Y',
                'CAN_BUY_ZERO' => $parentCanBuyZero,
            ]);
            if (!$r->isSuccess()) {
                $errors++;
                logm('ERR: update parent '.$parentId.' failed: '.implode('; ', $r->getErrorMessages()), $log);
                continue;
            }
        }
        logm("OK: PARENT {$parentId} => QUANTITY={$sumQty}, CAN_BUY_ZERO={$parentCanBuyZero}", $log);
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'processed' => $processed,
    'updated' => $updated,
    'errors' => $errors,
    'parents_updated' => UPDATE_PARENT_SUM ? count($parentsToSum) : 0,
    'dry' => (bool)$dry,
]);
