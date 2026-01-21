<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("WL0_WISHLIST_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("WL0_WISHLIST_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "wishlist",
			"NAME" => Loc::getMessage("WL0_WISHLIST_COMPONENT_NAME")
		]
	]
];
