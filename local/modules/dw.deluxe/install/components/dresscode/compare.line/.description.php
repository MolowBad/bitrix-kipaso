<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CL0_COMPARE_LINE_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CL0_COMPARE_LINE_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "compareLine",
			"NAME" => Loc::getMessage("CL0_COMPARE_LINE_COMPONENT_NAME")
		]
	]
];
