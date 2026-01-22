<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("SH0_SEARCH_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("SH0_SEARCH_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "search",
			"NAME" => Loc::getMessage("SH0_SEARCH_COMPONENT_NAME")
		]
	]
];
