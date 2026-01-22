<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserConsent\Agreement;

Loc::loadMessages(__FILE__);

$agreements = Agreement::getActiveList();
$agreementsFormatted = [];

foreach($agreements as $agreementId => $agreementName) {
	$agreementsFormatted[$agreementId] = "[{$agreementId}] $agreementName";
}

$arComponentParameters = [
	"PARAMETERS" => [
		"AGREEMENT_ID" => [
			"PARENT" => "DATA_SOURCE",
			"NAME" => Loc::getMessage("UA0_USER_AGREEMENT_COMPONENT_PARAMETER_AGREEMENT_ID"),
			"TYPE" => "LIST",
			"VALUES" => $agreementsFormatted,
		],
		"CACHE_TIME" => [
			"DEFAULT" => 360_000_000
		],
	]
];
