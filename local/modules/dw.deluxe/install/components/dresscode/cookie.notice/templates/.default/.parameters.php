<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arTemplateParameters = [
	"HEADING" => [
		"PARENT" => "BASE",
		"NAME" => Loc::getMessage("CN0_COOKIE_NOTICE_DEFAULT_PARAMETER_HEADING"),
		"TYPE" => "STRING",
		"DEFAULT" => "",
	],
	"TEXT" => [
		"PARENT" => "BASE",
		"NAME" => Loc::getMessage("CN0_COOKIE_NOTICE_DEFAULT_PARAMETER_TEXT"),
		"TYPE" => "STRING",
		"DEFAULT" => "",
		"ROWS" => 5,
		"COLS" => 60,
	],
	"PRIVACY_POLICY_URL" => [
		"PARENT" => "BASE",
		"NAME" => Loc::getMessage("CN0_COOKIE_NOTICE_DEFAULT_PARAMETER_PRIVACY_POLICY_URL"),
		"TYPE" => "STRING",
		"DEFAULT" => "#SITE_DIR#privacy-policy/",
	],
	"CONFIRM_BUTTON_TEXT" => [
		"PARENT" => "BASE",
		"NAME" => Loc::getMessage("CN0_COOKIE_NOTICE_DEFAULT_PARAMETER_CONFIRM_BUTTON_TEXT"),
		"TYPE" => "STRING",
		"DEFAULT" => "",
	]
];
