<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Context;

ini_set('display_errors', '1');
error_reporting(E_ALL);

$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? realpath(__DIR__ . '/..');//пытаемся определить DOCUMENT_ROOT ,если его нет то ищем сами
if ($docRoot === '') {
    $docRoot = realpath(__DIR__ . '/../../..');
    $_SERVER['DOCUMENT_ROOT'] = $docRoot;
}

$prologPath = $docRoot . '/bitrix/modules/main/include/prolog_before.php';

if (!file_exists($prologPath)) {
    die("Не найден файл prolog_before.php по пути: {$prologPath}\n");
}
require $prologPath;

if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
    die ("Не удалось подключить модуль iblock или catalog\n");
}

$request = Context::getCurrent()->getRequest();
$codeRaw = trim((string) $request->getQuery('code'));
$code = preg_replace('~[^a-zA-Z0-9_-]~', '', $codeRaw);

if ($code === '') {
    die ("символьный код не найдет");
}

