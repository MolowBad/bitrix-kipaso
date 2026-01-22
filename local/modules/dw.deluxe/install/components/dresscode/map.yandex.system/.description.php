<?php

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("MY0_YANDEX_SYSTEM_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("MY0_YANDEX_SYSTEM_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "mapYandexSystem",
			"NAME" => Loc::getMessage("MY0_YANDEX_SYSTEM_COMPONENT_NAME")
		]
	]
];
