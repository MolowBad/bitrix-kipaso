<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

if (!empty($_SESSION["COMPARE_LIST"]["ITEMS"])) {
	$arResult["COUNT_ITEMS"] = count($_SESSION["COMPARE_LIST"]["ITEMS"]);
}

$this->IncludeComponentTemplate();
