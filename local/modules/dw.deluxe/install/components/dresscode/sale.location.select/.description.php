<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("LS0_SALE_LOCATION_SEARCH_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("LS0_SALE_LOCATION_SEARCH_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "saleLocationSearch",
			"NAME" => Loc::getMessage("LS0_SALE_LOCATION_SEARCH_COMPONENT_NAME")
		]
	]
];
