<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("SI0_CATALOG_SALE_ITEM_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("SI0_CATALOG_SALE_ITEM_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogSaleItem",
			"NAME" => Loc::getMessage("SI0_CATALOG_SALE_ITEM_COMPONENT_NAME")
		]
	]
];
