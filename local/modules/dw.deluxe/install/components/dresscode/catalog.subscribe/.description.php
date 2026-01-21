<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CS0_CATALOG_SUBSCRIBE_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CS0_CATALOG_SUBSCRIBE_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogSubscribe",
			"NAME" => Loc::getMessage("CS0_CATALOG_SUBSCRIBE_COMPONENT_NAME")
		]
	]
];
