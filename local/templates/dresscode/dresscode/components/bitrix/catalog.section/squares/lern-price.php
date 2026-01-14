<?php

use Bitrix\Main\Loader;

ini_set('display_errors', '1');
error_reporting(E_ALL);

$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? realpath(__DIR__ . '/..');//пытаемся определить DOCUMENT_ROOT ,если его нет то ищем сами
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
    exit ("Не удалось подключить модуль iblock или catalog\n");
}
