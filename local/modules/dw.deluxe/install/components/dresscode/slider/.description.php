<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("MS0_MAIN_SLIDER_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("MS0_MAIN_SLIDER_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "mainSlider",
			"NAME" => Loc::getMessage("MS0_MAIN_SLIDER_COMPONENT_NAME")
		]
	]
];
