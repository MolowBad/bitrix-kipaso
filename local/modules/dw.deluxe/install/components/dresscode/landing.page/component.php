<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

global $CACHE_MANAGER, $APPLICATION, $USER;

if (
	!CModule::IncludeModule("sale") ||
	!CModule::IncludeModule("catalog") ||
	!CModule::IncludeModule("iblock")
){
	return;
}

if(!isset($arParams["CACHE_TIME"])){
	$arParams["CACHE_TIME"] = 36000000;
}

if(empty($arParams["CACHE_TYPE"])){
	$arParams["CACHE_TYPE"] = "Y";
}

if(empty($arParams["IGNORE_UTM"])){
	$arParams["IGNORE_UTM"] = "Y";
}

if(!empty($arParams["IGNORE_URL_PARAMS"])){
	foreach($arParams["IGNORE_URL_PARAMS"] as $index => $nextParam){
		if(empty($nextParam)){
			unset($arParams["IGNORE_URL_PARAMS"][$index]);
		}
	}
}

if(empty($arParams["IGNORE_URL_PARAMS"])){
	$arParams["IGNORE_URL_PARAMS"] = array(
		"sort_field",
		"sort_to",
		"view",
		"clear_cache",
		"bitrix_include_areas",
		"set_filter",
		"show_page_exec_time",
		"show_include_exec_time",
		"show_sql_stat"
	);
}

if(empty($arParams["BIG_PICTURE_WIDTH"])){
	$arParams["BIG_PICTURE_WIDTH"] = 1920;
}

if(empty($arParams["BIG_PICTURE_HEIGHT"])){
	$arParams["BIG_PICTURE_HEIGHT"] = 500;
}

if(empty($arParams["BIG_PICTURE_WIDTH"])){
	$arParams["SMALL_PICTURE_WIDTH"] = 600;
}

if(empty($arParams["BIG_PICTURE_HEIGHT"])){
	$arParams["SMALL_PICTURE_HEIGHT"] = 600;
}

$arResult = array();
$arCacheID = array();
$arResult["PAGE_STORAGE"] = array();

$arCacheID = array(
	"IBLOCK_ID" => $arParams["IBLOCK_ID"],
	"GROUPS" => $USER->GetGroups(),
	"SITE_ID" => SITE_ID
);

$cacheDir = implode(
  "/",
  [
    SITE_ID,
    'dresscode',
    'landing.page'
  ]
);

$obExtraCache = new CPHPCache();

if(
	$arParams["CACHE_TYPE"] != "N" &&
	$obExtraCache->InitCache($arParams["CACHE_TIME"], serialize($arCacheID), $cacheDir)
){
	$arResult = $obExtraCache->GetVars();
}

elseif($obExtraCache->StartDataCache()){


	if(empty($arParams["IBLOCK_ID"])){
		$obExtraCache->AbortDataCache();
	}

	$arSelect = array(
		"ID",
		"NAME",
		"IBLOCK_ID",
		"IBLOCK_TYPE",
		"PREVIEW_TEXT",
		"DETAIL_TEXT",
		"DETAIL_PICTURE",
		"PREVIEW_PICTURE"
	);

	$arFilter = array(
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"ACTIVE_DATE" => "Y",
		"ACTIVE" => "Y"
	);

	$iterator = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);

	while($obElement = $iterator->GetNextElement()){

		$arNextElement = $obElement->GetFields();
		$arNextElement["PROPERTIES"] = $obElement->GetProperties();

		if(!empty($arNextElement["PROPERTIES"]["LINK"]["VALUE"])){

			$arResult["PAGE_STORAGE"][$arNextElement["ID"]] = $arNextElement["PROPERTIES"]["LINK"]["VALUE"];
			$arResult["PAGES"][$arNextElement["ID"]] = $arNextElement;

		}

	}

	$CACHE_MANAGER->StartTagCache($cacheDir);
	$CACHE_MANAGER->RegisterTag("iblock_id_".$arParams["IBLOCK_ID"]);
	$CACHE_MANAGER->EndTagCache();

	$obExtraCache->EndDataCache($arResult);

}

