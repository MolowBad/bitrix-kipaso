<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("PS0_CATALOG_PRODUCT_SUBSCRIBE_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("PS0_CATALOG_PRODUCT_SUBSCRIBE_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogProductSubscribe",
			"NAME" => Loc::getMessage("PS0_CATALOG_PRODUCT_SUBSCRIBE_COMPONENT_NAME")
		]
	]
];
