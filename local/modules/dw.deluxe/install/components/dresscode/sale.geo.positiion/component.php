<?

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

use Bitrix\Sale\Location;

if(!CModule::IncludeModule("sale")) {
	return;
}

if(empty($_SESSION["USER_GEO_POSITION"])){
	$arParams["INCLUDE_YANDEX_API"] = true;
}

$arParams["DISABLE_CONFIRMATION_WINDOW"] = !empty($arParams["DISABLE_CONFIRMATION_WINDOW"])
	? $arParams["DISABLE_CONFIRMATION_WINDOW"]
	: "N";

if(!empty($_SERVER["HTTP_USER_AGENT"])){
	if(preg_match('/bot|rambler|yahoo|crawl|spider|Lighthouse|Google|mediapartners|slurp|patrol/i', $_SERVER["HTTP_USER_AGENT"])){
		$arParams["INCLUDE_YANDEX_API"] = false;
		$arParams["IS_BOT"] = true;
	}
}

if(empty($arParams["CACHE_TIME"])){
	$arParams["CACHE_TIME"] = 1285192;
}

$cacheId = "site.sale.locations";
$obCache = new CPHPCache();

if($obCache->InitCache($arParams["CACHE_TIME"], $cacheId)){
	$arResult = $obCache->GetVars();
}

elseif($obCache->StartDataCache()){

	$res = Location\DefaultSiteTable::getList(array(
		"filter" => array(
			"SITE_ID" => SITE_ID,
			"LOCATION.NAME.LANGUAGE_ID" => LANGUAGE_ID
		),
		"order" => array(
			"SORT" => "asc"
		),
		"select" => array(
			"CODE" => "LOCATION.CODE",
			"ID" => "LOCATION.ID",
			"PARENT_ID" => "LOCATION.PARENT_ID",
			"TYPE_ID" => "LOCATION.TYPE_ID",
			"LATITUDE" => "LOCATION.LATITUDE",
			"LONGITUDE" => "LOCATION.LONGITUDE",
			"NAME" => "LOCATION.NAME.NAME",
			"SHORT_NAME" => "LOCATION.NAME.SHORT_NAME",
			"LEFT_MARGIN" => "LOCATION.LEFT_MARGIN",
			"RIGHT_MARGIN" => "LOCATION.RIGHT_MARGIN"
		)
	));

	while($item = $res->Fetch()){
		$arResult["DEFAULT_LOCATIONS"][$item["ID"]] = $item;
	}

	$obCache->EndDataCache($arResult);

}

$this->IncludeComponentTemplate();
