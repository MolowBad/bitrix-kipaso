<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

use Bitrix\Main\Loader;
use	Bitrix\Iblock;

if(!Loader::includeModule("iblock")){
	return false;
}

if(empty($arParams["SECTION_ID"]) && empty($arParams["SECTION_CODE"])){
	return false;
}

$arParams["MAX_VISIBLE_TAGS_DESKTOP"] = intval(!empty($arParams["MAX_VISIBLE_TAGS_DESKTOP"]) ? $arParams["MAX_VISIBLE_TAGS_DESKTOP"] : "10");
$arParams["MAX_VISIBLE_TAGS_MOBILE"] = intval(!empty($arParams["MAX_VISIBLE_TAGS_MOBILE"]) ? $arParams["MAX_VISIBLE_TAGS_MOBILE"] : "6");
$arParams["USE_IBLOCK_MAIN_SECTION_TREE"] = !empty($arParams["USE_IBLOCK_MAIN_SECTION_TREE"]) ? $arParams["USE_IBLOCK_MAIN_SECTION_TREE"] : "N";
$arParams["USE_IBLOCK_MAIN_SECTION"] = !empty($arParams["USE_IBLOCK_MAIN_SECTION"]) ? $arParams["USE_IBLOCK_MAIN_SECTION"] : "N";
$arParams["TAGS_MAX_DEPTH_LEVEL"] = intval(!empty($arParams["TAGS_MAX_DEPTH_LEVEL"]) ? $arParams["TAGS_MAX_DEPTH_LEVEL"] : "5");
$arParams["SECTION_DEPTH_LEVEL"] = intval(!empty($arParams["SECTION_DEPTH_LEVEL"]) ? $arParams["SECTION_DEPTH_LEVEL"] : 0);
$arParams["INCLUDE_SUBSECTIONS"] = !empty($arParams["INCLUDE_SUBSECTIONS"]) ? $arParams["INCLUDE_SUBSECTIONS"] : "Y";
$arParams["HIDE_TAGS_ON_MOBILE"] = !empty($arParams["HIDE_TAGS_ON_MOBILE"]) ? $arParams["HIDE_TAGS_ON_MOBILE"] : "N";
$arParams["HIDE_NOT_AVAILABLE"] = !empty($arParams["HIDE_NOT_AVAILABLE"]) ? $arParams["HIDE_NOT_AVAILABLE"] : "N";
$arParams["CURRENT_TAG"] = !empty($arParams["CURRENT_TAG"]) ? $arParams["CURRENT_TAG"] : "";
$arParams["SORT_FIELD"] = !empty($arParams["SORT_FIELD"]) ? $arParams["SORT_FIELD"] : "COUNTER";
$arParams["SORT_TYPE"] = !empty($arParams["SORT_TYPE"]) ? $arParams["SORT_TYPE"] : "DESC";
$arParams["MAX_TAGS"] = intval(!empty($arParams["MAX_TAGS"]) ? $arParams["MAX_TAGS"] : "30");

global $APPLICATION, $USER, $arrFilter, $preFilter;

if($arParams["TAGS_MAX_DEPTH_LEVEL"] < $arParams["SECTION_DEPTH_LEVEL"]){
	return false;
}

$cacheID = array(
	"USE_MAIN_SECTION_TREE" => $arParams["USE_MAIN_SECTION_TREE"],
	"INCLUDE_SUBSECTIONS" => $arParams["INCLUDE_SUBSECTIONS"],
	"HIDE_TAGS_ON_MOBILE" => $arParams["HIDE_TAGS_ON_MOBILE"],
	"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
	"USE_MAIN_SECTION" => $arParams["SECTION_DEPTH_LEVEL"],
	"SECTION_CODE_PATH" => $arParams["SECTION_CODE_PATH"],
	"SECTION_CODE" => $arParams["SECTION_CODE"],
	"CURRENT_TAG" => $arParams["CURRENT_TAG"],
	"SECTION_ID" => $arParams["SECTION_ID"],
	"SEF_FOLDER" => $arParams["SEF_FOLDER"],
	"IBLOCK_ID" => $arParams["IBLOCK_ID"],
	"MAX_TAGS" => $arParams["MAX_TAGS"],
	"USER_GROUPS" => $USER->GetGroups(),
	"SITE_ID" => SITE_ID
);

