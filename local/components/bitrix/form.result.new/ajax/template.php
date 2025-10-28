<?php

use Bitrix\Main\Loader;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Web\Json;
use Bitrix\Main\UserConsent\Consent;

if($arResult["isFormErrors"] == "Y"){
	if(!empty($arResult["FORM_ERRORS"])){
		$arReturn["ERROR"] = $arResult["FORM_ERRORS"];
	}

	$arReturn["CAPTCHA"] = array(
		"CODE" => htmlspecialcharsbx($arResult["CAPTCHACode"]),
		"PICTURE" => "/bitrix/tools/captcha.php?captcha_sid=".htmlspecialcharsbx($arResult["CAPTCHACode"])
	);
}else{
	Loader::requireModule('dw.deluxe');

	$settings = DwSettings::getInstance()->getCurrentSettings();
	$userAgreementId = $settings['TEMPLATE_AGREEMENT_ID'] ?? null;

	if ($userAgreementId !== null) {
		Consent::addByContext($userAgreementId);
	}

	$arReturn["SUCCESS"] = "Y";
}

echo Json::encode($arReturn);
