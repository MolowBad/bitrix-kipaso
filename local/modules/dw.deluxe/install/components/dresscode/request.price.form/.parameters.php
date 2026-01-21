<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = [
	"PARAMETERS" => [
		"PRODUCT_ID" => [
			"PARENT" => "DATA_SOURCE",
			"NAME" => Loc::getMessage("RP0_REQUEST_PRICE_FORM_COMPONENT_PARAMETER_PRODUCT_ID"),
			"TYPE" => "STRING",
		],
		"CACHE_TIME" => [
			"DEFAULT" => 360_000_000
		],
	]
];
