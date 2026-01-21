<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("SS0_SETTINGS_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("SS0_SETTINGS_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "settings",
			"NAME" => Loc::getMessage("SS0_SETTINGS_COMPONENT_NAME")
		]
	]
];
