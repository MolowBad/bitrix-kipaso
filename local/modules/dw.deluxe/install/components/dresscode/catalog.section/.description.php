<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CS0_CATALOG_SECTION_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CS0_CATALOG_SECTION_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogSection",
			"NAME" => Loc::getMessage("CS0_CATALOG_SECTION_COMPONENT_NAME")
		]
	]
];
