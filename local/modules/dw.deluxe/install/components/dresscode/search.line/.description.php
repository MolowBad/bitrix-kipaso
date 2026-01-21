<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("SL0_SEARCH_LINE_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("SL0_SEARCH_LINE_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "searchLine",
			"NAME" => Loc::getMessage("SL0_SEARCH_LINE_COMPONENT_NAME")
		]
	]
];
