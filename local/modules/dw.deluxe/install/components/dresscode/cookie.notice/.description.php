<?

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("CN0_COOKIE_NOTICE_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("CN0_COOKIE_NOTICE_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "cookiesNotice",
			"NAME" => Loc::getMessage("CN0_COOKIE_NOTICE_COMPONENT_NAME")
		]
	]
];
