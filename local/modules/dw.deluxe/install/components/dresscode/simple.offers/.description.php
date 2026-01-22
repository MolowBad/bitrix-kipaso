<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("SO0_SIMPLE_OFFERS_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("SO0_SIMPLE_OFFERS_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "simpleOffers",
			"NAME" => Loc::getMessage("SO0_SIMPLE_OFFERS_COMPONENT_NAME")
		]
	]
];
