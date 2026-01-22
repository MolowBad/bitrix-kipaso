<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CV0_CATALOG_VIEWED_PRODUCTS_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CV0_CATALOG_VIEWED_PRODUCTS_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogViewedProducts",
			"NAME" => Loc::getMessage("CV0_CATALOG_VIEWED_PRODUCTS_COMPONENT_NAME")
		]
	]
];
