<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CA0_CATALOG_ADVANTAGES_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CA0_CATALOG_ADVANTAGES_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogAdvantages",
			"NAME" => Loc::getMessage("CA0_CATALOG_ADVANTAGES_COMPONENT_NAME")
		]
	]
];
