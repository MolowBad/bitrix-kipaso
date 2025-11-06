<?php
/**
 * Шаблон проверки/подготовки свойства GUID (ID=116, CODE=GUID) в ИБ SKU (ID=17).
 * Запуск:
 *   /local/scripts/check_guid_property.php?run=1&log=1
 */

use Bitrix\Main\Loader;

header('Content-Type: text/plain; charset=utf-8');

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($docRoot === '') {
    $docRoot = realpath(__DIR__ . '/../../..');
    $_SERVER['DOCUMENT_ROOT'] = $docRoot;
}
require $docRoot . '/bitrix/modules/main/include/prolog_before.php';

if (!Loader::includeModule('iblock')) {
    echo "Ошибка: модуль iblock недоступен\n";
    exit(1);
}

$run = isset($_GET['run']) ? (int)$_GET['run'] : 0;
$verbose = isset($_GET['log']) && (int)$_GET['log'] === 1;

const SKU_IBLOCK_ID      = 17;
const GUID_PROPERTY_ID   = 116;
const GUID_PROPERTY_CODE = 'GUID';

if (!$run) {
    echo "Проверка свойства GUID. Запуск: ?run=1&log=1\n";
    echo "SKU_IBLOCK_ID=" . SKU_IBLOCK_ID . ", PROP_ID=" . GUID_PROPERTY_ID . ", CODE=" . GUID_PROPERTY_CODE . "\n";
    exit(0);
}



echo "Шаблон проверки/создания свойства готов. Реализацию добавь по TODO.\n";
exit(0);
