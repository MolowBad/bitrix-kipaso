<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CT0_CATALOG_TAGS_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CT0_CATALOG_TAGS_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalogTags",
			"NAME" => Loc::getMessage("CT0_CATALOG_TAGS_COMPONENT_NAME")
		]
	]
];
