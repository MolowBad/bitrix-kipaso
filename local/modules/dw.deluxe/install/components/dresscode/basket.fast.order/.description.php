<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => GetMessage("BF0_BASKET_FAST_ORDER_COMPONENT_NAME"),
	"DESCRIPTION" => GetMessage("BF0_BASKET_FAST_ORDER_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "basketFastOrder",
			"NAME" => Loc::getMessage("BF0_BASKET_FAST_ORDER_COMPONENT_NAME")
		]
	]
];
