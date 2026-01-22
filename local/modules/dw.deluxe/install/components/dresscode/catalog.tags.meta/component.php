<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

global $APPLICATION;

if(!empty($arParams["TAG_NAME"])){
	$APPLICATION->AddChainItem($arParams["TAG_NAME"], "");
}

if(!empty($arParams["META_TITLE"])){
	$APPLICATION->SetPageProperty("title", $arParams["META_TITLE"]);
}

if(!empty($arParams["META_HEADING"])){
	$APPLICATION->SetTitle($arParams["META_HEADING"]);
}

if(isset($arParams["META_KEYWORDS"])){
	$APPLICATION->SetPageProperty("keywords", $arParams["META_KEYWORDS"]);
}

if(isset($arParams["META_DESCRIPTION"])){
	$APPLICATION->SetPageProperty("description", $arParams["META_DESCRIPTION"]);
}
