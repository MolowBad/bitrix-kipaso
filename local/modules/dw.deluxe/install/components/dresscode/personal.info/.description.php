<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("PI0_PERSONAL_INFO_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("PI0_PERSONAL_INFO_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "personalInfo",
			"NAME" => Loc::getMessage("PI0_PERSONAL_INFO_COMPONENT_NAME")
		]
	]
];
