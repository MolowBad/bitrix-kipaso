<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CL0_CATALOG_STORE_LIST_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CL0_CATALOG_STORE_LIST_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogStoreList",
			"NAME" => Loc::getMessage("CL0_CATALOG_STORE_LIST_COMPONENT_NAME")
		]
	]
];
