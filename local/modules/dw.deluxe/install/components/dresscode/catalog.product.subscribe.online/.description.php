<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("PO0_CATALOG_SUBSCRIBE_ONLINE_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("PO0_CATALOG_SUBSCRIBE_ONLINE_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogSubscribeOnline",
			"NAME" => Loc::getMessage("PO0_CATALOG_SUBSCRIBE_ONLINE_COMPONENT_NAME")
		]
	]
];
