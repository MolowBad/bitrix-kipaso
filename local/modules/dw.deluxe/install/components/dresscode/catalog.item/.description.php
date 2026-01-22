<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CI0_CATALOG_ITEM_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CI0_CATALOG_ITEM_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogItem",
			"NAME" => Loc::getMessage("CI0_CATALOG_ITEM_COMPONENT_NAME")
		]
	]
];
