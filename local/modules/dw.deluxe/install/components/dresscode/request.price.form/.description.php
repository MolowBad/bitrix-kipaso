<?

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("RP0_REQUEST_PRICE_FORM_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("RP0_REQUEST_PRICE_FORM_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "requestPriceForm",
			"NAME" => Loc::getMessage("RP0_REQUEST_PRICE_FORM_COMPONENT_NAME")
		]
	]
];
