<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CC0_CATALOG_COMPARE_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CC0_CATALOG_COMPARE_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogCompare",
			"NAME" => Loc::getMessage("CC0_CATALOG_COMPARE_COMPONENT_NAME")
		]
	]
];
