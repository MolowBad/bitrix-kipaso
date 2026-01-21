<?
use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arParams["DISABLE_SECTION_SELECT"] = !empty($arParams["DISABLE_SECTION_SELECT"])
	? $arParams["DISABLE_SECTION_SELECT"]
	: "N";

if (
	!Loader::IncludeModule("iblock") ||
	!Loader::IncludeModule("search") ||
	!Loader::IncludeModule("catalog") ||
	!Loader::IncludeModule("sale")
) {
	throw new Exception("Modules is not installed");
}

$request = Context::getCurrent()->getRequest();
$query = Encoding::convertEncodingToCurrent($request->get("q"));

if(!empty($query) && strlen($query) > 1){

	global $APPLICATION, $arrFilter;

	$arResult["ITEMS_ID"] = array();

	$arParams["FILTER_NAME"] = "arrFilter";
	$arParams["LAZY_LOAD_PICTURES"] = !empty($arParams["LAZY_LOAD_PICTURES"])
		? $arParams["LAZY_LOAD_PICTURES"]
		: "N";

	if(empty($arParams["CURRENCY_ID"])){
		$arParams["CURRENCY_ID"] = CurrencyManager::getBaseCurrency();
		$arParams["CONVERT_CURRENCY"] = "Y";
	}

	$arResult["ITEMS"] = array();
	$arResult["QUERY"] = $arResult["~QUERY"] = trim($query);

	if(!empty($arParams["CONVERT_CASE"]) && $arParams["CONVERT_CASE"] == "Y"){
		$arLang = \CSearchLanguage::GuessLanguage($arResult["QUERY"]);
		if(is_array($arLang) && $arLang["from"] != $arLang["to"]){
			$arResult["QUERY"] = \CSearchLanguage::ConvertKeyboardLayout($arResult["QUERY"], $arLang["from"], $arLang["to"]);
			$arResult["QUERY_REPLACE"] = true;
		}
	}

	$arResult["QUERY_TITLE"] = GetMessage("SEARCH_RESULT")." - &laquo;".trim(htmlspecialcharsbx($arResult["QUERY"])."&raquo;");

	$APPLICATION->SetTitle($arResult["QUERY_TITLE"]);
	$APPLICATION->AddChainItem(trim(htmlspecialcharsbx($arResult["QUERY"])));

	$arResult["MENU_SECTIONS"] = array();

	$arFilter = Array(
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"INCLUDE_SUBSECTIONS" => "Y",
		"ACTIVE" => "Y",
	);

	if($arParams["HIDE_NOT_AVAILABLE"] == "Y"){
		$arFilter["CATALOG_AVAILABLE"] = "Y";
	}

	$obSearch = new CSearch;
	$arSearchParams = array(
		"QUERY" => $arResult["QUERY"],
		"SITE_ID" => SITE_ID,
		"MODULE_ID" => "iblock",
		"PARAM2" => $arParams["IBLOCK_ID"]
	);

	$obSearch->Search(
		$arSearchParams,
		array(),
		array(
			"STEMMING" => !empty($arParams["STEMMING"]) && $arParams["STEMMING"] == "Y"
		)
	);
	while($searchItem = $obSearch->fetch()){
		if(is_numeric($searchItem["ITEM_ID"])){
			$arResult["ITEMS_ID"][$searchItem["ITEM_ID"]] = $searchItem["ITEM_ID"];
		}
	}

	$arrFilter["=ID"] = $arResult["ITEMS_ID"];

	if(!empty($arResult["ITEMS_ID"])){

		$arFilter["SECTION_ID"] = !empty($_REQUEST["SECTION_ID"]) ? intval($_REQUEST["SECTION_ID"]) : array();

		$res = \CIBlockElement::GetList(array(), array_merge($arFilter, $arrFilter), false, false, array("ID"));

		while($nextElement = $res->GetNext()){

			if($arParams["DISABLE_SECTION_SELECT"] == "N"){

				$resGroup = \CIBlockElement::GetElementGroups($nextElement["ID"], false);

				while($arGroup = $resGroup->Fetch()){
					$IBLOCK_SECTION_ID = $arGroup["ID"];
				}

				$arSections[$IBLOCK_SECTION_ID] = $IBLOCK_SECTION_ID;
				$arSectionCount[$IBLOCK_SECTION_ID] = !empty($arSectionCount[$IBLOCK_SECTION_ID]) ? $arSectionCount[$IBLOCK_SECTION_ID] + 1 : 1;

			}

			$arResult["ITEMS"][] = $nextElement;

		}

		if($arParams["DISABLE_SECTION_SELECT"] == "N"){

			if(!empty($arSections)){

				$arFilter = array("ID" => $arSections, "CNT_ACTIVE" => "Y", "ELEMENT_SUBSECTIONS" => "Y", "CNT_ALL" => "N");
				$rsSections = \CIBlockSection::GetList(array("SORT" => "DESC"), $arFilter);

				while ($arSection = $rsSections->Fetch()){
					$searchParam = "SECTION_ID=".$arSection["ID"];
					$searchID = intval($_REQUEST["SECTION_ID"]);
					$arSection["SELECTED"] = $arSection["ID"] == $searchID ? "Y" : "N";
					$arSection["FILTER_LINK"] = $APPLICATION->GetCurPageParam($searchParam , array("SECTION_ID"));
					$arSection["ELEMENTS_COUNT"] = $arSectionCount[$arSection["ID"]];
					array_push($arResult["MENU_SECTIONS"], $arSection);
				}
			}

		}

	}

	if(Option::get("search", "stat_phrase") == "Y"){
		$obSearch->NavStart(30, false);
		$obSearch->Statistic = new \CSearchStatistic($obSearch->strQueryText, $obSearch->strTagsText);
		$obSearch->Statistic->PhraseStat($obSearch->NavRecordCount, $obSearch->NavPageNomer);
	}

}


if(!empty($arResult["ITEMS"]) && count($arResult["ITEMS"]) == 1){
	if(!empty($arResult["ITEMS"][0]["ID"])){
		if($gLastProduct = \CIBlockElement::GetByID($arResult["ITEMS"][0]["ID"])){
			$arLastProduct = $gLastProduct->GetNext();
			if(!empty($arLastProduct["DETAIL_PAGE_URL"])){
				LocalRedirect($arLastProduct["DETAIL_PAGE_URL"]);
			}
		}
	}
}

$this->IncludeComponentTemplate();
