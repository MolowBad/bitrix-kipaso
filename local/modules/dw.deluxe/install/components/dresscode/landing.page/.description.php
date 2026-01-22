<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("LP0_LANDING_PAGE_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("LP0_LANDING_PAGE_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "landingPage",
			"NAME" => Loc::getMessage("LP0_LANDING_PAGE_COMPONENT_NAME")
		]
	]
];
