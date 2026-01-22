<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arTemplateParameters = [
	"USE_PHONE_MASK" => [
		"PARENT" => "BASE",
		"NAME" =>  Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_PARAMETER_USE_PHONE_MASK"),
		"TYPE" => "CHECKBOX",
		"REFRESH" => "Y"
	],
];

if(isset($arCurrentValues["USE_PHONE_MASK"]) && $arCurrentValues["USE_PHONE_MASK"] === "Y"){
	$arTemplateParameters["PHONE_MASK_FORMAT"] = [
		"PARENT" => "BASE",
		"NAME" =>  Loc::getMessage("RP0_REQUEST_PRICE_FORM_MODAL_PARAMETER_PHONE_MASK_FORMAT"),
		"TYPE" => "LIST",
		"VALUES" => [
			"+7 (999) 999-99-99" => "+7 (999) 999-99-99 (ru)",
			"+380 (999) 999-99-99" => "+380 (999) 999-99-99 (ua)",
			"+375 (999) 999-99-99" => "+375 (999) 999-99-99 (by)"
		],
		"DEFAULT" => "+7 (999) 999-99-99",
		"ADDITIONAL_VALUES" => "Y",
	];
}