foreach($arParams["IGNORE_URL_PARAMS"] as $index => $paramCode){

	if(strstr($paramCode, "*")){

		unset($arParams["IGNORE_URL_PARAMS"][$index]);

		foreach($_GET as $code => $nextParam){

			if(strstr($code, str_replace("*", "", $paramCode))){
				$arParams["IGNORE_URL_PARAMS"][] = $code;
			}
		}
	}
}

if($arParams["IGNORE_UTM"] == "Y"){

	foreach($_GET as $code => $nextParam){

		if(strstr($code, "utm_")){
			$arParams["IGNORE_URL_PARAMS"][] = $code;
		}

	}

}

$curPageUrl = $APPLICATION->GetCurPageParam("", $arParams["IGNORE_URL_PARAMS"], false);
$searchPage = array_search($curPageUrl, $arResult["PAGE_STORAGE"]);

if(!empty($searchPage) && !empty($arResult["PAGES"][$searchPage])){

	$arPageData = $arResult["PAGES"][$searchPage];

	$pageBrowserTitle = !empty($arPageData["PROPERTIES"]["SEO_TITLE"]["VALUE"]) ? $arPageData["PROPERTIES"]["SEO_TITLE"]["VALUE"] : "";
	$pageTitle = !empty($arPageData["PROPERTIES"]["SEO_PAGE_TITLE"]["VALUE"]) ? $arPageData["PROPERTIES"]["SEO_PAGE_TITLE"]["VALUE"] : "";
	$pageMetaKeywords = !empty($arPageData["PROPERTIES"]["META_KEYWORDS"]["VALUE"]) ? $arPageData["PROPERTIES"]["META_KEYWORDS"]["VALUE"] : "";
	$pageMetaDescription = !empty($arPageData["PROPERTIES"]["META_DESCRIPTION"]["VALUE"]) ? $arPageData["PROPERTIES"]["META_DESCRIPTION"]["VALUE"] : "";
	$pageTopText = !empty($arPageData["~PREVIEW_TEXT"]) ? $arPageData["~PREVIEW_TEXT"] : "";
	$pageBottomText = !empty($arPageData["~DETAIL_TEXT"]) ? $arPageData["~DETAIL_TEXT"] : "";

	$arTitleOptions = array(
		"COMPONENT_NAME" => $this->getName()
	);

	if(!empty($pageTitle)){
		$APPLICATION->SetTitle($pageTitle, $arTitleOptions);
	}

	if(!empty($pageBrowserTitle)){
		$APPLICATION->SetPageProperty("title", $pageBrowserTitle, $arTitleOptions);
	}

	if(!empty($pageMetaKeywords)){
		$APPLICATION->SetPageProperty("keywords", $pageMetaKeywords, $arTitleOptions);
	}

	if(!empty($pageMetaDescription)){
		$APPLICATION->SetPageProperty("description", $pageMetaDescription, $arTitleOptions);
	}

	if(!empty($pageTopText)){
		$arResult["PAGE_TOP_TEXT"] = $pageTopText;
	}

	if(!empty($pageBottomText)){
		$arResult["PAGE_BOTTOM_TEXT"] = $pageBottomText;
	}

	if(!empty($arPageData["PREVIEW_PICTURE"])){

		$arBanner = array();
		$arBanner["BIG_PICTURE"] = $arPageData["PREVIEW_PICTURE"];

		if(!empty($arPageData["DETAIL_PICTURE"])){
			$arBanner["SMALL_PICTURE"] = $arPageData["DETAIL_PICTURE"];
		}

		if(!empty($arPageData["PROPERTIES"]["BANNER_TEXT"])){
			$arBanner["TEXT"] = $arPageData["PROPERTIES"]["BANNER_TEXT"];
		}

		$arResult["BANNER"] = $arBanner;

	}

}

$this->IncludeComponentTemplate();
