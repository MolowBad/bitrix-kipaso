<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("SB0_SALE_BASKET_BASKET_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("SB0_SALE_BASKET_BASKET_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "saleBasketBasket",
			"NAME" => Loc::getMessage("SB0_SALE_BASKET_BASKET_COMPONENT_NAME")
		]
	]
];
