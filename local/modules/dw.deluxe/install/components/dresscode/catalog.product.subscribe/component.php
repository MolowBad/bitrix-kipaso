<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

if (
	!CModule::IncludeModule("sale") ||
	!CModule::IncludeModule("catalog") ||
	!CModule::IncludeModule("iblock")
){
	return;
}

global $USER;

if($USER->isAuthorized()){
	$arResult["USER_ID"] = $USER->getId();
	$arResult["USER_EMAIL"] = $USER->getEmail();
}

$this->IncludeComponentTemplate();
