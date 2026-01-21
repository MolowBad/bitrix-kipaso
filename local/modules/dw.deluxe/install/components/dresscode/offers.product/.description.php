<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("OP0_OFFERS_PRODUCT_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("OP0_OFFERS_PRODUCT_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "offersProduct",
			"NAME" => Loc::getMessage("OP0_OFFERS_PRODUCT_COMPONENT_NAME")
		]
	]
];
