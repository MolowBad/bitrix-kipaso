<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("SL0_SEARCH_SMART_FILTER_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("SL0_SEARCH_SMART_FILTER_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "searchSmartFilter",
			"NAME" => Loc::getMessage("SL0_SEARCH_SMART_FILTER_COMPONENT_NAME")
		]
	]
];
