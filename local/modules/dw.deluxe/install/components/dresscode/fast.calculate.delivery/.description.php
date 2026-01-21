<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CD0_FAST_CALCULATE_DELIVERY_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CD0_FAST_CALCULATE_DELIVERY_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "fastCalculateDelivery",
			"NAME" => Loc::getMessage("CD0_FAST_CALCULATE_DELIVERY_COMPONENT_NAME")
		]
	]
];
