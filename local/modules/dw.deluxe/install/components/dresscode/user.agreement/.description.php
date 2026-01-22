<?

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
	"NAME" => Loc::getMessage("UA0_USER_AGREEMENT_COMPONENT_NAME"),
	"DESCRIPTION" => Loc::getMessage("UA0_USER_AGREEMENT_COMPONENT_DESCRIPTION"),
	"ICON" => "/images/offers.gif",
	"PATH" => [
		"ID" => "DRESSCODE",
		"CHILD" => [
			"ID" => "userAgreement",
			"NAME" => Loc::getMessage("UA0_USER_AGREEMENT_COMPONENT_NAME")
		]
	]
];
