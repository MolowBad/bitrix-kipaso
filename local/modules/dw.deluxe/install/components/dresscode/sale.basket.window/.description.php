<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("SW0_SALE_BASKET_WINDOW_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("SW0_SALE_BASKET_WINDOW_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "saleBasketWindow",
			"NAME" => Loc::getMessage("SW0_SALE_BASKET_WINDOW_COMPONENT_NAME")
		]
	]
];
