<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("PO0_CATALOG_PRODUCT_OFFERS_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("PO0_CATALOG_PRODUCT_OFFERS_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogProductOffers",
			"NAME" => Loc::getMessage("PO0_CATALOG_PRODUCT_OFFERS_COMPONENT_NAME")
		]
	]
];
