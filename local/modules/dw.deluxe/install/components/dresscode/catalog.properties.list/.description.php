<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("PL0_CATALOG_PROPERTIES_LIST_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("PL0_CATALOG_PROPERTIES_LIST_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogPropertiesList",
			"NAME" => Loc::getMessage("PL0_CATALOG_PROPERTIES_LIST_COMPONENT_NAME")
		]
	]
];
