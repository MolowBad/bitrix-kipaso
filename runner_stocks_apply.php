<?php
$doc = __DIR__;
chdir($doc);
$_SERVER['DOCUMENT_ROOT'] = $doc;
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $doc.'/bitrix/modules/main/include/prolog_before.php';

$_GET['run']=1; $_GET['log']=1;
$_REQUEST = $_GET;

// путь к XML, если нужен параметр:
$_GET['xml'] = '/1c-exchange/date-stock.xml';
$_REQUEST['xml'] = $_GET['xml'];

include $doc.'/local/scripts/import_stocks.php';