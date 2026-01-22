<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CM0_CATALOG_TAGS_META_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CM0_CATALOG_TAGS_META_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogTagsMeta",
			"NAME" => Loc::getMessage("CM0_CATALOG_TAGS_META_COMPONENT_NAME")
		]
	]
];
