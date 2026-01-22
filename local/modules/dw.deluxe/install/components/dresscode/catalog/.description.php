<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CG0_CATALOG_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CG0_CATALOG_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/catalog.gif",
	"COMPLEX" => "Y",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "catalog",
			"NAME" => Loc::getMessage("CG0_CATALOG_COMPONENT_NAME")
		]
	]
];