if($this->StartResultCache($arParams["CACHE_TIME"], serialize($cacheID))){

	$arFilter = array(
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"!TAGS" => false,
		"ACTIVE_DATE" => "Y",
		"ACTIVE" => "Y",
		"INCLUDE_SUBSECTIONS" => $arParams["INCLUDE_SUBSECTIONS"],
		"IBLOCK_LID" => SITE_ID
	);

	if(!empty($arParams["SECTION_ID"])){
		$arFilter["=SECTION_ID"] = $arParams["SECTION_ID"];
	}

	else{
		$arFilter["=SECTION_CODE"] = $arParams["SECTION_CODE"];
	}

	if($arParams["HIDE_NOT_AVAILABLE"] == "Y"){
		$arFilter["CATALOG_AVAILABLE"] = "Y";
	}

	$sectionPath = (!empty($arParams["SECTION_CODE_PATH"])
		? $arParams["SECTION_CODE_PATH"]
		: $arParams["SECTION_CODE"]);

	if(empty($sectionPath) && !empty($arParams["SECTION_ID"])){
		$sectionPath = $arParams["SECTION_ID"];
	}

	if($arParams["USE_IBLOCK_MAIN_SECTION"] == "Y"){

		$currentSectionId = 0;

		$arIBlock = CIBlock::GetArrayByID($arParams["IBLOCK_ID"]);

		if(
			!empty($arIBlock["FIELDS"]["IBLOCK_SECTION"]["DEFAULT_VALUE"]["KEEP_IBLOCK_SECTION_ID"]) &&
			$arIBlock["FIELDS"]["IBLOCK_SECTION"]["DEFAULT_VALUE"]["KEEP_IBLOCK_SECTION_ID"] == "Y"
		){
			$keepIblockSectionId = $arIBlock["FIELDS"]["IBLOCK_SECTION"]["DEFAULT_VALUE"]["KEEP_IBLOCK_SECTION_ID"];
		}

		if(!empty($keepIblockSectionId)){

			if(!empty($arParams["SECTION_ID"])){
				$currentSectionId = $arParams["SECTION_ID"];
			}
			else{

				$arSectionFilter = array("IBLOCK_ID" => $arParams["IBLOCK_ID"], "=SECTION_CODE" => $arParams["SECTION_CODE"], "=SECTION_ID" => $arParams["SECTION_ID"]);
				$rsSection = CIBlockSection::GetList(array($by => $order), $arSectionFilter, false);

				if($arSection = $rsSection->GetNext()){
					$currentSectionId = $arSection["ID"];
				}

			}

			if($arParams["USE_IBLOCK_MAIN_SECTION_TREE"] == "N"){
				$arFilter["=IBLOCK_SECTION_ID"] = $arParams["SECTION_ID"];
			}

		}

	}

	if(!empty($arParams["SEF_URL_TEMPLATES"]["section"])){
		$tagPath = $arParams["SEF_FOLDER"] . str_replace(array("#SECTION_CODE_PATH#", "#SECTION_CODE#", "#SECTION_ID#"), $sectionPath, $arParams["SEF_URL_TEMPLATES"]["section"]);
	}

	else{
		$tagPath = $arParams["SEF_FOLDER"] . $sectionPath . "/";
	}

	$rsProducts = CIBlockElement::GetList(
		array(),
		$arFilter,
		false,
		false,
		array(
			"ID",
			"TAGS",
			"IBLOCK_SECTION_ID"
		)
	);

	while($obNextProduct = $rsProducts->GetNextElement()){

		$arProduct = $obNextProduct->GetFields();

		if(
			$arParams["USE_IBLOCK_MAIN_SECTION"] == "Y" &&
			$arParams["USE_IBLOCK_MAIN_SECTION_TREE"] == "Y"
		){
			if(
				!DwItemInfo::checkTagSectionTree(
					$currentSectionId,
					$arProduct["IBLOCK_SECTION_ID"]
				)
			){
				continue 1;
			}
		}

		if(!empty($arProduct["TAGS"])){

			$arTags = explode(",", $arProduct["TAGS"]);

			foreach($arTags as $inx => $tagName){

				$tagCode = Cutil::translit(
					$tagName,
					LANGUAGE_ID,
					array(
						"change_case" => "L",
						"replace_space" => "-",
						"replace_other" => "-"
					)
				);

				$arTag = array(
					"LINK" => $tagPath."tag/".$tagCode."/",
					"NAME" => $tagName,
					"CODE" => $tagCode,
					"COUNTER" => 1
				);

				if($arParams["CURRENT_TAG"] == $tagCode){
					$arTag["LINK"] = $tagPath;
					$arTag["SELECTED"] = "Y";
				}

				if(!empty($arResult["TAGS"][$tagCode])){
					$arTag["COUNTER"] = $arResult["TAGS"][$tagCode]["COUNTER"] + 1;
				}

				$arResult["TAGS"][$tagCode] = $arTag;

			}

		}

	}

	if(!empty($arResult["TAGS"])){

		$arResult["TAGS"] = array_slice($arResult["TAGS"], 0, intval($arParams["MAX_TAGS"]), true);

		uasort($arResult["TAGS"], function($a, $b) use($arParams){

			if($a[$arParams["SORT_FIELD"]] == $b[$arParams["SORT_FIELD"]]){
				return false;
			}

			if($arParams["SORT_TYPE"] == "DESC"){
				return (trim($a[$arParams["SORT_FIELD"]]) > trim($b[$arParams["SORT_FIELD"]])) ? -1 : 1;
			}
			else{
				if($arParams["SORT_TYPE"] == "ASC"){
					return (trim($a[$arParams["SORT_FIELD"]]) < trim($b[$arParams["SORT_FIELD"]])) ? -1 : 1;
				}
			}

		});

	}

	$this->IncludeComponentTemplate();

}

if(!empty($arResult["TAGS"][$arParams["CURRENT_TAG"]])){

	$arResult["CURRENT_TAG"] = $arResult["TAGS"][$arParams["CURRENT_TAG"]];

	$arrFilter["?TAGS"] = $arResult["CURRENT_TAG"]["NAME"];
	$preFilter["?TAGS"] = $arResult["CURRENT_TAG"]["NAME"];

	$arResult["SEO"] = DwItemInfo::getSeoByTag(
		trim($arResult["CURRENT_TAG"]["NAME"]),
		$arParams["CURRENT_TAG"],
		$arParams["IBLOCK_ID"],
		$arParams["SECTION_ID"]
	);

	return $arResult;

}

else{
	if(!empty($arParams["CURRENT_TAG"])){
		if(Loader::includeModule("iblock")){
			Iblock\Component\Tools::process404(
				trim($arParams["MESSAGE_404"]) ?: GetMessage("CATALOG_TAGS_MESSAGE_404"),
				true,
				$arParams["SET_STATUS_404"] === "Y",
				$arParams["SHOW_404"] === "Y",
				$arParams["FILE_404"]
			);
		}
	}

	return false;
}
