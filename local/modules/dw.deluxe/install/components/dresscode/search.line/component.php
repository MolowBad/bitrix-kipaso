<?
use Bitrix\Main\Context;
use Bitrix\Main\Text\Encoding;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

$arParams["LAZY_LOAD_PICTURES"] = !empty($arParams["LAZY_LOAD_PICTURES"])
	? $arParams["LAZY_LOAD_PICTURES"]
	: "N";

$request = Context::getCurrent()->getRequest();
$query = Encoding::convertEncodingToCurrent($request->get("q"));

$arResult["SELECTED"] = !empty($_GET["where"]) ? intval($_GET["where"]) : 0;
$cacheID = !empty($query) || !empty($_GET["where"]) ? time() : 0;

if ($this->StartResultCache($arParams["CACHE_TIME"], $cacheID)){

	if(!empty($query) && $_GET["r"] == "Y"){
		$arResult["q"] = htmlspecialchars($query);
		$this->AbortResultCache();
	}

	$this->IncludeComponentTemplate();
}
