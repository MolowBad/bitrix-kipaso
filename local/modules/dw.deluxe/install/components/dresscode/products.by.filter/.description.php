<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("PF0_PRODUCTS_BY_FILTER_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("PF0_PRODUCTS_BY_FILTER_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "productsByFilter",
			"NAME" => Loc::getMessage("PF0_PRODUCTS_BY_FILTER_COMPONENT_NAME")
		]
	]
];
