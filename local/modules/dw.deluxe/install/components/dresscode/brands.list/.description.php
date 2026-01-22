<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("BL0_BRANDS_LIST_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("BL0_BRANDS_LIST_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "brandsList",
			"NAME" => Loc::getMessage("BL0_BRANDS_LIST_COMPONENT_NAME")
		]
	]
];
