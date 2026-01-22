<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("SP0_SPECIAL_PRODUCT_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("SP0_SPECIAL_PRODUCT_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "specialProduct",
			"NAME" => Loc::getMessage("SP0_SPECIAL_PRODUCT_COMPONENT_NAME")
		]
	]
];
