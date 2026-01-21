<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("PS0_POP_SECTION_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("PS0_POP_SECTION_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "popSection",
			"NAME" => Loc::getMessage("PS0_POP_SECTION_COMPONENT_NAME")
		]
	]
];
