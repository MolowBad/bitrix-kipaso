<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

if(!\Bitrix\Main\Loader::includeModule("subscribe")){
	return false;
}

if(!isset($arParams["CACHE_TIME"])){
	$arParams["CACHE_TIME"] = 36000000;
}

$arParams["SITE_ID"] = !empty($arParams["SITE_ID"]) ? $arParams["SITE_ID"] : SITE_ID;

if(empty($arParams["RUBRIC_ID"])){
	return false;
}

$cacheID = array(
	"USER_GROUPS" => $USER->GetGroups(),
	"RUBRIC_ID" => $arParams["SUBRIC_ID"],
	"SITE_ID" => $arParams["SITE_ID"]
);

$arResult = array();

if($this->StartResultCache($arParams["CACHE_TIME"], serialize($cacheID))){

	$rsRubric = CRubric::GetList(
		array(
			"SORT" => "ASC",
			"NAME" => "ASC"
		),
		array(
			"ID" => intval($arParams["RUBRIC_ID"]),
			"ACTIVE" => "Y",
			"VISIBLE" => "Y",
			"LID" => $arParams["SITE_ID"]
		)
	);
	while($arRubric = $rsRubric->GetNext()){
		$arResult["RUBRIC"] = $arRubric;
	}

	$this->IncludeComponentTemplate();

}
