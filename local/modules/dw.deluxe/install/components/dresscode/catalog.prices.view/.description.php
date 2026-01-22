<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("PV0_CATALOG_PRICES_VIEW_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("PV0_CATALOG_PRICES_VIEW_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogPricesView",
			"NAME" => Loc::getMessage("PV0_CATALOG_PRICES_VIEW_COMPONENT_NAME")
		]
	]
];
